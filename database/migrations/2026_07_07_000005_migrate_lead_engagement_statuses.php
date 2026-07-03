<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (config('crm.legacy_lead_status_map', []) as $from => $to) {
            DB::table('leads')->where('status', $from)->update(['status' => $to]);
        }
    }

    public function down(): void
    {
        $reverse = array_flip(config('crm.legacy_lead_status_map', []));

        foreach ($reverse as $from => $to) {
            DB::table('leads')->where('status', $from)->update(['status' => $to]);
        }
    }
};
