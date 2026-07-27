<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('member_sales') && ! Schema::hasColumn('member_sales', 'deleted_at')) {
            Schema::table('member_sales', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('member_sales') && Schema::hasColumn('member_sales', 'deleted_at')) {
            Schema::table('member_sales', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
