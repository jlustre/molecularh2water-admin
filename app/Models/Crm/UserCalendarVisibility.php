<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCalendarVisibility extends Model
{
    protected $fillable = [
        'user_id',
        'user_calendar_id',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(UserCalendar::class, 'user_calendar_id');
    }
}
