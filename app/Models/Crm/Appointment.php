<?php

namespace App\Models\Crm;

use App\Enums\Crm\AppointmentStatus;
use App\Models\Crm\Concerns\BelongsToCrmContact;
use App\Models\User;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'contact_type',
    'contact_id',
    'user_id',
    'business_line',
    'title',
    'meeting_type',
    'location',
    'zoom_link',
    'status',
    'starts_at',
    'ends_at',
    'reminder_notes',
])]
class Appointment extends Model
{
    use BelongsToCrmContact;
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'business_line' => \App\Enums\BusinessLine::class,
            'status' => AppointmentStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): AppointmentFactory
    {
        return AppointmentFactory::new();
    }
}
