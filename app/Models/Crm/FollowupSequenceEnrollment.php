<?php

namespace App\Models\Crm;

use App\Models\Crm\Concerns\BelongsToCrmContact;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowupSequenceEnrollment extends Model
{
    use BelongsToCrmContact;

    protected $fillable = [
        'followup_sequence_id',
        'contact_type',
        'contact_id',
        'user_id',
        'trigger_event',
        'status',
        'current_step_order',
        'next_step_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'current_step_order' => 'integer',
            'next_step_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(FollowupSequence::class, 'followup_sequence_id');
    }

    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
