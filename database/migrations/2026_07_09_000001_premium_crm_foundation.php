<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_stage_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('funnel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_stage_id')->nullable()->constrained('funnel_stages')->nullOnDelete();
            $table->foreignId('to_stage_id')->constrained('funnel_stages')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('duration_in_previous_stage_seconds')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
            $table->index(['to_stage_id', 'created_at']);
        });

        Schema::create('demonstrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('calendar_event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('home');
            $table->string('status')->default('scheduled');
            $table->string('outcome')->nullable();
            $table->timestamp('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('venue')->nullable();
            $table->string('host')->nullable();
            $table->unsignedSmallInteger('guests_count')->nullable();
            $table->boolean('attended')->nullable();
            $table->text('notes')->nullable();
            $table->json('materials')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'scheduled_at']);
            $table->index(['user_id', 'scheduled_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demonstrations');
        Schema::dropIfExists('pipeline_stage_histories');
    }
};
