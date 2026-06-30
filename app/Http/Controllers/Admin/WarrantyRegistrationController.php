<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WarrantyRegistration;
use App\Rules\UniqueWarrantySerialNumber;
use App\Support\FrontendUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarrantyRegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $query = WarrantyRegistration::query()->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('machine_model', 'like', "%{$search}%")
                    ->orWhere('purchased_from', 'like', "%{$search}%");
            });
        }

        if ($request->filled('machine_model')) {
            $query->where('machine_model', $request->string('machine_model')->toString());
        }

        if ($request->filled('registered')) {
            match ($request->registered) {
                '7_days' => $query->where('created_at', '>=', now()->subDays(7)),
                '30_days' => $query->where('created_at', '>=', now()->subDays(30)),
                '90_days' => $query->where('created_at', '>=', now()->subDays(90)),
                default => null,
            };
        }

        $registrations = $query
            ->paginate((int) $request->integer('per_page', 10))
            ->withQueryString();

        return view('admin.warranty-registrations.index', [
            'environmentLabel' => FrontendUrl::environmentLabel(),
            'machineModels' => WarrantyRegistration::query()
                ->select('machine_model')
                ->distinct()
                ->orderBy('machine_model')
                ->pluck('machine_model'),
            'newRegistrations' => WarrantyRegistration::query()
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'registrations' => $registrations,
            'thisMonthRegistrations' => WarrantyRegistration::query()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'totalRegistrations' => WarrantyRegistration::query()->count(),
            'uniqueModels' => WarrantyRegistration::query()->distinct('machine_model')->count('machine_model'),
            'warrantyUrl' => FrontendUrl::warrantyRegistration(),
        ]);
    }

    public function show(WarrantyRegistration $warrantyRegistration): View
    {
        return view('admin.warranty-registrations.show', [
            'registration' => $warrantyRegistration,
            'warrantyUrl' => FrontendUrl::warrantyRegistration(),
        ]);
    }

    public function edit(WarrantyRegistration $warrantyRegistration): View
    {
        return view('admin.warranty-registrations.edit', [
            'registration' => $warrantyRegistration,
        ]);
    }

    public function update(Request $request, WarrantyRegistration $warrantyRegistration): RedirectResponse
    {
        $request->merge([
            'serial_number' => WarrantyRegistration::normalizeSerialNumber(
                (string) $request->input('serial_number', ''),
            ),
        ]);

        $attributes = $request->validate($this->validationRules($warrantyRegistration));

        $warrantyRegistration->update($attributes);

        return redirect()
            ->route('admin.warranty-registrations.show', $warrantyRegistration)
            ->with('status', 'Warranty registration updated.');
    }

    public function destroy(WarrantyRegistration $warrantyRegistration): RedirectResponse
    {
        $warrantyRegistration->delete();

        return redirect()
            ->route('admin.warranty-registrations.index')
            ->with('status', 'Warranty registration deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validationRules(WarrantyRegistration $warrantyRegistration): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'serial_number' => [
                'required',
                'string',
                'max:100',
                new UniqueWarrantySerialNumber($warrantyRegistration),
            ],
            'machine_model' => ['required', 'string', 'max:255'],
            'purchase_date' => ['required', 'date', 'before_or_equal:today'],
            'purchased_from' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
