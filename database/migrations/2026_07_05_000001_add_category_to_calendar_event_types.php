<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_event_types', function (Blueprint $table) {
            $table->string('category')->default('meeting')->after('slug');
            $table->index('category');
        });

        foreach (config('calendar.event_types', []) as $type) {
            DB::table('calendar_event_types')
                ->where('slug', $type['slug'])
                ->update([
                    'category' => $type['category'] ?? 'meeting',
                    'is_active' => true,
                ]);
        }

        DB::table('calendar_event_types')
            ->whereIn('slug', config('calendar.legacy_inactive_slugs', []))
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        Schema::table('calendar_event_types', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};
