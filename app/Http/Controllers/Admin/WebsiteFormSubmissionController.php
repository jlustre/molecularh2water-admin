<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WebsiteFormSubmissionStatus;
use App\Enums\WebsiteFormType;
use App\Http\Controllers\Controller;
use App\Models\WebsiteFormSubmission;
use App\Support\FrontendUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WebsiteFormSubmissionController extends Controller
{
    public function index(Request $request, string $formType): View
    {
        $type = $this->resolveFormType($formType);
        $query = WebsiteFormSubmission::query()
            ->ofType($type)
            ->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('referrer_name', 'like', "%{$search}%")
                    ->orWhere('interested_in', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('preferred_time', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && array_key_exists($request->status, WebsiteFormSubmissionStatus::options())) {
            $query->where('status', $request->status);
        }

        if ($request->filled('submitted')) {
            match ($request->submitted) {
                '7_days' => $query->where('created_at', '>=', now()->subDays(7)),
                '30_days' => $query->where('created_at', '>=', now()->subDays(30)),
                '90_days' => $query->where('created_at', '>=', now()->subDays(90)),
                default => null,
            };
        }

        $baseQuery = WebsiteFormSubmission::query()->ofType($type);

        return view('admin.website-form-submissions.index', [
            'formType' => $type,
            'publicUrl' => FrontendUrl::websiteForm($type),
            'statuses' => WebsiteFormSubmissionStatus::options(),
            'submissions' => $query
                ->paginate((int) $request->integer('per_page', 10))
                ->withQueryString(),
            'totalSubmissions' => (clone $baseQuery)->count(),
            'newSubmissions' => (clone $baseQuery)
                ->where('status', WebsiteFormSubmissionStatus::New)
                ->count(),
            'thisMonthSubmissions' => (clone $baseQuery)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'contactedSubmissions' => (clone $baseQuery)
                ->where('status', WebsiteFormSubmissionStatus::Contacted)
                ->count(),
        ]);
    }

    public function create(string $formType): View
    {
        $type = $this->resolveFormType($formType);

        return view('admin.website-form-submissions.create', [
            'formType' => $type,
            'statuses' => WebsiteFormSubmissionStatus::options(),
            'submission' => new WebsiteFormSubmission([
                'form_type' => $type,
                'status' => WebsiteFormSubmissionStatus::New,
                'source' => 'admin',
                'form_context' => $type->formContext(),
                'consent_given' => true,
            ]),
        ]);
    }

    public function store(Request $request, string $formType): RedirectResponse
    {
        $type = $this->resolveFormType($formType);
        $attributes = $this->validatedAttributes($request, $type);

        $submission = WebsiteFormSubmission::query()->create($attributes);

        return redirect()
            ->route('admin.website-forms.show', [$type->routeKey(), $submission])
            ->with('status', $type->singularLabel().' created.');
    }

    public function show(string $formType, WebsiteFormSubmission $websiteFormSubmission): View
    {
        $type = $this->resolveFormType($formType);
        $this->ensureSubmissionMatchesType($websiteFormSubmission, $type);

        $websiteFormSubmission->load('prospect');

        return view('admin.website-form-submissions.show', [
            'formType' => $type,
            'publicUrl' => FrontendUrl::websiteForm($type),
            'submission' => $websiteFormSubmission,
            'statuses' => WebsiteFormSubmissionStatus::options(),
        ]);
    }

    public function edit(string $formType, WebsiteFormSubmission $websiteFormSubmission): View
    {
        $type = $this->resolveFormType($formType);
        $this->ensureSubmissionMatchesType($websiteFormSubmission, $type);

        return view('admin.website-form-submissions.edit', [
            'formType' => $type,
            'statuses' => WebsiteFormSubmissionStatus::options(),
            'submission' => $websiteFormSubmission,
        ]);
    }

    public function update(
        Request $request,
        string $formType,
        WebsiteFormSubmission $websiteFormSubmission,
    ): RedirectResponse {
        $type = $this->resolveFormType($formType);
        $this->ensureSubmissionMatchesType($websiteFormSubmission, $type);

        $websiteFormSubmission->update($this->validatedAttributes($request, $type));

        return redirect()
            ->route('admin.website-forms.show', [$type->routeKey(), $websiteFormSubmission])
            ->with('status', $type->singularLabel().' updated.');
    }

    public function destroy(string $formType, WebsiteFormSubmission $websiteFormSubmission): RedirectResponse
    {
        $type = $this->resolveFormType($formType);
        $this->ensureSubmissionMatchesType($websiteFormSubmission, $type);

        $websiteFormSubmission->delete();

        return redirect()
            ->route('admin.website-forms.index', $type->routeKey())
            ->with('status', $type->singularLabel().' deleted.');
    }

    public function convertToProspect(
        string $formType,
        WebsiteFormSubmission $websiteFormSubmission,
    ): RedirectResponse {
        abort_unless(auth()->user()?->hasPermission('website-forms.manage'), 403);
        abort_unless(auth()->user()?->hasPermission('prospects.manage') || auth()->user()?->hasPermission('leads.create'), 403);

        $type = $this->resolveFormType($formType);
        $this->ensureSubmissionMatchesType($websiteFormSubmission, $type);

        if ($websiteFormSubmission->prospect_id) {
            return redirect()
                ->route('admin.crm.prospects.show', $websiteFormSubmission->prospect_id)
                ->with('status', 'This submission is already linked to a CRM prospect.');
        }

        $prospect = app(\App\Services\Crm\ProspectCaptureService::class)->capture([
            'name' => $websiteFormSubmission->name,
            'email' => $websiteFormSubmission->email,
            'phone' => $websiteFormSubmission->phone,
            'referrer_name' => $websiteFormSubmission->referrer_name,
            'preferred_time' => $websiteFormSubmission->preferred_time,
            'interested_in' => $websiteFormSubmission->interested_in,
            'message' => $websiteFormSubmission->message,
            'source' => $websiteFormSubmission->source ?: 'website',
            'form_context' => $websiteFormSubmission->form_context ?: $type->formContext(),
            'tracking_source' => $websiteFormSubmission->tracking_source,
            'page_url' => $websiteFormSubmission->page_url,
            'consent_given' => $websiteFormSubmission->consent_given,
        ], null, false);

        $websiteFormSubmission->update([
            'prospect_id' => $prospect->id,
            'status' => WebsiteFormSubmissionStatus::Contacted,
        ]);

        return redirect()
            ->route('admin.crm.prospects.show', $prospect)
            ->with('status', 'CRM prospect created from website form submission.');
    }

    private function resolveFormType(string $formType): WebsiteFormType
    {
        $type = WebsiteFormType::tryFromRouteKey($formType);

        abort_unless($type instanceof WebsiteFormType, 404);

        return $type;
    }

    private function ensureSubmissionMatchesType(
        WebsiteFormSubmission $submission,
        WebsiteFormType $type,
    ): void {
        abort_unless($submission->form_type === $type, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAttributes(Request $request, WebsiteFormType $type): array
    {
        $attributes = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(WebsiteFormSubmissionStatus::options()))],
            'name' => ['nullable', 'string', 'max:255', 'required_without_all:email,phone'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:50', 'required_without:email'],
            'referrer_name' => ['nullable', 'string', 'max:255'],
            'preferred_time' => ['nullable', 'string', 'max:255'],
            'interested_in' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'source' => ['nullable', 'string', 'max:120'],
            'tracking_source' => ['nullable', 'string', 'max:120'],
            'page_url' => ['nullable', 'string', 'max:500'],
            'consent_given' => ['sometimes', 'boolean'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        return [
            ...$attributes,
            'form_type' => $type,
            'form_context' => $type->formContext(),
            'consent_given' => $request->boolean('consent_given'),
            'source' => $attributes['source'] ?? 'admin',
        ];
    }
}
