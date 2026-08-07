<?php

namespace App\Models;

use App\Enums\WebsiteFormSubmissionStatus;
use App\Enums\WebsiteFormType;
use App\Models\Crm\Prospect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
        'warranty_concern',
        'warranty_media',
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
            'warranty_media' => 'array',
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

    /**
     * @return list<array{path: string, original_name: ?string, mime_type: ?string, url: string}>
     */
    public function warrantyMediaItems(): array
    {
        return collect($this->warranty_media ?? [])
            ->filter(fn ($item) => is_array($item) && filled($item['path'] ?? null))
            ->map(fn (array $item) => [
                'path' => (string) $item['path'],
                'original_name' => filled($item['original_name'] ?? null) ? (string) $item['original_name'] : null,
                'mime_type' => filled($item['mime_type'] ?? null) ? (string) $item['mime_type'] : null,
                'url' => Storage::disk('public')->url((string) $item['path']),
            ])
            ->values()
            ->all();
    }
}
