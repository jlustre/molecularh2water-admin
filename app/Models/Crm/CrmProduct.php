<?php

namespace App\Models\Crm;

use App\Enums\Crm\CrmProductKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmProduct extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'kind',
        'crm_product_category_id',
        'category',
        'description',
        'unit_price',
        'inventory_quantity',
        'reorder_level',
        'reserved_quantity',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'kind' => CrmProductKind::class,
            'unit_price' => 'decimal:2',
            'inventory_quantity' => 'integer',
            'reorder_level' => 'integer',
            'reserved_quantity' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(CrmProductCategory::class, 'crm_product_category_id');
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function memberSaleItems(): HasMany
    {
        return $this->hasMany(MemberSaleItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    public function availableQuantity(): int
    {
        return max(0, (int) $this->inventory_quantity - (int) $this->reserved_quantity);
    }

    public function isLowStock(): bool
    {
        return $this->availableQuantity() <= (int) $this->reorder_level;
    }

    public function categoryLabel(): string
    {
        return $this->productCategory?->name
            ?? $this->category
            ?? '—';
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereRaw('(inventory_quantity - reserved_quantity) <= reorder_level');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeProducts(Builder $query): Builder
    {
        return $query->where('kind', CrmProductKind::Product->value);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeGifts(Builder $query): Builder
    {
        return $query->where('kind', CrmProductKind::Gift->value);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isReferenced(): bool
    {
        return $this->quotationItems()->exists()
            || $this->orderItems()->exists()
            || $this->memberSaleItems()->exists();
    }
}
