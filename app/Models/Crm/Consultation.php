<?php

namespace App\Models\Crm;

use App\Models\Crm\Concerns\BelongsToCrmContact;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultation extends Model
{
    use BelongsToCrmContact;

    protected $fillable = [
        'contact_type',
        'contact_id',
        'user_id',
        'customer_needs',
        'product_recommendation',
        'family_size',
        'water_consumption',
        'budget',
        'financing_option',
        'health_goals',
        'objections',
        'competitor_comparison',
        'final_recommendation',
        'conducted_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'family_size' => 'integer',
            'budget' => 'decimal:2',
            'conducted_at' => 'datetime',
        ];
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }
}
