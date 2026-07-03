<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['funnel_id', 'name', 'slug', 'color', 'sort_order', 'is_won', 'is_lost'])]
class FunnelStage extends Model
{
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_won' => 'boolean',
            'is_lost' => 'boolean',
        ];
    }

    public function funnel(): BelongsTo
    {
        return $this->belongsTo(Funnel::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'funnel_stage_id');
    }

    /**
     * @return array{border: string, bg: string, header: string, dot: string}
     */
    public function panelClasses(): array
    {
        return match ($this->color) {
            'cyan' => [
                'border' => 'border-cyan-200',
                'bg' => 'bg-cyan-50/80',
                'header' => 'bg-cyan-100/60 text-cyan-900',
                'dot' => 'bg-cyan-500',
            ],
            'blue' => [
                'border' => 'border-blue-200',
                'bg' => 'bg-blue-50/80',
                'header' => 'bg-blue-100/60 text-blue-900',
                'dot' => 'bg-blue-500',
            ],
            'indigo' => [
                'border' => 'border-indigo-200',
                'bg' => 'bg-indigo-50/80',
                'header' => 'bg-indigo-100/60 text-indigo-900',
                'dot' => 'bg-indigo-500',
            ],
            'amber' => [
                'border' => 'border-amber-200',
                'bg' => 'bg-amber-50/80',
                'header' => 'bg-amber-100/60 text-amber-900',
                'dot' => 'bg-amber-500',
            ],
            'orange' => [
                'border' => 'border-orange-200',
                'bg' => 'bg-orange-50/80',
                'header' => 'bg-orange-100/60 text-orange-900',
                'dot' => 'bg-orange-500',
            ],
            'emerald' => [
                'border' => 'border-emerald-200',
                'bg' => 'bg-emerald-50/80',
                'header' => 'bg-emerald-100/60 text-emerald-900',
                'dot' => 'bg-emerald-500',
            ],
            'rose' => [
                'border' => 'border-rose-200',
                'bg' => 'bg-rose-50/80',
                'header' => 'bg-rose-100/60 text-rose-900',
                'dot' => 'bg-rose-500',
            ],
            default => [
                'border' => 'border-slate-200',
                'bg' => 'bg-slate-50/80',
                'header' => 'bg-slate-100/60 text-slate-900',
                'dot' => 'bg-slate-500',
            ],
        };
    }
}
