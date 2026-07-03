<?php

use App\Support\Crm\CrmContactSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BREAKING CHANGE: Creates prospects, customers, and recruits tables
 * mirroring the leads schema (with lifecycle_id FK, no lifecycle string column).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['prospects', 'customers', 'recruits'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('business_line')->default('h2s');
                $table->index('business_line');
                CrmContactSchema::columns($table);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recruits');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('prospects');
    }
};
