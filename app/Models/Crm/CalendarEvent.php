<?php

namespace App\Models\Crm;

use App\Enums\Crm\CalendarEventPriority;
use App\Enums\Crm\CalendarEventStatus;
use App\Models\User;
use Database\Factories\CalendarEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'team_id',
    'business_line',
    'related_type',
    'related_id',
    'calendar_event_type_id',
    'user_calendar_id',
    'task_id',
    'title',
    'description',
    'start_at',
    'end_at',
    'is_all_day',
    'timezone',
    'location',
    'meeting_link',
    'status',
    'priority',
    'reminder_enabled',
    'completed_at',
    'cancelled_at',
    'completion_notes',
    'created_by',
    'updated_by',
    'metadata',
])]
class CalendarEvent extends Model
{
    /** @use HasFactory<CalendarEventFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'business_line' => \App\Enums\BusinessLine::class,
            'status' => CalendarEventStatus::class,
            'priority' => CalendarEventPriority::class,
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_all_day' => 'boolean',
            'reminder_enabled' => 'boolean',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function newFactory(): CalendarEventFactory
    {
        return CalendarEventFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CalendarEventType::class, 'calendar_event_type_id');
    }

    public function userCalendar(): BelongsTo
    {
        return $this->belongsTo(UserCalendar::class, 'user_calendar_id');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(CalendarEventAttendee::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(CalendarEventReminder::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CalendarEventNote::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function lead(): ?Lead
    {
        return $this->related instanceof Lead ? $this->related : null;
    }

    public function crmContact(): Lead|Prospect|Customer|Recruit|null
    {
        $related = $this->related;

        return $related instanceof Lead
            || $related instanceof Prospect
            || $related instanceof Customer
            || $related instanceof Recruit
            ? $related
            : null;
    }
}
