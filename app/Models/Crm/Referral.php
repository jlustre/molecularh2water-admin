<?php

namespace App\Models\Crm;

use App\Enums\Crm\ReferralStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Referral extends Model
{
    protected $fillable = [
        'referrer_type',
        'referrer_id',
        'referred_type',
        'referred_id',
        'user_id',
        'status',
        'reward_type',
        'reward_amount',
        'reward_notes',
        'rewarded_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReferralStatus::class,
            'reward_amount' => 'decimal:2',
            'rewarded_at' => 'datetime',
        ];
    }

    public function referrer(): MorphTo
    {
        return $this->morphTo();
    }

    public function referred(): MorphTo
    {
        return $this->morphTo();
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getReferrerLeadIdAttribute(): ?int
    {
        return $this->referrer_type === 'lead' ? $this->referrer_id : null;
    }

    public function getReferredLeadIdAttribute(): ?int
    {
        return $this->referred_type === 'lead' ? $this->referred_id : null;
    }
}
