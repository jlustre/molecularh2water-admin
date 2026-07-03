<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'color'])]
class Tag extends Model
{
    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class, 'lead_tag')->withTimestamps();
    }
}
