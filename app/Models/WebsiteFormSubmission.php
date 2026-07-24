<?php

namespace App\Models;

use App\Enums\WebsiteFormSubmissionStatus;
use App\Enums\WebsiteFormType;
use App\Models\Crm\Prospect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteFormSubmission extends Model
{
    /** @use HasFactory<\Database\Factories\WebsiteFormSubmissionFactory> */
    use HasFactory;

    protected $fillable = [
        'form_type',
        'status',
        'name',
        'email',
        'phone',
        'referrer_name',
        'preferred_time',
        'interested_in',
        'message',
        'source',
        'form_context',
        'tracking_source',
        'page_url',
        'consent_given',
        'admin_notes',
        'prospect_id',
    ];

    protected function casts(): array
    {
        return [
            'form_type' => WebsiteFormType::class,
            'status' => WebsiteFormSubmissionStatus::class,
            'consent_given' => 'boolean',
        ];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function scopeOfType(Builder $query, WebsiteFormType $type): Builder
    {
        return $query->where('form_type', $type->value);
    }

    public function displayName(): string
    {
        return $this->name ?: ($this->email ?: ($this->phone ?: 'Untitled submission'));
    }
}
