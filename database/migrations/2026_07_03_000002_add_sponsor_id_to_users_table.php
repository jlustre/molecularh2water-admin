<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'sponsor_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $column = $table->foreignId('sponsor_id')->nullable();

            if (Schema::hasColumn('users', 'avatar_path')) {
                $column->after('avatar_path');
            }

            $column->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'sponsor_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sponsor_id');
        });
    }
};
