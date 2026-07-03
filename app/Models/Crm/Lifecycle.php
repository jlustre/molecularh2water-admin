<?php

namespace App\Models\Crm;

use App\Enums\Crm\LeadLifecycle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Lifecycle extends Model
{
    protected $fillable = [
        'slug',
        'label',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function toLeadLifecycle(): LeadLifecycle
    {
        return LeadLifecycle::from($this->slug);
    }

    public static function idFor(LeadLifecycle|string $lifecycle): int
    {
        $slug = $lifecycle instanceof LeadLifecycle ? $lifecycle->value : $lifecycle;

        return (int) self::cachedIdsBySlug()[$slug];
    }

    public static function forLeadLifecycle(LeadLifecycle|string $lifecycle): self
    {
        $slug = $lifecycle instanceof LeadLifecycle ? $lifecycle->value : $lifecycle;

        return self::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * @return array<string, int>
     */
    public static function cachedIdsBySlug(): array
    {
        return Cache::rememberForever('crm.lifecycles.ids_by_slug', function () {
            return self::query()
                ->orderBy('sort_order')
                ->pluck('id', 'slug')
                ->all();
        });
    }

    public static function flushCache(): void
    {
        Cache::forget('crm.lifecycles.ids_by_slug');
    }
}
