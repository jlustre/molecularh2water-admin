<?php

namespace App\Support\Crm;

use Illuminate\Database\Schema\Blueprint;

class CrmContactSchema
{
    public static function columns(Blueprint $table, bool $withLifecycleId = true): void
    {
        if ($withLifecycleId) {
            $table->foreignId('lifecycle_id')->constrained('lifecycles')->restrictOnDelete();
        }

        $table->string('status')->default('new');
        $table->string('temperature')->default('cold');
        $table->unsignedSmallInteger('score')->default(0);
        $table->string('first_name');
        $table->string('last_name')->nullable();
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->string('address')->nullable();
        $table->string('city')->nullable();
        $table->string('state')->nullable();
        $table->string('country')->nullable();
        $table->string('company')->nullable();
        $table->string('occupation')->nullable();
        $table->string('spouse_name')->nullable();
        $table->string('spouse_occupation')->nullable();
        $table->string('best_time_to_contact')->nullable();
        $table->foreignId('lead_source_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('funnel_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('funnel_stage_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('lost_reason_id')->nullable()->constrained('lost_reasons')->nullOnDelete();
        $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->nullableMorphs('referred_by');
        $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
        $table->string('interested_in')->nullable();
        $table->text('message')->nullable();
        $table->string('lost_reason')->nullable();
        $table->timestamp('last_contacted_at')->nullable();
        $table->timestamp('next_follow_up_at')->nullable();
        $table->timestamp('converted_at')->nullable();
        $table->boolean('consent_given')->default(false);
        $table->json('metadata')->nullable();
        $table->timestamps();
        $table->softDeletes();

        $table->index(['status']);
        $table->index(['assigned_user_id', 'next_follow_up_at']);
        $table->index('temperature');
    }
}
