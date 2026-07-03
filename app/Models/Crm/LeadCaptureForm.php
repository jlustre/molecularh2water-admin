<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['landing_page_id', 'fields', 'settings'])]
class LeadCaptureForm extends Model
{
    protected $table = 'lead_forms';

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'settings' => 'array',
        ];
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }
}
