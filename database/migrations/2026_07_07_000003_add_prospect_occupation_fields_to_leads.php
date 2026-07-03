<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('occupation')->nullable()->after('company');
            $table->string('spouse_name')->nullable()->after('occupation');
            $table->string('spouse_occupation')->nullable()->after('spouse_name');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['occupation', 'spouse_name', 'spouse_occupation']);
        });
    }
};
