<?php

namespace App\Models\Crm;

use App\Enums\Crm\DemonstrationOutcome;
use App\Enums\Crm\DemonstrationStatus;
use App\Enums\Crm\DemonstrationType;
use App\Models\Crm\Concerns\BelongsToCrmContact;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Demonstration extends Model
{
    use BelongsToCrmContact;

    protected $fillable = [
        'contact_type',
        'contact_id',
        'user_id',
        'calendar_event_id',
        'type',
        'status',
        'outcome',
        'scheduled_at',
        'duration_minutes',
        'venue',
        'host',
        'guests_count',
        'attended',
        'notes',
        'materials',
    ];

    protected function casts(): array
    {
        return [
            'type' => DemonstrationType::class,
            'status' => DemonstrationStatus::class,
            'outcome' => DemonstrationOutcome::class,
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'guests_count' => 'integer',
            'attended' => 'boolean',
            'materials' => 'array',
        ];
    }

    public function demonstrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }
}
