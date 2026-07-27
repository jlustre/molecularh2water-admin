<?php

namespace App\Services\Crm;

use App\Enums\Crm\EngagementType;
use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\Crm\LeadSource;
use App\Models\Crm\Lifecycle;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use App\Support\BusinessLineResolver;
use App\Support\Crm\CrmContactResolver;
use App\Support\Crm\CrmScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LeadService
{
    public function __construct(
        private readonly TimelineService $timeline,
        private readonly CrmLookupService $lookups,
        private readonly FunnelService $funnels,
        private readonly CrmContactTransferService $transfers,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int|string>  $tagIds
     * @return Lead|Prospect|Customer|Recruit
     */
    public function create(array $data, User $user, array $tagIds = []): Model
    {
        $lifecycle = LeadLifecycle::from(Arr::get($data, 'lifecycle', LeadLifecycle::Lead->value));
        $payload = $this->preparePayload($data, $user, $lifecycle);
        $class = CrmContactResolver::modelClassFor($lifecycle);

        /** @var Lead|Prospect|Customer|Recruit $contact */
        $contact = $class::query()->create($payload);
        $this->syncTags($contact, $tagIds);

        $this->timeline->log(
            $contact,
            'record_created',
            ucfirst($lifecycle->value).' created',
            'Record created in the CRM.',
            ['lifecycle' => $lifecycle->value],
            $user,
        );

        $contact = $contact->fresh(['source', 'stage', 'assignedUser', 'tags', 'lifecycleRecord']);

        if ($contact->stage) {
            $contact = $this->funnels->syncLifecycleForStage($contact, $contact->stage, $user);
        }

        return $contact->fresh(['source', 'stage', 'assignedUser', 'tags', 'lifecycleRecord']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int|string>  $tagIds
     * @return Lead|Prospect|Customer|Recruit
     */
    public function update(Model $contact, array $data, User $user, array $tagIds = []): Model
    {
        $lifecycle = CrmContactResolver::lifecycleForModel($contact);
        $originalAssignee = $contact->assigned_user_id;
        $originalStageId = $contact->funnel_stage_id;
        $requestedStageId = Arr::has($data, 'funnel_stage_id')
            ? (filled(Arr::get($data, 'funnel_stage_id')) ? (int) Arr::get($data, 'funnel_stage_id') : null)
            : $originalStageId;

        $payload = $this->preparePayload(
            Arr::except($data, ['funnel_stage_id', 'lost_reason_id', 'lost_reason_detail', 'lifecycle']),
            $user,
            $lifecycle,
            $contact,
            preserveStage: true,
        );

        $contact->update($payload);
        $this->syncTags($contact, $tagIds);
        $contact->refresh();

        if ((int) $originalAssignee !== (int) $contact->assigned_user_id) {
            $this->timeline->log(
                $contact,
                'assignment_changed',
                'Assignment updated',
                $contact->assignedUser
                    ? "Assigned to {$contact->assignedUser->name}."
                    : 'Assignment removed.',
                ['assigned_user_id' => $contact->assigned_user_id],
                $user,
            );
        }

        $this->timeline->log(
            $contact,
            'record_updated',
            'Record updated',
            'Contact details were updated.',
            null,
            $user,
        );

        if ($requestedStageId && (int) $requestedStageId !== (int) $originalStageId) {
            $stage = FunnelStage::query()->find($requestedStageId);

            if ($stage) {
                $lostReasonId = $stage->is_lost && filled(Arr::get($data, 'lost_reason_id'))
                    ? (int) Arr::get($data, 'lost_reason_id')
                    : null;

                $contact = $this->funnels->moveLead(
                    $contact->fresh(),
                    $stage,
                    $user,
                    $lostReasonId,
                    Arr::get($data, 'lost_reason_detail'),
                );
            }
        }

        return $contact->fresh(['source', 'stage', 'assignedUser', 'tags', 'funnel', 'lifecycleRecord']);
    }

    public function delete(Model $contact, User $user): void
    {
        $this->timeline->log(
            $contact,
            'record_deleted',
            'Record deleted',
            'Record was removed from the CRM.',
            null,
            $user,
        );

        $contact->delete();
    }

    /**
     * @return Lead|Prospect|Customer|Recruit
     */
    public function convertLifecycle(Model $contact, LeadLifecycle $to, User $user): Model
    {
        return $this->transfers->transfer($contact, $to, $user);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePayload(
        array $data,
        User $user,
        LeadLifecycle $lifecycle,
        ?Model $existing = null,
        bool $preserveStage = false,
    ): array {
        $funnel = $this->resolveFunnel($lifecycle, $data, $existing);
        $defaultStage = $funnel
            ? FunnelStage::query()->where('funnel_id', $funnel->id)->orderBy('sort_order')->first()
            : null;

        $assignedUserId = Arr::get($data, 'assigned_user_id');

        if (! CrmScope::userCanViewAll($user)) {
            $assignedUserId = $user->id;
        }

        $status = LeadStatus::normalize(Arr::get($data, 'status', $existing?->status?->value ?? LeadStatus::New->value))
            ?? LeadStatus::New;

        $payload = [
            'lifecycle_id' => Lifecycle::idFor($lifecycle),
            'business_line' => Arr::has($data, 'business_line')
                ? BusinessLineResolver::forLead($data, $user)
                : ($existing?->business_line?->value ?? BusinessLineResolver::defaultForUser($user)),
            'status' => $status->value,
            'temperature' => Arr::get($data, 'temperature', $existing?->temperature?->value ?? 'cold'),
            'score' => (int) Arr::get($data, 'score', $existing?->score ?? 0),
            'first_name' => trim((string) Arr::get($data, 'first_name')),
            'last_name' => Arr::get($data, 'last_name') ? trim((string) Arr::get($data, 'last_name')) : null,
            'email' => Arr::get($data, 'email') ?: null,
            'phone' => Arr::get($data, 'phone') ?: null,
            'address' => Arr::get($data, 'address') ?: null,
            'city' => Arr::get($data, 'city') ?: null,
            'state' => Arr::get($data, 'state') ?: null,
            'country' => Arr::get($data, 'country') ?: null,
            'company' => Arr::get($data, 'company') ?: null,
            'occupation' => Arr::get($data, 'occupation') ?: null,
            'spouse_name' => Arr::get($data, 'spouse_name') ?: null,
            'spouse_occupation' => Arr::get($data, 'spouse_occupation') ?: null,
            'best_time_to_contact' => Arr::get($data, 'best_time_to_contact') ?: null,
            'lead_source_id' => Arr::get($data, 'lead_source_id') ?: null,
            'funnel_id' => $preserveStage
                ? ($existing?->funnel_id ?? $funnel?->id)
                : $this->resolveFunnelId($lifecycle, $data, $existing, $funnel),
            'funnel_stage_id' => $preserveStage
                ? $existing?->funnel_stage_id
                : $this->resolveStageId($data, $existing, $funnel, $defaultStage),
            'assigned_user_id' => $assignedUserId ?: null,
            'interested_in' => Arr::get($data, 'interested_in') ?: null,
            'message' => Arr::get($data, 'message') ?: null,
            'last_contacted_at' => Arr::get($data, 'last_contacted_at'),
            'next_follow_up_at' => Arr::get($data, 'next_follow_up_at'),
            'consent_given' => (bool) Arr::get($data, 'consent_given', $existing?->consent_given ?? false),
        ];

        if (in_array($lifecycle, [LeadLifecycle::Client, LeadLifecycle::Recruit], true)) {
            $defaultType = $lifecycle === LeadLifecycle::Client
                ? EngagementType::Customer->value
                : EngagementType::Recruit->value;

            $payload['engagement_type'] = Arr::get(
                $data,
                'engagement_type',
                $existing?->getAttribute('engagement_type')?->value
                    ?? $existing?->getAttribute('engagement_type')
                    ?? $defaultType,
            );
        }

        $stageId = Arr::get($data, 'funnel_stage_id', $existing?->funnel_stage_id);
        $stage = $stageId ? FunnelStage::query()->find($stageId) : null;

        if ($stage?->is_lost) {
            $payload = array_merge(
                $payload,
                $this->lookups->resolveLeadLostReason(
                    Arr::get($data, 'lost_reason_id') ? (int) Arr::get($data, 'lost_reason_id') : null,
                    Arr::get($data, 'lost_reason_detail'),
                    required: true,
                ),
            );
        } else {
            $payload['lost_reason_id'] = null;
            $payload['lost_reason'] = null;
        }

        if ($existing === null && empty($payload['lead_source_id']) && ! empty($data['source_name'])) {
            $source = LeadSource::query()->firstOrCreate(
                ['slug' => Str::slug($data['source_name'])],
                ['name' => $data['source_name'], 'is_active' => true, 'sort_order' => 999],
            );
            $payload['lead_source_id'] = $source->id;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveFunnel(LeadLifecycle $lifecycle, array $data, ?Model $existing = null): ?Funnel
    {
        if ($funnelId = Arr::get($data, 'funnel_id')) {
            return Funnel::query()->find($funnelId);
        }

        if ($stageId = Arr::get($data, 'funnel_stage_id')) {
            $stage = FunnelStage::query()->find($stageId);

            if ($stage) {
                return $stage->funnel;
            }
        }

        if ($existing?->funnel_id) {
            return $existing->funnel;
        }

        $slug = match ($lifecycle) {
            LeadLifecycle::Recruit => 'recruiting-funnel',
            default => config('crm.default_funnel_slug', 'sales-funnel'),
        };

        return Funnel::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first()
            ?? Funnel::query()->where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveFunnelId(
        LeadLifecycle $lifecycle,
        array $data,
        ?Model $existing,
        ?Funnel $funnel,
    ): ?int {
        if ($funnel) {
            return $funnel->id;
        }

        return Arr::get($data, 'funnel_id', $existing?->funnel_id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveStageId(
        array $data,
        ?Model $existing,
        ?Funnel $funnel,
        ?FunnelStage $defaultStage,
    ): ?int {
        if ($stageId = Arr::get($data, 'funnel_stage_id')) {
            return (int) $stageId;
        }

        if ($existing?->funnel_stage_id && $funnel && $existing->funnel_id === $funnel->id) {
            return $existing->funnel_stage_id;
        }

        return $defaultStage?->id;
    }

    /**
     * @param  list<int|string>  $tagIds
     */
    private function syncTags(Model $contact, array $tagIds): void
    {
        $tagIds = collect($tagIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $contact->tags()->sync($tagIds);
    }
}
