<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultant_performance_dailies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('stat_date');
            $table->unsignedInteger('leads_added')->default(0);
            $table->unsignedInteger('phone_calls')->default(0);
            $table->unsignedInteger('invites')->default(0);
            $table->unsignedInteger('schedule_presentation')->default(0);
            $table->unsignedInteger('actual_demo')->default(0);
            $table->unsignedInteger('sales_closed')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'stat_date']);
            $table->index(['stat_date', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultant_performance_dailies');
    }
};
