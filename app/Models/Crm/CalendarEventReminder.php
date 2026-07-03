<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'calendar_event_id',
    'channel',
    'minutes_before',
    'remind_at',
    'sent_at',
])]
class CalendarEventReminder extends Model
{
    protected function casts(): array
    {
        return [
            'minutes_before' => 'integer',
            'remind_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }
}
