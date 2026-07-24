<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installation_questionnaires', function (Blueprint $table) {
            $table->json('sink_photos')->nullable()->after('sink_photo_original_name');
        });

        DB::table('installation_questionnaires')
            ->whereNotNull('sink_photo_path')
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('installation_questionnaires')
                    ->where('id', $row->id)
                    ->update([
                        'sink_photos' => json_encode([[
                            'path' => $row->sink_photo_path,
                            'original_name' => $row->sink_photo_original_name,
                        ]]),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('installation_questionnaires', function (Blueprint $table) {
            $table->dropColumn('sink_photos');
        });
    }
};
