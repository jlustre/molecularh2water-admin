<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $stageRenames = [
            'appointment-scheduled' => ['slug' => 'show-booked', 'name' => 'Show Booked'],
            'presentation-done' => ['slug' => 'show-completed', 'name' => 'Show Completed'],
            'application-started' => ['slug' => 'order-started', 'name' => 'Order Started'],
        ];

        foreach ($stageRenames as $oldSlug => $next) {
            DB::table('funnel_stages')
                ->where('slug', $oldSlug)
                ->update([
                    'slug' => $next['slug'],
                    'name' => $next['name'],
                    'updated_at' => now(),
                ]);
        }

        foreach (config('crm.activity_types', []) as $index => $type) {
            DB::table('activity_types')->updateOrInsert(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'icon' => $type['icon'] ?? null,
                    'is_active' => true,
                    'sort_order' => $type['sort_order'] ?? ($index + 1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        DB::table('activity_types')
            ->whereIn('slug', config('crm.legacy_inactive_activity_slugs', []))
            ->update(['is_active' => false, 'updated_at' => now()]);

        foreach (['Cooking Show', 'Water Awareness Show'] as $index => $name) {
            DB::table('lead_sources')->updateOrInsert(
                ['slug' => str($name)->slug()->toString()],
                [
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => 20 + $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        DB::table('lead_sources')
            ->where('slug', 'event')
            ->update(['is_active' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        $stageRenames = [
            'show-booked' => ['slug' => 'appointment-scheduled', 'name' => 'Appointment Scheduled'],
            'show-completed' => ['slug' => 'presentation-done', 'name' => 'Presentation Done'],
            'order-started' => ['slug' => 'application-started', 'name' => 'Application Started'],
        ];

        foreach ($stageRenames as $oldSlug => $next) {
            DB::table('funnel_stages')
                ->where('slug', $oldSlug)
                ->update([
                    'slug' => $next['slug'],
                    'name' => $next['name'],
                    'updated_at' => now(),
                ]);
        }
    }
};
