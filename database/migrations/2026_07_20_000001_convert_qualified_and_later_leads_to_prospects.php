<?php

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\User;
use App\Services\Crm\LeadService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $conversionSlug = config('crm.prospect_conversion_stage_slug', 'qualified');
        $actor = User::query()->orderBy('id')->first();

        if (! $actor) {
            return;
        }

        $leadService = app(LeadService::class);
        $leadIds = Lead::query()->whereNotNull('funnel_stage_id')->orderBy('id')->pluck('id');

        foreach ($leadIds as $leadId) {
            $lead = Lead::query()->with('stage')->find($leadId);

            if (! $lead?->stage || $lead->stage->is_won) {
                continue;
            }

            $conversionStage = FunnelStage::query()
                ->where('funnel_id', $lead->stage->funnel_id)
                ->where('slug', $conversionSlug)
                ->first();

            if (! $conversionStage || $lead->stage->sort_order < $conversionStage->sort_order) {
                continue;
            }

            $leadService->convertLifecycle($lead, LeadLifecycle::Prospect, $actor);
        }
    }

    public function down(): void
    {
        // Irreversible data correction — post-qualified contacts should remain Prospects.
    }
};
