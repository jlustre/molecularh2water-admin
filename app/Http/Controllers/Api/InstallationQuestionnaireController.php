<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstallationQuestionnaire;
use App\Services\EmailMappingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class InstallationQuestionnaireController extends Controller
{
    private const MAX_SINK_PHOTOS = 8;

    public function __construct(
        private readonly EmailMappingService $emailMappings,
    ) {}

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
            'sink_photos' => ['nullable', 'array', 'max:'.self::MAX_SINK_PHOTOS],
            'sink_photos.*' => ['image', 'max:10240'],
        ]);

        $uploadedPhotos = $this->storeSinkPhotos($request);

        $firstPhoto = $uploadedPhotos[0] ?? null;

        $questionnaire = InstallationQuestionnaire::create([
            ...collect($validated)->except(['sink_photo', 'sink_photos'])->all(),
            'existing_equipment' => $validated['existing_equipment'] ?? [],
            'sink_photos' => $uploadedPhotos,
            'sink_photo_path' => $firstPhoto['path'] ?? null,
            'sink_photo_original_name' => $firstPhoto['original_name'] ?? null,
        ]);

        $this->emailMappings->notifyInstallationQuestionnaire($questionnaire);

        return response()->json([
            'message' => 'Thank you. Your pre-installation questionnaire has been submitted.',
            'data' => [
                'id' => $questionnaire->id,
                'submitted_at' => $questionnaire->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * @return list<array{path: string, original_name: string}>
     */
    private function storeSinkPhotos(Request $request): array
    {
        /** @var list<UploadedFile> $files */
        $files = [];

        if ($request->hasFile('sink_photos')) {
            $uploaded = $request->file('sink_photos');
            $files = is_array($uploaded) ? array_values($uploaded) : [$uploaded];
        } elseif ($request->hasFile('sink_photo')) {
            $files = [$request->file('sink_photo')];
        }

        $photos = [];

        foreach (array_slice($files, 0, self::MAX_SINK_PHOTOS) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $photos[] = [
                'path' => $file->store('installation-questionnaires', 'public'),
                'original_name' => $file->getClientOriginalName(),
            ];
        }

        return $photos;
    }
}
