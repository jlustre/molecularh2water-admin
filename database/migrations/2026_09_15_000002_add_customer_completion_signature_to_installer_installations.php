<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installer_installations', function (Blueprint $table) {
            $table->string('customer_signature_path')->nullable()->after('completed_at');
            $table->string('customer_signature_name')->nullable()->after('customer_signature_path');
            $table->timestamp('customer_signed_at')->nullable()->after('customer_signature_name');
        });
    }

    public function down(): void
    {
        Schema::table('installer_installations', function (Blueprint $table) {
            $table->dropColumn([
                'customer_signature_path',
                'customer_signature_name',
                'customer_signed_at',
            ]);
        });
    }
};