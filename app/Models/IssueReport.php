<?php

namespace App\Models;

use App\Enums\IssueReportCategory;
use App\Enums\IssueReportSeverity;
use App\Enums\IssueReportSite;
use App\Enums\IssueReportSource;
use App\Enums\IssueReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class IssueReport extends Model
{
    /** @use HasFactory<\Database\Factories\IssueReportFactory> */
    use HasFactory;

    protected $fillable = [
        'reference_code',
        'reporter_name',
        'reporter_email',
        'reporter_phone',
        'user_id',
        'source',
        'site',
        'category',
        'severity',
        'title',
        'description',
        'page_url',
        'steps_to_reproduce',
        'expected_behavior',
        'actual_behavior',
        'browser',
        'device',
        'screenshot_path',
        'status',
        'admin_notes',
        'resolution_summary',
        'assigned_to_user_id',
        'status_changed_at',
        'resolved_at',
        'closed_at',
        'last_reporter_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => IssueReportSource::class,
            'site' => IssueReportSite::class,
            'category' => IssueReportCategory::class,
            'severity' => IssueReportSeverity::class,
            'status' => IssueReportStatus::class,
            'status_changed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_reporter_notified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $report): void {
            if (blank($report->reference_code)) {
                $report->reference_code = 'tmp-'.uniqid('', true);
            }

            $report->status ??= IssueReportStatus::New;
            $report->status_changed_at ??= now();
        });

        static::created(function (self $report): void {
            if (str_starts_with((string) $report->reference_code, 'tmp-')) {
                $year = $report->created_at?->format('Y') ?? now()->format('Y');
                $report->forceFill([
                    'reference_code' => sprintf('IR-%s-%05d', $year, $report->id),
                ])->saveQuietly();
            }
        });

        static::deleting(function (self $report): void {
            $report->deleteScreenshot();
        });
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function statusUpdates(): HasMany
    {
        return $this->hasMany(IssueReportStatusUpdate::class)->latest();
    }

    public function screenshotUrl(): ?string
    {
        if (! filled($this->screenshot_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->screenshot_path);
    }

    public function deleteScreenshot(): void
    {
        if (filled($this->screenshot_path) && Storage::disk('public')->exists($this->screenshot_path)) {
            Storage::disk('public')->delete($this->screenshot_path);
        }
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            IssueReportStatus::New,
            IssueReportStatus::Acknowledged,
            IssueReportStatus::InProgress,
        ]);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.$term.'%';

        return $query->where(function (Builder $inner) use ($like) {
            $inner->where('reference_code', 'like', $like)
                ->orWhere('title', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhere('reporter_name', 'like', $like)
                ->orWhere('reporter_email', 'like', $like)
                ->orWhere('page_url', 'like', $like);
        });
    }
}
