<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstallationQuestionnaire;
use App\Support\FrontendUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstallationQuestionnaireController extends Controller
{
    public function index(Request $request): View
    {
        $query = InstallationQuestionnaire::query()->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('postal_code', 'like', "%{$search}%")
                    ->orWhere('property_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('property_type')) {
            $query->where('property_type', $request->string('property_type')->toString());
        }

        if ($request->filled('submitted')) {
            match ($request->submitted) {
                '7_days' => $query->where('created_at', '>=', now()->subDays(7)),
                '30_days' => $query->where('created_at', '>=', now()->subDays(30)),
                '90_days' => $query->where('created_at', '>=', now()->subDays(90)),
                default => null,
            };
        }

        $questionnaires = $query
            ->paginate((int) $request->integer('per_page', 10))
            ->withQueryString();

        return view('admin.installation-questionnaires.index', [
            'installationUrl' => FrontendUrl::installationQuestionnaire(),
            'newSubmissions' => InstallationQuestionnaire::query()
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'propertyTypes' => InstallationQuestionnaire::query()
                ->select('property_type')
                ->distinct()
                ->orderBy('property_type')
                ->pluck('property_type'),
            'questionnaires' => $questionnaires,
            'thisMonthSubmissions' => InstallationQuestionnaire::query()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'totalSubmissions' => InstallationQuestionnaire::query()->count(),
            'withPhotos' => InstallationQuestionnaire::query()
                ->whereNotNull('sink_photo_path')
                ->count(),
        ]);
    }

    public function show(InstallationQuestionnaire $installationQuestionnaire): View
    {
        return view('admin.installation-questionnaires.show', [
            'installationUrl' => FrontendUrl::installationQuestionnaire(),
            'questionnaire' => $installationQuestionnaire,
        ]);
    }

    public function edit(InstallationQuestionnaire $installationQuestionnaire): View
    {
        return view('admin.installation-questionnaires.edit', [
            'questionnaire' => $installationQuestionnaire,
        ]);
    }

    public function update(
        Request $request,
        InstallationQuestionnaire $installationQuestionnaire,
    ): RedirectResponse {
        $attributes = $request->validate($this->validationRules());

        $installationQuestionnaire->update([
            ...$attributes,
            'existing_equipment' => $attributes['existing_equipment'] ?? [],
        ]);

        return redirect()
            ->route('admin.installation-questionnaires.show', $installationQuestionnaire)
            ->with('status', 'Installation questionnaire updated.');
    }

    public function destroy(InstallationQuestionnaire $installationQuestionnaire): RedirectResponse
    {
        if ($installationQuestionnaire->sink_photo_path) {
            Storage::disk('public')->delete($installationQuestionnaire->sink_photo_path);
        }

        $installationQuestionnaire->delete();

        return redirect()
            ->route('admin.installation-questionnaires.index')
            ->with('status', 'Installation questionnaire deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validationRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'street_address' => ['required', 'string', 'max:255'],
            'street_address_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:40'],
            'country' => ['required', 'string', 'max:120'],
            'property_type' => [
                'required',
                'string',
                Rule::in([
                    'Single Family Home',
                    'Condo',
                    'Townhouse',
                    'Apartment',
                ]),
            ],
            'existing_equipment' => ['nullable', 'array'],
            'existing_equipment.*' => [
                'string',
                Rule::in([
                    'Under the sink Reverse Osmosis/Water Filter',
                    'Counter Alkaline Water Machine/Water Purifier',
                    'Water Softener',
                ]),
            ],
            'ownership' => ['nullable', 'string', Rule::in(['own', 'rent'])],
            'water_source' => [
                'required',
                'string',
                Rule::in([
                    'Municipal (connected to the city)',
                    'Well',
                    'Rainwater',
                    'None',
                    'Other',
                ]),
            ],
            'water_source_other' => ['nullable', 'string', 'max:255'],
            'special_requirements' => ['nullable', 'string', 'max:5000'],
            'additional_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
