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
    ];

    protected function casts(): array
    {
        return [
            'existing_equipment' => 'array',
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

    public function sinkPhotoUrl(): ?string
    {
        if (! $this->sink_photo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->sink_photo_path);
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
