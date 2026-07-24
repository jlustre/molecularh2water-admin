<?php

namespace App\Models\Crm;

use App\Enums\Crm\StockMovementType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'crm_product_id',
        'type',
        'quantity_delta',
        'quantity_before',
        'quantity_after',
        'reserved_before',
        'reserved_after',
        'reason',
        'notes',
        'reference_type',
        'reference_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity_delta' => 'integer',
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'reserved_before' => 'integer',
            'reserved_after' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CrmProduct::class, 'crm_product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
