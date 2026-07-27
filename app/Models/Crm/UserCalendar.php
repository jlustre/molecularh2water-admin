<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserCalendar extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'color',
        'kind',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'user_calendar_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(UserCalendarShare::class);
    }

    public function sharedWithUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_calendar_shares', 'user_calendar_id', 'shared_with_user_id')
            ->withTimestamps();
    }

    public function visibilities(): HasMany
    {
        return $this->hasMany(UserCalendarVisibility::class);
    }

    public function isHolidayKind(): bool
    {
        return in_array($this->kind, ['us_holidays', 'ca_holidays'], true);
    }
}
