<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('followup_sequence_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('followup_sequence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('trigger_event');
            $table->string('status')->default('active');
            $table->unsignedInteger('current_step_order')->default(0);
            $table->timestamp('next_step_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'status']);
            $table->index(['followup_sequence_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('followup_sequence_enrollments');
    }
};
