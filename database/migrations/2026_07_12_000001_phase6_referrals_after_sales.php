<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('referred_by_lead_id')
                ->nullable()
                ->after('assigned_user_id')
                ->constrained('leads')
                ->nullOnDelete();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('referred_lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->string('reward_type')->nullable();
            $table->decimal('reward_amount', 12, 2)->nullable();
            $table->text('reward_notes')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('referred_lead_id');
            $table->index(['referrer_lead_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_lead_id');
        });
    }
};
