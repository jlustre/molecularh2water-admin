<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Crm\ProspectCaptureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class ProspectController extends Controller
{
    private const WARRANTY_MEDIA_MAX_FILES = 6;

    public function __construct(
        private readonly ProspectCaptureService $prospectCaptureService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        if ($request->filled(config('crm.capture.honeypot_field', 'company_website'))) {
            return response()->json([
                'message' => 'Thank you. A team member will be in touch soon.',
            ], 201);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255', 'required_without_all:first_name,last_name'],
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:50', 'required_without:email'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'interested_in' => ['nullable', 'string', 'max:255'],
            'warranty_concern' => [
                Rule::requiredIf(fn () => $request->input('interested_in') === 'Warranty Service'),
                'nullable',
                'string',
                'max:5000',
            ],
            'warranty_media' => [
                Rule::requiredIf(fn () => $request->input('interested_in') === 'Warranty Service'),
                'nullable',
                'array',
                'max:'.self::WARRANTY_MEDIA_MAX_FILES,
            ],
            'warranty_media.*' => [
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,image/heic,image/heif,video/mp4,video/quicktime,video/webm',
                'max:51200',
            ],
            'source' => ['nullable', 'string', 'max:120'],
            'form_context' => ['nullable', 'string', 'max:120'],
            'tracking_source' => ['nullable', 'string', 'max:120'],
            'referrer_name' => ['nullable', 'string', 'max:255'],
            'preferred_time' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'page_url' => ['nullable', 'url', 'max:500'],
            'consent_given' => ['accepted'],
        ], [
            'consent_given.accepted' => 'Please confirm you agree to be contacted.',
            'email.required_without' => 'Please provide an email address or phone number.',
            'phone.required_without' => 'Please provide a phone number or email address.',
            'warranty_concern.required' => 'Please describe your warranty concern.',
            'warranty_media.required' => 'Please upload images or a short video for warranty service.',
        ]);

        $validated['warranty_media'] = $this->storeWarrantyMedia($request);

        if (($validated['interested_in'] ?? null) !== 'Warranty Service') {
            $validated['warranty_concern'] = null;
            $validated['warranty_media'] = [];
        }

        $prospect = $this->prospectCaptureService->capture($validated);

        return response()->json([
            'message' => 'Thank you. A team member will be in touch soon.',
            'data' => [
                'id' => $prospect->id,
                'lifecycle' => $prospect->lifecycle->value,
                'first_name' => $prospect->first_name,
                'email' => $prospect->email,
                'captured_at' => $prospect->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * @return list<array{path: string, original_name: string, mime_type: string|null}>
     */
    private function storeWarrantyMedia(Request $request): array
    {
        if (! $request->hasFile('warranty_media')) {
            return [];
        }

        $uploaded = $request->file('warranty_media');
        $files = is_array($uploaded) ? array_values($uploaded) : [$uploaded];

        $stored = [];

        foreach (array_slice($files, 0, self::WARRANTY_MEDIA_MAX_FILES) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $stored[] = [
                'path' => $file->store('website-form-warranty-media', 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
            ];
        }

        return $stored;
    }
}
