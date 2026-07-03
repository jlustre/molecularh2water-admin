<?php

namespace App\Http\Controllers\Api;

use App\Enums\Crm\LeadLifecycle;
use App\Http\Controllers\Controller;
use App\Models\Crm\LandingPage;
use App\Services\Crm\ProspectCaptureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadCaptureController extends Controller
{
    public function __construct(
        private readonly ProspectCaptureService $captureService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        if ($request->filled(config('crm.capture.honeypot_field', 'company_website'))) {
            return $this->successResponse(null);
        }

        $validated = $request->validate($this->submissionRules());

        $landingPage = null;

        if (! empty($validated['landing_page_id'])) {
            $landingPage = LandingPage::query()
                ->whereKey($validated['landing_page_id'])
                ->where('is_published', true)
                ->with('form')
                ->firstOrFail();
        } elseif (! empty($validated['landing_page_slug'])) {
            $landingPage = LandingPage::query()
                ->where('slug', $validated['landing_page_slug'])
                ->where('is_published', true)
                ->with('form')
                ->firstOrFail();
        }

        $lead = $this->captureService->capture($validated, $landingPage);

        $redirectUrl = $landingPage?->form?->settings['redirect_url']
            ?? $landingPage?->cta_url;

        return $this->successResponse($lead, $redirectUrl);
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'lifecycle' => ['nullable', Rule::in(['lead', 'prospect', 'client', 'recruit'])],
        ]);

        $lifecycle = isset($validated['lifecycle'])
            ? LeadLifecycle::from($validated['lifecycle'])
            : null;

        return response()->json([
            'email' => $validated['email'],
            'exists' => $this->captureService->emailExists($validated['email'], $lifecycle),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function submissionRules(): array
    {
        return [
            'landing_page_id' => ['nullable', 'integer', 'exists:landing_pages,id'],
            'landing_page_slug' => ['nullable', 'string', 'max:120'],
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
        ];
    }

    private function successResponse($lead, ?string $redirectUrl = null): JsonResponse
    {
        return response()->json([
            'message' => 'Thank you. A team member will be in touch soon.',
            'redirect_url' => $redirectUrl,
            'data' => $lead ? [
                'id' => $lead->id,
                'lifecycle' => $lead->lifecycle->value,
                'first_name' => $lead->first_name,
                'email' => $lead->email,
                'assigned_user_id' => $lead->assigned_user_id,
                'captured_at' => $lead->created_at?->toIso8601String(),
            ] : null,
        ], 201);
    }
}
