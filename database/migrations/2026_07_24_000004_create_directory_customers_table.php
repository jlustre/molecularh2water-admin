<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directory_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('street_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('postal_code')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('status');
            $table->index('email');
        });

        Schema::table('installer_installations', function (Blueprint $table) {
            $table->foreignId('directory_customer_id')
                ->nullable()
                ->after('installer_id')
                ->constrained('directory_customers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('installer_installations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('directory_customer_id');
        });

        Schema::dropIfExists('directory_customers');
    }
};
