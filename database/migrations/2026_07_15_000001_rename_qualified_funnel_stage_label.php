<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('funnel_stages')
            ->where('slug', 'qualified')
            ->where('name', 'Qualified')
            ->update([
                'name' => 'Qualified as Prospect',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('funnel_stages')
            ->where('slug', 'qualified')
            ->where('name', 'Qualified as Prospect')
            ->update([
                'name' => 'Qualified',
                'updated_at' => now(),
            ]);
    }
};
