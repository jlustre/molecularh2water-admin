<?php

namespace App\Services\Crm;

use App\Models\Crm\Customer;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\Crm\PipelineStageHistory;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FunnelService
{
    public function __construct(
        private readonly TimelineService $timeline,
        private readonly CrmLookupService $lookups,
    ) {}

    public function moveLead(Lead|Prospect|Customer|Recruit $lead, FunnelStage $stage, User $user, ?int $lostReasonId = null, ?string $lostReasonDetail = null): Lead|Prospect|Customer|Recruit
    {
        if ($stage->is_lost) {
            $lostFields = $this->lookups->resolveLeadLostReason($lostReasonId, $lostReasonDetail, required: true);
        } else {
            $lostFields = ['lost_reason_id' => null, 'lost_reason' => null];
        }

        $previousStage = $lead->stage;
        $previousName = $previousStage?->name;

        $payload = [
            'funnel_id' => $stage->funnel_id,
            'funnel_stage_id' => $stage->id,
        ];

        if ($stage->is_lost) {
            $payload['lost_reason_id'] = $lostFields['lost_reason_id'];
            $payload['lost_reason'] = $lostFields['lost_reason'];
        } else {
            $payload['lost_reason_id'] = null;
            $payload['lost_reason'] = null;
        }

        $lead->update($payload);

        $this->recordStageHistory($lead, $previousStage, $stage, $user);

        $this->timeline->log(
            $lead,
            'funnel_moved',
            'Moved to '.$stage->name,
            $previousName
                ? "Stage changed from {$previousName} to {$stage->name}."
                : "Stage set to {$stage->name}.",
            [
                'from_stage' => $previousName,
                'to_stage' => $stage->name,
                'from_stage_id' => $previousStage?->id,
                'to_stage_id' => $stage->id,
                'lost_reason_id' => $lostFields['lost_reason_id'] ?? null,
                'lost_reason' => $lostFields['lost_reason'] ?? null,
            ],
            $user,
        );

        $lead = $lead->fresh(['stage', 'assignedUser', 'funnel', 'lostReason']);

        if ($stage->slug === config('crm.closed_won_stage_slug', 'closed-won') && $stage->is_won) {
            $lead = app(AfterSalesService::class)->handleClosedWon($lead, $user);
        }

        app(CrmAutomationService::class)->dispatch('stage.moved', [
            'lead_id' => $lead->id,
            'stage_slug' => $stage->slug,
            'from_stage_slug' => $previousStage?->slug,
        ], $user);

        return $lead;
    }

    public function moveLeadToStageSlug(Lead|Prospect|Customer|Recruit $lead, string $slug, User $user, ?int $lostReasonId = null, ?string $lostReasonDetail = null): Lead|Prospect|Customer|Recruit|null
    {
        $stage = $this->findStageForLead($lead, $slug);

        if (! $stage || $lead->funnel_stage_id === $stage->id) {
            return $lead;
        }

        return $this->moveLead($lead, $stage, $user, $lostReasonId, $lostReasonDetail);
    }

    public function findStageForLead(Lead|Prospect|Customer|Recruit $lead, string $slug): ?FunnelStage
    {
        if (! $lead->funnel_id) {
            return null;
        }

        return FunnelStage::query()
            ->where('funnel_id', $lead->funnel_id)
            ->where('slug', $slug)
            ->first();
    }

    public function findStageForFunnel(string|Funnel $funnel, string $slug): ?FunnelStage
    {
        $funnelId = $funnel instanceof Funnel
            ? $funnel->id
            : Funnel::query()->where('slug', $funnel)->value('id');

        if (! $funnelId) {
            return null;
        }

        return FunnelStage::query()
            ->where('funnel_id', $funnelId)
            ->where('slug', $slug)
            ->first();
    }

    public function moveLeadToFunnelStage(Lead|Prospect|Customer|Recruit $lead, string $funnelSlug, string $stageSlug, User $user): Lead|Prospect|Customer|Recruit|null
    {
        $stage = $this->findStageForFunnel($funnelSlug, $stageSlug);

        if (! $stage || $lead->funnel_stage_id === $stage->id) {
            return $lead;
        }

        return $this->moveLead($lead, $stage, $user);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>|null  $stages
     */
    public function createFunnel(array $data, ?array $stages = null): Funnel
    {
        $slug = Str::slug((string) Arr::get($data, 'slug', Arr::get($data, 'name', 'pipeline')));

        $funnel = Funnel::query()->create([
            'name' => trim((string) Arr::get($data, 'name')),
            'slug' => $this->uniqueFunnelSlug($slug),
            'description' => Arr::get($data, 'description'),
            'is_default' => (bool) Arr::get($data, 'is_default', false),
            'is_active' => (bool) Arr::get($data, 'is_active', true),
        ]);

        foreach ($stages ?? [] as $stage) {
            $funnel->stages()->create([
                'name' => $stage['name'],
                'slug' => $stage['slug'],
                'color' => $stage['color'] ?? 'slate',
                'sort_order' => $stage['sort_order'],
                'is_won' => (bool) ($stage['is_won'] ?? false),
                'is_lost' => (bool) ($stage['is_lost'] ?? false),
            ]);
        }

        return $funnel->fresh('stages');
    }

    public function seedStages(Funnel $funnel, array $stages): void
    {
        foreach ($stages as $stage) {
            FunnelStage::query()->updateOrCreate(
                [
                    'funnel_id' => $funnel->id,
                    'slug' => $stage['slug'],
                ],
                [
                    'name' => $stage['name'],
                    'color' => $stage['color'] ?? 'slate',
                    'sort_order' => $stage['sort_order'],
                    'is_won' => (bool) ($stage['is_won'] ?? false),
                    'is_lost' => (bool) ($stage['is_lost'] ?? false),
                ],
            );
        }
    }

    private function recordStageHistory(Lead|Prospect|Customer|Recruit $lead, ?FunnelStage $from, FunnelStage $to, User $user): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('pipeline_stage_histories')) {
            return;
        }

        $lastHistory = PipelineStageHistory::query()
            ->where('contact_type', $lead->getMorphClass())
            ->where('contact_id', $lead->id)
            ->latest()
            ->first();

        $duration = $lastHistory
            ? (int) $lastHistory->created_at->diffInSeconds(now())
            : ($lead->updated_at ? (int) $lead->updated_at->diffInSeconds(now()) : null);

        PipelineStageHistory::query()->create([
            'contact_type' => $lead->getMorphClass(),
            'contact_id' => $lead->id,
            'funnel_id' => $to->funnel_id,
            'from_stage_id' => $from?->id,
            'to_stage_id' => $to->id,
            'user_id' => $user->id,
            'duration_in_previous_stage_seconds' => $from ? $duration : null,
        ]);
    }

    private function uniqueFunnelSlug(string $slug): string
    {
        $base = $slug !== '' ? $slug : 'pipeline';
        $candidate = $base;
        $counter = 2;

        while (Funnel::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createStage(Funnel $funnel, array $data): FunnelStage
    {
        $name = trim((string) Arr::get($data, 'name'));
        $slug = $this->uniqueStageSlug($funnel, Str::slug($name));
        $sortOrder = (int) ($funnel->stages()->max('sort_order') ?? 0) + 1;

        return $funnel->stages()->create([
            'name' => $name,
            'slug' => $slug,
            'color' => Arr::get($data, 'color', 'slate'),
            'sort_order' => $sortOrder,
            'is_won' => (bool) Arr::get($data, 'is_won', false),
            'is_lost' => (bool) Arr::get($data, 'is_lost', false),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStage(FunnelStage $stage, array $data): FunnelStage
    {
        $name = trim((string) Arr::get($data, 'name', $stage->name));

        $stage->update([
            'name' => $name,
            'slug' => $this->uniqueStageSlug($stage->funnel, Str::slug($name), $stage->id),
            'color' => Arr::get($data, 'color', $stage->color),
            'is_won' => (bool) Arr::get($data, 'is_won', $stage->is_won),
            'is_lost' => (bool) Arr::get($data, 'is_lost', $stage->is_lost),
        ]);

        return $stage->fresh();
    }

    public function deleteStage(FunnelStage $stage): void
    {
        if ($stage->leads()->exists()) {
            throw ValidationException::withMessages([
                'stage' => 'Cannot delete a stage that still contains leads. Move those records first.',
            ]);
        }

        $stage->delete();
    }

    public function moveStage(FunnelStage $stage, string $direction): void
    {
        $sibling = $stage->funnel->stages()
            ->when($direction === 'up', fn ($query) => $query->where('sort_order', '<', $stage->sort_order)->orderByDesc('sort_order'))
            ->when($direction === 'down', fn ($query) => $query->where('sort_order', '>', $stage->sort_order)->orderBy('sort_order'))
            ->first();

        if (! $sibling) {
            return;
        }

        $currentOrder = $stage->sort_order;
        $stage->update(['sort_order' => $sibling->sort_order]);
        $sibling->update(['sort_order' => $currentOrder]);
    }

    private function uniqueStageSlug(Funnel $funnel, string $slug, ?int $ignoreId = null): string
    {
        $base = $slug !== '' ? $slug : 'stage';
        $candidate = $base;
        $counter = 2;

        while ($funnel->stages()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
