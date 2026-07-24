<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('form_type', 64)->index();
            $table->string('status', 32)->default('new')->index();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 50)->nullable();
            $table->string('referrer_name')->nullable();
            $table->string('preferred_time')->nullable();
            $table->string('interested_in')->nullable();
            $table->text('message')->nullable();
            $table->string('source', 120)->nullable();
            $table->string('form_context', 120)->nullable()->index();
            $table->string('tracking_source', 120)->nullable();
            $table->string('page_url', 500)->nullable();
            $table->boolean('consent_given')->default(false);
            $table->text('admin_notes')->nullable();
            $table->foreignId('prospect_id')->nullable()->constrained('prospects')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_form_submissions');
    }
};
