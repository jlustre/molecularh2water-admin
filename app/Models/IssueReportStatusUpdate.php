<?php

namespace App\Models;

use App\Enums\IssueReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueReportStatusUpdate extends Model
{
    protected $fillable = [
        'issue_report_id',
        'user_id',
        'from_status',
        'to_status',
        'note',
        'notified_reporter',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => IssueReportStatus::class,
            'to_status' => IssueReportStatus::class,
            'notified_reporter' => 'boolean',
        ];
    }

    public function issueReport(): BelongsTo
    {
        return $this->belongsTo(IssueReport::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
