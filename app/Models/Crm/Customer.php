<?php

namespace App\Models\Crm;

use App\Contracts\Crm\CrmContact;
use App\Enums\Crm\EngagementType;
use App\Enums\Crm\LeadTemperature;
use App\Models\Crm\Concerns\IsCrmContact;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'lifecycle_id',
    'business_line',
    'status',
    'engagement_type',
    'temperature',
    'score',
    'first_name',
    'last_name',
    'email',
    'phone',
    'address',
    'city',
    'state',
    'country',
    'company',
    'occupation',
    'spouse_name',
    'spouse_occupation',
    'best_time_to_contact',
    'lead_source_id',
    'funnel_id',
    'funnel_stage_id',
    'lost_reason_id',
    'assigned_user_id',
    'referred_by_type',
    'referred_by_id',
    'team_id',
    'interested_in',
    'message',
    'lost_reason',
    'last_contacted_at',
    'next_follow_up_at',
    'converted_at',
    'consent_given',
    'metadata',
])]
class Customer extends Model implements CrmContact
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, IsCrmContact, SoftDeletes;

    protected $table = 'customers';

    protected function casts(): array
    {
        return array_merge($this->crmContactCasts(), [
            'engagement_type' => EngagementType::class,
        ]);
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    public function scopeHot($query)
    {
        return $query->where('temperature', LeadTemperature::Hot->value);
    }

    public function scopeFollowUpDueToday($query)
    {
        return $query->whereDate('next_follow_up_at', '<=', now()->toDateString())
            ->whereNotNull('next_follow_up_at');
    }

    public function locationSummary(): string
    {
        return collect([
            $this->address,
            $this->city,
            $this->state,
        ])
            ->filter()
            ->implode(', ');
    }

    public function latestOrder(): ?Order
    {
        if ($this->relationLoaded('orders')) {
            return $this->orders->first();
        }

        return $this->orders()->with(['items.product'])->first();
    }

    public function productsSummaryLabel(): string
    {
        $orders = $this->relationLoaded('orders')
            ? $this->orders
            : $this->orders()->with(['items.product'])->get();

        $items = $orders->flatMap(fn (Order $order) => $order->items);

        if ($items->isEmpty()) {
            return '—';
        }

        return $items
            ->map(function (OrderItem $item) {
                $name = trim((string) ($item->product?->name ?: $item->description ?: 'Product'));
                $quantity = (int) $item->quantity;

                return $quantity > 1 ? "{$name} ×{$quantity}" : $name;
            })
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: ?string,
     *     phone: ?string,
     *     street_address: ?string,
     *     city: ?string,
     *     state: ?string,
     *     postal_code: ?string
     * }
     */
    public function toInstallerFormPayload(): array
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];

        return [
            'id' => $this->id,
            'name' => $this->fullName(),
            'email' => $this->email,
            'phone' => $this->phone,
            'street_address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => isset($metadata['postal_code']) ? (string) $metadata['postal_code'] : null,
        ];
    }
}
