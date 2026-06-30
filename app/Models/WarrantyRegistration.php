<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyRegistration extends Model
{
    /** @use HasFactory<\Database\Factories\WarrantyRegistrationFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'email',
        'phone',
        'serial_number',
        'machine_model',
        'purchase_date',
        'purchased_from',
        'notes',
    ];

    public static function normalizeSerialNumber(string $serialNumber): string
    {
        return strtoupper(trim($serialNumber));
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeMatchingSerial(Builder $query, string $serialNumber): Builder
    {
        $normalizedSerial = self::normalizeSerialNumber($serialNumber);

        return $query->whereRaw('UPPER(TRIM(serial_number)) = ?', [$normalizedSerial]);
    }

    public function isSerialRegistered(string $serialNumber): bool
    {
        return self::query()
            ->matchingSerial($serialNumber)
            ->whereKeyNot($this->getKey())
            ->exists();
    }

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
        ];
    }
}