<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_sales', function (Blueprint $table) {
            $table->foreignId('demo_consultant_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('demo_consultant_id');
        });

        Schema::table('demonstrations', function (Blueprint $table) {
            $table->foreignId('credited_consultant_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('credited_consultant_id');
        });
    }

    public function down(): void
    {
        Schema::table('demonstrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credited_consultant_id');
        });

        Schema::table('member_sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('demo_consultant_id');
        });
    }
};
