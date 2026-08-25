<?php

namespace App\Models;

use App\Enums\InstallerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'city',
        'state',
        'status',
        'notes',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => InstallerStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    public function installations(): HasMany
    {
        return $this->hasMany(InstallerInstallation::class);
    }

    public function questionnaires(): HasMany
    {
        return $this->hasMany(InstallationQuestionnaire::class);
    }

    public function locationSummary(): string
    {
        return collect([$this->city, $this->state])
            ->filter()
            ->implode(', ');
    }

    public function isArchived(): bool
    {
        return $this->status === InstallerStatus::Archived;
    }

    public function archive(): void
    {
        $this->update([
            'status' => InstallerStatus::Archived,
            'archived_at' => now(),
        ]);
    }

    public function restoreFromArchive(): void
    {
        $this->update([
            'status' => InstallerStatus::Active,
            'archived_at' => null,
        ]);
    }
}
