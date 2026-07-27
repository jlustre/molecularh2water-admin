<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installer_installations', function (Blueprint $table) {
            if (! Schema::hasColumn('installer_installations', 'crm_customer_id')) {
                $table->foreignId('crm_customer_id')
                    ->nullable()
                    ->after('installer_id')
                    ->constrained('customers')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('installer_installations', function (Blueprint $table) {
            if (Schema::hasColumn('installer_installations', 'crm_customer_id')) {
                $table->dropConstrainedForeignId('crm_customer_id');
            }
        });
    }
};
