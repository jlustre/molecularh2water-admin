<?php

namespace App\Models\Crm;

use App\Models\Crm\Concerns\BelongsToCrmContact;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineStageHistory extends Model
{
    use BelongsToCrmContact;

    protected $fillable = [
        'contact_type',
        'contact_id',
        'funnel_id',
        'from_stage_id',
        'to_stage_id',
        'user_id',
        'duration_in_previous_stage_seconds',
    ];

    protected function casts(): array
    {
        return [
            'duration_in_previous_stage_seconds' => 'integer',
        ];
    }

    public function funnel(): BelongsTo
    {
        return $this->belongsTo(Funnel::class);
    }

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(FunnelStage::class, 'from_stage_id');
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(FunnelStage::class, 'to_stage_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
