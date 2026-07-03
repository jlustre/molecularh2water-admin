<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowupSequenceStep extends Model
{
    protected $fillable = [
        'followup_sequence_id',
        'channel',
        'template_id',
        'delay_minutes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'delay_minutes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(FollowupSequence::class, 'followup_sequence_id');
    }
}
