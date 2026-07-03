<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Crm\ProspectCaptureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProspectController extends Controller
{
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
        ]);

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
}
