<?php

namespace App\Models\Crm;

use App\Enums\Crm\DeliveryStatus;
use App\Models\Crm\Concerns\BelongsToCrmContact;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Delivery extends Model
{
    use BelongsToCrmContact;

    protected $fillable = [
        'order_id',
        'contact_type',
        'contact_id',
        'user_id',
        'status',
        'scheduled_at',
        'delivered_at',
        'address',
        'contact_name',
        'contact_phone',
        'checklist',
        'photo_paths',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'scheduled_at' => 'datetime',
            'delivered_at' => 'datetime',
            'checklist' => 'array',
            'photo_paths' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function installation(): HasOne
    {
        return $this->hasOne(Installation::class);
    }
}
