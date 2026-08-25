<?php

namespace App\Models;

use App\Enums\InstallerAssignmentRejectionReason;
use App\Enums\InstallerAssignmentResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class InstallationQuestionnaire extends Model
{
    /** @use HasFactory<\Database\Factories\InstallationQuestionnaireFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'street_address',
        'street_address_2',
        'city',
        'state',
        'postal_code',
        'country',
        'property_type',
        'existing_equipment',
        'ownership',
        'water_source',
        'water_source_other',
        'special_requirements',
        'additional_notes',
        'installer_id',
        'assigned_by_user_id',
        'assigned_at',
        'assignment_notes',
        'assignment_response',
        'assignment_responded_at',
        'assignment_rejection_reason',
        'assignment_rejection_notes',
        'seller_id',
        'sink_photo_path',
        'sink_photo_original_name',
        'sink_photos',
    ];

    protected function casts(): array
    {
        return [
            'existing_equipment' => 'array',
            'sink_photos' => 'array',
            'assigned_at' => 'datetime',
            'assignment_response' => InstallerAssignmentResponse::class,
            'assignment_responded_at' => 'datetime',
            'assignment_rejection_reason' => InstallerAssignmentRejectionReason::class,
        ];
    }

    public function installer(): BelongsTo
    {
        return $this->belongsTo(Installer::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function installerInstallations(): HasMany
    {
        return $this->hasMany(InstallerInstallation::class);
    }

    public function currentInstallerInstallation(): ?InstallerInstallation
    {
        $jobs = $this->relationLoaded('installerInstallations')
            ? $this->installerInstallations
            : $this->installerInstallations()->get();

        return $jobs
            ->sortByDesc('id')
            ->first(function (InstallerInstallation $job) {
                if (! $this->installer_id) {
                    return false;
                }

                return $job->installer_id === $this->installer_id;
            });
    }

    public function isAssigned(): bool
    {
        return $this->installer_id !== null;
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('installer_id');
    }

    public function scopeAssigned(Builder $query): Builder
    {
        return $query->whereNotNull('installer_id');
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(
            fn (): string => trim("{$this->first_name} {$this->last_name}"),
        );
    }

    protected function formattedAddress(): Attribute
    {
        return Attribute::get(function (): string {
            $lines = array_filter([
                $this->street_address,
                $this->street_address_2,
                trim("{$this->city}, {$this->state} {$this->postal_code}"),
                $this->country,
            ]);

            return implode("\n", $lines);
        });
    }

    /**
     * @return list<array{path: string, original_name: ?string, url: string}>
     */
    public function sinkPhotoItems(): array
    {
        $photos = collect($this->sink_photos ?? [])
            ->filter(fn ($photo) => is_array($photo) && filled($photo['path'] ?? null))
            ->map(fn (array $photo) => [
                'path' => (string) $photo['path'],
                'original_name' => filled($photo['original_name'] ?? null)
                    ? (string) $photo['original_name']
                    : null,
                'url' => Storage::disk('public')->url((string) $photo['path']),
            ])
            ->values()
            ->all();

        if ($photos !== []) {
            return $photos;
        }

        if (! $this->sink_photo_path) {
            return [];
        }

        return [[
            'path' => $this->sink_photo_path,
            'original_name' => $this->sink_photo_original_name,
            'url' => Storage::disk('public')->url($this->sink_photo_path),
        ]];
    }

    public function hasSinkPhotos(): bool
    {
        return $this->sinkPhotoItems() !== [];
    }

    public function sinkPhotoUrl(): ?string
    {
        return $this->sinkPhotoItems()[0]['url'] ?? null;
    }

    public function ownershipLabel(): string
    {
        return match ($this->ownership) {
            'own' => 'Yes I own',
            'rent' => 'Yes I rent',
            default => 'Not provided',
        };
    }

    public function waterSourceLabel(): string
    {
        if ($this->water_source === 'Other') {
            return filled($this->water_source_other)
                ? 'Other: '.$this->water_source_other
                : 'Other';
        }

        return $this->water_source ?: 'Not provided';
    }

    public function existingEquipmentLabel(): string
    {
        $items = collect($this->existing_equipment ?? [])->filter();

        return $items->isEmpty() ? 'None' : $items->implode(', ');
    }
}
