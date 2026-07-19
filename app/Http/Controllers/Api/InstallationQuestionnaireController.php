<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\InstallationQuestionnaireSubmitted;
use App\Models\InstallationQuestionnaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class InstallationQuestionnaireController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
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
            'water_source_other' => [
                Rule::requiredIf(fn () => $request->input('water_source') === 'Other'),
                'nullable',
                'string',
                'max:255',
            ],
            'special_requirements' => ['nullable', 'string', 'max:5000'],
            'additional_notes' => ['nullable', 'string', 'max:5000'],
            'sink_photo' => ['nullable', 'image', 'max:10240'],
        ]);

        $sinkPhotoPath = null;
        $sinkPhotoOriginalName = null;

        if ($request->hasFile('sink_photo')) {
            $file = $request->file('sink_photo');
            $sinkPhotoPath = $file->store('installation-questionnaires', 'public');
            $sinkPhotoOriginalName = $file->getClientOriginalName();
        }

        $questionnaire = InstallationQuestionnaire::create([
            ...collect($validated)->except(['sink_photo'])->all(),
            'existing_equipment' => $validated['existing_equipment'] ?? [],
            'sink_photo_path' => $sinkPhotoPath,
            'sink_photo_original_name' => $sinkPhotoOriginalName,
        ]);

        Mail::to('shipping@happycooking.com')->send(
            new InstallationQuestionnaireSubmitted($questionnaire),
        );

        return response()->json([
            'message' => 'Thank you. Your pre-installation questionnaire has been submitted.',
            'data' => [
                'id' => $questionnaire->id,
                'submitted_at' => $questionnaire->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
