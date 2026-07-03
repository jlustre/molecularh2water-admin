<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\LandingPageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'funnel_id',
    'business_line',
    'title',
    'slug',
    'headline',
    'subheadline',
    'hero_media',
    'cta_label',
    'cta_url',
    'thank_you_headline',
    'thank_you_body',
    'tracking_source',
    'conversion_count',
    'is_published',
])]
class LandingPage extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'business_line' => \App\Enums\BusinessLine::class,
            'conversion_count' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function funnel(): BelongsTo
    {
        return $this->belongsTo(Funnel::class);
    }

    public function form(): HasOne
    {
        return $this->hasOne(LeadCaptureForm::class);
    }

    public function publicUrl(): string
    {
        return rtrim((string) config('frontend.url', config('app.url')), '/').'/lp/'.$this->slug;
    }

    protected static function newFactory(): LandingPageFactory
    {
        return LandingPageFactory::new();
    }
}
