<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sponsor_id',
    'code',
    'label',
    'registered_user_id',
    'consumed_at',
    'expires_at',
])]
class RegistrationInvite extends Model
{
    protected function casts(): array
    {
        return [
            'consumed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    public function registeredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_user_id');
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isAvailable(): bool
    {
        return ! $this->isConsumed() && ! $this->isExpired();
    }

    /**
     * @param  Builder<RegistrationInvite>  $query
     * @return Builder<RegistrationInvite>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->whereNull('consumed_at')
            ->where(function (Builder $inner) {
                $inner->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
