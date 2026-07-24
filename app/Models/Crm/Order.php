<?php

namespace App\Models\Crm;

use App\Enums\Crm\OrderStatus;
use App\Enums\Crm\PaymentStatus;
use App\Models\Crm\Concerns\BelongsToCrmContact;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use BelongsToCrmContact;

    protected $fillable = [
        'contact_type',
        'contact_id',
        'quotation_id',
        'user_id',
        'demo_consultant_id',
        'order_number',
        'status',
        'payment_status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'total',
        'amount_paid',
        'payment_method',
        'payment_reference',
        'notes',
        'submitted_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'submitted_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function demoConsultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demo_consultant_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('sort_order');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class)->orderByDesc('scheduled_at');
    }

    public function installations(): HasMany
    {
        return $this->hasMany(Installation::class)->orderByDesc('scheduled_at');
    }

    public function latestDelivery(): HasOne
    {
        return $this->hasOne(Delivery::class)->latestOfMany();
    }

    public function latestInstallation(): HasOne
    {
        return $this->hasOne(Installation::class)->latestOfMany();
    }
}
