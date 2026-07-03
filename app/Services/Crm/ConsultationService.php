<?php

namespace App\Services\Crm;

use App\Models\Crm\Consultation;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use Illuminate\Support\Arr;

class ConsultationService
{
    public function __construct(
        private readonly TimelineService $timeline,
        private readonly FunnelService $funnels,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(Lead|Prospect|Customer|Recruit $lead, array $data, User $user, bool $moveStage = true): Consultation
    {
        $consultation = Consultation::query()->create([
            'contact_type' => $lead->getMorphClass(),
            'contact_id' => $lead->id,
            'user_id' => Arr::get($data, 'user_id', $user->id),
            'customer_needs' => Arr::get($data, 'customer_needs'),
            'product_recommendation' => Arr::get($data, 'product_recommendation'),
            'family_size' => Arr::get($data, 'family_size'),
            'water_consumption' => Arr::get($data, 'water_consumption'),
            'budget' => Arr::get($data, 'budget'),
            'financing_option' => Arr::get($data, 'financing_option'),
            'health_goals' => Arr::get($data, 'health_goals'),
            'objections' => Arr::get($data, 'objections'),
            'competitor_comparison' => Arr::get($data, 'competitor_comparison'),
            'final_recommendation' => Arr::get($data, 'final_recommendation'),
            'conducted_at' => Arr::get($data, 'conducted_at', now()),
            'notes' => Arr::get($data, 'notes'),
        ]);

        $this->timeline->log(
            $lead,
            'consultation_recorded',
            'Consultation recorded',
            $consultation->final_recommendation ?? $consultation->product_recommendation,
            ['consultation_id' => $consultation->id],
            $user,
        );

        if ($moveStage) {
            $this->funnels->moveLeadToStageSlug($lead, 'consultation', $user);
        }

        return $consultation->fresh(['consultant']);
    }
}
