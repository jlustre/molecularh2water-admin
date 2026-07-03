<?php

namespace App\Models\Crm;

use App\Enums\Crm\QuotationStatus;
use App\Models\Crm\Concerns\BelongsToCrmContact;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use BelongsToCrmContact;

    protected $fillable = [
        'contact_type',
        'contact_id',
        'user_id',
        'consultation_id',
        'quote_number',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'total',
        'warranty_notes',
        'financing_notes',
        'payment_plan_notes',
        'notes',
        'valid_until',
        'presented_at',
        'viewed_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuotationStatus::class,
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'valid_until' => 'datetime',
            'presented_at' => 'datetime',
            'viewed_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    public function order(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Order::class);
    }
}
