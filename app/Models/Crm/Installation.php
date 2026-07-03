<?php

namespace App\Models\Crm;

use App\Enums\Crm\InstallationStatus;
use App\Models\Crm\Concerns\BelongsToCrmContact;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installation extends Model
{
    use BelongsToCrmContact;

    protected $fillable = [
        'order_id',
        'delivery_id',
        'contact_type',
        'contact_id',
        'user_id',
        'status',
        'scheduled_at',
        'completed_at',
        'checklist',
        'photo_paths',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => InstallationStatus::class,
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'checklist' => 'array',
            'photo_paths' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
