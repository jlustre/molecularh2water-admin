<?php

namespace App\Services\Crm;

use App\Enums\Crm\DemonstrationOutcome;
use App\Enums\Crm\DemonstrationStatus;
use App\Enums\Crm\DemonstrationType;
use App\Models\Crm\Customer;
use App\Models\Crm\Demonstration;
use App\Models\Crm\Lead;
use App\Models\Crm\LostReason;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use Illuminate\Support\Arr;

class DemonstrationService
{
    public function __construct(
        private readonly TimelineService $timeline,
        private readonly FunnelService $funnels,
        private readonly CrmAutomationService $automation,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function schedule(Lead|Prospect|Customer|Recruit $lead, array $data, User $user): Demonstration
    {
        $demo = Demonstration::query()->create([
            'contact_type' => $lead->getMorphClass(),
            'contact_id' => $lead->id,
            'user_id' => Arr::get($data, 'user_id', $user->id),
            'type' => Arr::get($data, 'type', DemonstrationType::Home->value),
            'status' => Arr::get($data, 'status', DemonstrationStatus::Scheduled->value),
            'scheduled_at' => Arr::get($data, 'scheduled_at', now()->addDay()),
            'duration_minutes' => (int) Arr::get($data, 'duration_minutes', 60),
            'venue' => Arr::get($data, 'venue'),
            'host' => Arr::get($data, 'host'),
            'guests_count' => Arr::get($data, 'guests_count'),
            'notes' => Arr::get($data, 'notes'),
            'materials' => Arr::get($data, 'materials'),
        ]);

        $this->timeline->log(
            $lead,
            'demonstration_scheduled',
            'Demo scheduled',
            $demo->type->label().' on '.$demo->scheduled_at->format('M j, Y g:i A'),
            ['demonstration_id' => $demo->id, 'type' => $demo->type->value],
            $user,
        );

        $this->automation->dispatch('demonstration.scheduled', [
            'lead_id' => $lead->id,
            'demonstration_id' => $demo->id,
        ], $user);

        return $demo->fresh(['demonstrator']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Demonstration $demo, array $data, User $user): Demonstration
    {
        $demo->update(Arr::only($data, [
            'type', 'status', 'outcome', 'scheduled_at', 'duration_minutes',
            'venue', 'host', 'guests_count', 'attended', 'notes', 'materials', 'user_id',
        ]));

        if ($demo->wasChanged('status')) {
            $this->timeline->log(
                $demo->contact,
                'demonstration_updated',
                'Demo status: '.$demo->status->label(),
                $demo->notes,
                ['demonstration_id' => $demo->id, 'status' => $demo->status->value],
                $user,
            );
        }

        if ($demo->status === DemonstrationStatus::Completed) {
            $this->applyCompletionStageMove($demo->fresh(['contact']), $user);
        }

        return $demo->fresh(['demonstrator']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function complete(Demonstration $demo, array $data, User $user): Demonstration
    {
        $outcome = Arr::get($data, 'outcome');

        $demo->update([
            'status' => DemonstrationStatus::Completed,
            'outcome' => $outcome,
            'attended' => (bool) Arr::get($data, 'attended', true),
            'notes' => Arr::get($data, 'notes', $demo->notes),
        ]);

        $this->timeline->log(
            $demo->contact,
            'demonstration_completed',
            'Demo completed',
            $outcome ? DemonstrationOutcome::from($outcome)->label() : null,
            [
                'demonstration_id' => $demo->id,
                'outcome' => $outcome,
            ],
            $user,
        );

        $this->applyCompletionStageMove($demo->fresh(['contact']), $user);

        $this->automation->dispatch('demonstration.completed', [
            'lead_id' => $demo->contact_id,
            'demonstration_id' => $demo->id,
        ], $user);

        return $demo->fresh(['demonstrator']);
    }

    private function applyCompletionStageMove(Demonstration $demo, User $user): void
    {
        $lead = $demo->contact;

        if ($demo->outcome) {
            $slug = config('crm.demo_outcome_stage_map.'.$demo->outcome->value);

            if ($slug) {
                $lostReasonId = null;

                if ($demo->outcome === DemonstrationOutcome::NotInterested) {
                    $lostReasonId = LostReason::query()
                        ->where('slug', 'not-interested')
                        ->value('id');
                }

                $this->funnels->moveLeadToStageSlug($lead, $slug, $user, $lostReasonId);

                return;
            }
        }

        $this->funnels->moveLeadToStageSlug($lead, 'demo-completed', $user);
    }
}
