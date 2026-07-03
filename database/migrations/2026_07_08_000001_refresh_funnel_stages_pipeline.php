<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $funnelId = DB::table('funnels')
            ->where('slug', config('crm.default_funnel_slug', 'sales-funnel'))
            ->value('id');

        if (! $funnelId) {
            return;
        }

        $now = now();

        foreach (config('crm.default_stages', []) as $stage) {
            DB::table('funnel_stages')->updateOrInsert(
                [
                    'funnel_id' => $funnelId,
                    'slug' => $stage['slug'],
                ],
                [
                    'name' => $stage['name'],
                    'color' => $stage['color'] ?? 'slate',
                    'sort_order' => $stage['sort_order'],
                    'is_won' => (bool) ($stage['is_won'] ?? false),
                    'is_lost' => (bool) ($stage['is_lost'] ?? false),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        foreach (config('crm.legacy_funnel_stage_slug_map', []) as $oldSlug => $newSlug) {
            $oldStageId = DB::table('funnel_stages')
                ->where('funnel_id', $funnelId)
                ->where('slug', $oldSlug)
                ->value('id');

            $newStageId = DB::table('funnel_stages')
                ->where('funnel_id', $funnelId)
                ->where('slug', $newSlug)
                ->value('id');

            if ($oldStageId && $newStageId && $oldStageId !== $newStageId) {
                DB::table('leads')
                    ->where('funnel_stage_id', $oldStageId)
                    ->update(['funnel_stage_id' => $newStageId, 'updated_at' => $now]);
            }
        }

        $validSlugs = collect(config('crm.default_stages', []))->pluck('slug')->all();

        DB::table('funnel_stages')
            ->where('funnel_id', $funnelId)
            ->whereNotIn('slug', $validSlugs)
            ->orderBy('id')
            ->get()
            ->each(function (object $stage) use ($now) {
                $hasLeads = DB::table('leads')->where('funnel_stage_id', $stage->id)->exists();

                if (! $hasLeads) {
                    DB::table('funnel_stages')->where('id', $stage->id)->delete();
                }
            });
    }

    public function down(): void
    {
        // Stage rollback is handled by re-seeding from config history if needed.
    }
};
