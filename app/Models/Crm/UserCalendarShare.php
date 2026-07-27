<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCalendarShare extends Model
{
    protected $fillable = [
        'user_calendar_id',
        'shared_with_user_id',
    ];

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(UserCalendar::class, 'user_calendar_id');
    }

    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }
}
