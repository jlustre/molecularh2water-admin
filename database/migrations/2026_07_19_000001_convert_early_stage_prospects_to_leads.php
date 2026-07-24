<?php

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Prospect;
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
        $prospectIds = Prospect::query()->orderBy('id')->pluck('id');

        foreach ($prospectIds as $prospectId) {
            $prospect = Prospect::query()->with('stage')->find($prospectId);

            if (! $prospect?->stage) {
                continue;
            }

            $conversionStage = FunnelStage::query()
                ->where('funnel_id', $prospect->stage->funnel_id)
                ->where('slug', $conversionSlug)
                ->first();

            if (! $conversionStage || $prospect->stage->sort_order >= $conversionStage->sort_order) {
                continue;
            }

            $leadService->convertLifecycle($prospect, LeadLifecycle::Lead, $actor);
        }
    }

    public function down(): void
    {
        // Irreversible data correction — early-stage contacts should remain Leads.
    }
};
