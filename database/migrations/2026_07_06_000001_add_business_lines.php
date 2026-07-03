<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'leads',
        'calendar_events',
        'tasks',
        'activities',
        'appointments',
        'landing_pages',
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('business_lines')->nullable()->after('sponsor_id');
        });

        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->string('business_line')->default('h2s')->after('id');
                $table->index('business_line');
            });
        }

        DB::table('users')
            ->whereNull('business_lines')
            ->update(['business_lines' => json_encode(['h2s'])]);

        DB::table('users')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('role_user')
                    ->join('roles', 'roles.id', '=', 'role_user.role_id')
                    ->whereColumn('role_user.user_id', 'users.id')
                    ->whereIn('roles.slug', ['super-admin', 'admin', 'manager']);
            })
            ->update(['business_lines' => json_encode(['hcc', 'h2s'])]);

        if (Schema::hasTable('calendar_events') && Schema::hasTable('calendar_event_types')) {
            $cookingTypeIds = DB::table('calendar_event_types')
                ->where('slug', 'cooking-show')
                ->pluck('id');

            if ($cookingTypeIds->isNotEmpty()) {
                DB::table('calendar_events')
                    ->whereIn('calendar_event_type_id', $cookingTypeIds)
                    ->update(['business_line' => 'hcc']);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'business_line')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['business_line']);
                $table->dropColumn('business_line');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('business_lines');
        });
    }
};
