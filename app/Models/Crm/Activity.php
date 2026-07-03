<?php

namespace App\Models\Crm;

use App\Models\Crm\Concerns\BelongsToCrmContact;
use App\Models\User;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'activity_type_id',
    'user_id',
    'contact_type',
    'contact_id',
    'business_line',
    'title',
    'description',
    'outcome',
    'next_action',
    'scheduled_at',
    'completed_at',
    'duration_minutes',
    'metadata',
])]
class Activity extends Model
{
    use BelongsToCrmContact;
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'business_line' => \App\Enums\BusinessLine::class,
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): ActivityFactory
    {
        return ActivityFactory::new();
    }
}
