<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_invites', function (Blueprint $table) {
            $table->foreignId('created_by')
                ->nullable()
                ->after('sponsor_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('registration_invites')
            ->whereNull('created_by')
            ->update(['created_by' => DB::raw('sponsor_id')]);
    }

    public function down(): void
    {
        Schema::table('registration_invites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
