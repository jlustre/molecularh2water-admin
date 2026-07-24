<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'stat_date',
    'leads_added',
    'phone_calls',
    'invites',
    'schedule_presentation',
    'actual_demo',
    'sales_closed',
])]
class ConsultantPerformanceDaily extends Model
{
    /**
     * @return list<string>
     */
    public static function metricKeys(): array
    {
        return [
            'leads_added',
            'phone_calls',
            'invites',
            'schedule_presentation',
            'actual_demo',
            'sales_closed',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function metricLabels(): array
    {
        return [
            'leads_added' => 'Leads added',
            'phone_calls' => 'Phone calls',
            'invites' => 'Invites',
            'schedule_presentation' => 'Schedule presentation',
            'actual_demo' => 'Actual Presentation',
            'sales_closed' => 'Sales closed',
        ];
    }

    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'leads_added' => 'integer',
            'phone_calls' => 'integer',
            'invites' => 'integer',
            'schedule_presentation' => 'integer',
            'actual_demo' => 'integer',
            'sales_closed' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
