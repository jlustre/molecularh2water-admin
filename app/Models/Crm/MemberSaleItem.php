<?php

namespace App\Models\Crm;

use App\Enums\Crm\CrmProductKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberSaleItem extends Model
{
    protected $fillable = [
        'member_sale_id',
        'crm_product_id',
        'item_kind',
        'name',
        'sku',
        'quantity',
        'unit_price',
        'line_total',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'item_kind' => CrmProductKind::class,
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(MemberSale::class, 'member_sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CrmProduct::class, 'crm_product_id');
    }
}
