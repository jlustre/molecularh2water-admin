<?php

namespace App\Models;

use App\Enums\Crm\EngagementType;
use App\Enums\DirectoryCustomerStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DirectoryCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'street_address',
        'city',
        'state',
        'postal_code',
        'status',
        'notes',
        'order_id',
        'assigned_user_id',
        'crm_customer_id',
        'engagement_type',
        'products_summary',
    ];

    protected function casts(): array
    {
        return [
            'status' => DirectoryCustomerStatus::class,
            'engagement_type' => EngagementType::class,
            'products_summary' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function crmCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'crm_customer_id');
    }

    public function installations(): HasMany
    {
        return $this->hasMany(InstallerInstallation::class);
    }

    public function locationSummary(): string
    {
        return collect([
            $this->street_address,
            $this->city,
            $this->state,
            $this->postal_code,
        ])
            ->filter()
            ->implode(', ');
    }

    public function productsSummaryLabel(): string
    {
        $items = collect($this->products_summary ?? []);

        if ($items->isEmpty()) {
            return '—';
        }

        return $items
            ->map(function (array $item) {
                $name = trim((string) ($item['name'] ?? 'Product'));
                $quantity = (int) ($item['quantity'] ?? 1);

                return $quantity > 1 ? "{$name} ×{$quantity}" : $name;
            })
            ->filter()
            ->implode(', ');
    }

    public function isArchived(): bool
    {
        return $this->status === DirectoryCustomerStatus::Archived;
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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'street_address' => $this->street_address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
        ];
    }
}
