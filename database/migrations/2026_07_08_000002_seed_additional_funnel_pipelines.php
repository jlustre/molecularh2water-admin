<?php

use App\Models\Crm\Funnel;
use App\Services\Crm\FunnelService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $funnelService = app(FunnelService::class);
        $now = now();

        foreach (config('crm.additional_pipelines', []) as $pipeline) {
            $funnelId = DB::table('funnels')->where('slug', $pipeline['slug'])->value('id');

            if (! $funnelId) {
                $funnelId = DB::table('funnels')->insertGetId([
                    'slug' => $pipeline['slug'],
                    'name' => $pipeline['name'],
                    'description' => $pipeline['description'] ?? null,
                    'is_default' => false,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('funnels')->where('id', $funnelId)->update([
                    'name' => $pipeline['name'],
                    'description' => $pipeline['description'] ?? null,
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
            }

            $funnel = Funnel::query()->find($funnelId);

            if ($funnel) {
                $funnelService->seedStages($funnel, $pipeline['stages'] ?? []);
            }
        }
    }

    public function down(): void
    {
        // Additional pipeline stages are managed via config re-seeding if needed.
    }
};
