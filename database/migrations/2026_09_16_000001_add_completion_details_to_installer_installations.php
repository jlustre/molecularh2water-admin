<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installer_installations', function (Blueprint $table) {
            $table->string('completion_installer_name')->nullable()->after('completed_at');
            $table->date('installation_date')->nullable()->after('completion_installer_name');
            $table->text('completion_address')->nullable()->after('installation_date');
            $table->string('installed_product')->nullable()->after('completion_address');
            $table->text('completion_details')->nullable()->after('installed_product');
            $table->text('installer_completion_notes')->nullable()->after('completion_details');
        });
    }

    public function down(): void
    {
        Schema::table('installer_installations', function (Blueprint $table) {
            $table->dropColumn([
                'completion_installer_name',
                'installation_date',
                'completion_address',
                'installed_product',
                'completion_details',
                'installer_completion_notes',
            ]);
        });
    }
};