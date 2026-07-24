<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'sink_photo_path',
        'sink_photo_original_name',
        'sink_photos',
    ];

    protected function casts(): array
    {
        return [
            'existing_equipment' => 'array',
            'sink_photos' => 'array',
        ];
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
}
