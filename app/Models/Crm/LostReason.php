<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'requires_detail', 'is_active', 'sort_order'])]
class LostReason extends Model
{
    protected function casts(): array
    {
        return [
            'requires_detail' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function displayLabel(?string $detail = null): string
    {
        if ($this->requires_detail && filled($detail)) {
            return $this->name.': '.$detail;
        }

        return $this->name;
    }
}
