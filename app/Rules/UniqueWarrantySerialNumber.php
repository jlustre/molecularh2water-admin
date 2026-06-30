<?php

namespace App\Rules;

use App\Models\WarrantyRegistration;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueWarrantySerialNumber implements ValidationRule
{
    public function __construct(private ?WarrantyRegistration $ignore = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $serialNumber = WarrantyRegistration::normalizeSerialNumber((string) $value);

        if ($serialNumber === '') {
            return;
        }

        $query = WarrantyRegistration::query()->matchingSerial($serialNumber);

        if ($this->ignore) {
            $query->whereKeyNot($this->ignore->getKey());
        }

        if ($query->exists()) {
            $fail('This serial number has already been registered for warranty.');
        }
    }
}
