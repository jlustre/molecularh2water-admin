<?php

namespace App\Models\Crm\Concerns;

use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadStatus;
use App\Enums\Crm\LeadTemperature;
use App\Models\Crm\Activity;
use App\Models\Crm\Appointment;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\Consultation;
use App\Models\Crm\Customer;
use App\Models\Crm\Demonstration;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\LeadSource;
use App\Models\Crm\Lifecycle;
use App\Models\Crm\LostReason;
use App\Models\Crm\Note;
use App\Models\Crm\Order;
use App\Models\Crm\PipelineStageHistory;
use App\Models\Crm\Prospect;
use App\Models\Crm\Quotation;
use App\Models\Crm\Recruit;
use App\Models\Crm\Referral;
use App\Models\Crm\Tag;
use App\Models\Crm\Task;
use App\Models\Crm\Team;
use App\Models\Crm\TimelineEvent;
use App\Models\User;
use App\Support\Crm\CrmScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait IsCrmContact
{
    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function lifecycleSlug(): LeadLifecycle
    {
        if ($this->relationLoaded('lifecycleRecord') && $this->lifecycleRecord) {
            return $this->lifecycleRecord->toLeadLifecycle();
        }

        if ($this->lifecycle_id) {
            $slug = Lifecycle::query()->find($this->lifecycle_id)?->slug ?? 'lead';

            return LeadLifecycle::from($slug);
        }

        return match (static::class) {
            Prospect::class => LeadLifecycle::Prospect,
            Customer::class => LeadLifecycle::Client,
            Recruit::class => LeadLifecycle::Recruit,
            default => LeadLifecycle::Lead,
        };
    }

    /**
     * @deprecated Use lifecycleSlug() — kept for backward compatibility during refactor.
     */
    public function getLifecycleAttribute(): LeadLifecycle
    {
        return $this->lifecycleSlug();
    }

    protected function crmContactCasts(): array
    {
        return [
            'business_line' => \App\Enums\BusinessLine::class,
            'status' => LeadStatus::class,
            'temperature' => LeadTemperature::class,
            'score' => 'integer',
            'last_contacted_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'converted_at' => 'datetime',
            'consent_given' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function lifecycleRecord(): BelongsTo
    {
        return $this->belongsTo(Lifecycle::class, 'lifecycle_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function funnel(): BelongsTo
    {
        return $this->belongsTo(Funnel::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(FunnelStage::class, 'funnel_stage_id');
    }

    public function lostReason(): BelongsTo
    {
        return $this->belongsTo(LostReason::class);
    }

    public function lostReasonLabel(): ?string
    {
        if ($this->lostReason) {
            $detail = $this->lostReason->requires_detail ? $this->lost_reason : null;

            return $this->lostReason->displayLabel($detail);
        }

        return $this->lost_reason;
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function owners(): MorphToMany
    {
        return $this->morphToMany(User::class, 'contact', 'crm_contact_user')->withTimestamps();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'contact', 'crm_contact_tag')->withTimestamps();
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'contact');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'contact');
    }

    public function appointments(): MorphMany
    {
        return $this->morphMany(Appointment::class, 'contact');
    }

    public function calendarEvents(): MorphMany
    {
        return $this->morphMany(CalendarEvent::class, 'related');
    }

    public function timelineEvents(): MorphMany
    {
        return $this->morphMany(TimelineEvent::class, 'contact');
    }

    public function pipelineStageHistories(): MorphMany
    {
        return $this->morphMany(PipelineStageHistory::class, 'contact')->latest();
    }

    public function demonstrations(): MorphMany
    {
        return $this->morphMany(Demonstration::class, 'contact')->orderByDesc('scheduled_at');
    }

    public function consultations(): MorphMany
    {
        return $this->morphMany(Consultation::class, 'contact')->orderByDesc('conducted_at');
    }

    public function quotations(): MorphMany
    {
        return $this->morphMany(Quotation::class, 'contact')->orderByDesc('created_at');
    }

    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'contact')->orderByDesc('created_at');
    }

    public function referredBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function referralsGiven(): MorphMany
    {
        return $this->morphMany(Referral::class, 'referrer')->latest();
    }

    public function referralRecord(): MorphOne
    {
        return $this->morphOne(Referral::class, 'referred');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable')->latest();
    }

    public function scopeVisibleToUser($query, ?User $user = null)
    {
        return CrmScope::contacts($query, $user);
    }

    public function isAccessibleBy(?User $user = null): bool
    {
        return CrmScope::contactIsAccessible($this, $user);
    }
}
