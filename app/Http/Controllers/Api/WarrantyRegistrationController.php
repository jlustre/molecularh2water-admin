<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\WarrantyRegistrationConfirmation;
use App\Models\WarrantyRegistration;
use App\Rules\UniqueWarrantySerialNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class WarrantyRegistrationController extends Controller
{
    public function checkSerial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'serial_number' => ['required', 'string', 'max:100'],
        ]);

        $serialNumber = WarrantyRegistration::normalizeSerialNumber($validated['serial_number']);
        $available = ! WarrantyRegistration::query()->matchingSerial($serialNumber)->exists();

        return response()->json([
            'available' => $available,
            'message' => $available
                ? 'This serial number is available for registration.'
                : 'This serial number has already been registered for warranty.',
            'serial_number' => $serialNumber,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'serial_number' => WarrantyRegistration::normalizeSerialNumber(
                (string) $request->input('serial_number', ''),
            ),
        ]);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'serial_number' => ['required', 'string', 'max:100', new UniqueWarrantySerialNumber],
            'machine_model' => ['required', 'string', 'max:255'],
            'purchase_date' => ['required', 'date', 'before_or_equal:today'],
            'purchased_from' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $registration = WarrantyRegistration::create($validated);

        Mail::to($registration->email)->send(
            new WarrantyRegistrationConfirmation($registration),
        );

        return response()->json([
            'message' => 'Your machine has been registered for warranty coverage.',
            'data' => [
                'id' => $registration->id,
                'serial_number' => $registration->serial_number,
                'registered_at' => $registration->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
