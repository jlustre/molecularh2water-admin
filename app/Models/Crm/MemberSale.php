<?php

namespace App\Models\Crm;

use App\Enums\BusinessLine;
use App\Enums\Crm\MemberSaleStatus;
use App\Models\Crm\Concerns\BelongsToCrmContact;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberSale extends Model
{
    use BelongsToCrmContact;

    protected $fillable = [
        'user_id',
        'demo_consultant_id',
        'contact_type',
        'contact_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'status',
        'business_line',
        'notes',
        'application_started_at',
        'financing_at',
        'approved_at',
        'delivered_at',
        'completed_at',
        'subtotal',
        'gifts_total',
        'total',
        'inventory_reserved',
        'inventory_deducted',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => MemberSaleStatus::class,
            'business_line' => BusinessLine::class,
            'application_started_at' => 'datetime',
            'financing_at' => 'datetime',
            'approved_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'gifts_total' => 'decimal:2',
            'total' => 'decimal:2',
            'inventory_reserved' => 'boolean',
            'inventory_deducted' => 'boolean',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Primary consultant credited for the sale (often the learning consultant). */
    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Consultant who ran the demo (often assisting a learning consultant). */
    public function demoConsultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demo_consultant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creditLabels(): string
    {
        $consultant = $this->consultant?->name;
        $demo = $this->demoConsultant?->name;

        if ($consultant && $demo && (int) $this->user_id !== (int) $this->demo_consultant_id) {
            return $consultant.' · Demo: '.$demo;
        }

        return $consultant ?: ($demo ?: '—');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MemberSaleItem::class)->orderBy('sort_order');
    }

    public function displayCustomerName(): string
    {
        if (filled($this->customer_name)) {
            return $this->customer_name;
        }

        $contact = $this->contact;

        if ($contact && method_exists($contact, 'fullName')) {
            return $contact->fullName() ?: 'Unnamed contact';
        }

        return '—';
    }
}
