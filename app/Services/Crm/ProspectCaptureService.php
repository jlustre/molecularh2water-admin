<?php

namespace App\Services\Crm;

use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadTemperature;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\LandingPage;
use App\Models\Crm\Lead;
use App\Models\Crm\LeadSource;
use App\Models\Crm\Lifecycle;
use App\Models\Crm\Prospect;
use App\Models\User;
use App\Notifications\Crm\CrmLeadCapturedNotification;
use App\Support\Crm\CrmContactResolver;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProspectCaptureService
{
    public function __construct(
        private readonly TimelineService $timeline,
        private readonly LeadAssignmentService $assignment,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function capture(array $data, ?LandingPage $landingPage = null): Prospect
    {
        if ($landingPage && ! $landingPage->is_published) {
            throw ValidationException::withMessages([
                'landing_page_id' => 'This landing page is not published.',
            ]);
        }

        [$firstName, $lastName] = $this->resolveName($data);
        $funnel = $this->resolveFunnel($landingPage);
        $stage = $this->resolveStage($funnel);
        $formSettings = $landingPage?->form?->settings ?? [];
        $assigneeId = $this->resolveAssignee($formSettings);
        $lifecycle = LeadLifecycle::from(Arr::get(
            $formSettings,
            'lifecycle',
            config('crm.capture.default_lifecycle', 'prospect'),
        ));

        $prospect = Prospect::query()->create([
            'lifecycle_id' => Lifecycle::idFor($lifecycle),
            'status' => 'new',
            'temperature' => LeadTemperature::from(config('crm.capture.default_temperature', 'warm')),
            'score' => 10,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => Arr::get($data, 'email'),
            'phone' => Arr::get($data, 'phone'),
            'city' => Arr::get($data, 'city'),
            'state' => Arr::get($data, 'state'),
            'country' => Arr::get($data, 'country'),
            'lead_source_id' => $this->resolveLeadSourceId(
                $landingPage?->tracking_source
                    ?? Arr::get($data, 'tracking_source')
                    ?? Arr::get($data, 'source')
            ),
            'funnel_id' => $funnel?->id,
            'funnel_stage_id' => $stage?->id,
            'assigned_user_id' => $assigneeId,
            'interested_in' => Arr::get($data, 'interested_in'),
            'message' => Arr::get($data, 'message'),
            'consent_given' => (bool) Arr::get($data, 'consent_given', false),
            'metadata' => array_filter([
                'referrer_name' => Arr::get($data, 'referrer_name'),
                'preferred_time' => Arr::get($data, 'preferred_time'),
                'form_context' => Arr::get($data, 'form_context'),
                'tracking_source' => $landingPage?->tracking_source ?? Arr::get($data, 'tracking_source'),
                'page_url' => Arr::get($data, 'page_url'),
                'landing_page_id' => $landingPage?->id,
                'landing_page_slug' => $landingPage?->slug,
            ]),
        ]);

        $this->timeline->log(
            $prospect,
            $landingPage ? 'landing_page_conversion' : 'prospect_captured',
            $landingPage ? 'Landing page conversion' : 'Prospect captured from website',
            $this->buildCaptureDescription($data, $landingPage),
            [
                'source' => Arr::get($data, 'source'),
                'form_context' => Arr::get($data, 'form_context'),
                'interested_in' => Arr::get($data, 'interested_in'),
                'landing_page_id' => $landingPage?->id,
            ],
        );

        if ($landingPage) {
            $landingPage->increment('conversion_count');
        }

        $this->notifyStakeholders($prospect, $landingPage, $assigneeId);
        $actor = User::query()->find($assigneeId)
            ?? User::query()->whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first();

        if ($actor) {
            app(CrmAutomationService::class)->dispatch('prospect_captured', [
                'lead_id' => $prospect->id,
            ], $actor);
        }

        return $prospect->fresh(['source', 'stage', 'assignedUser']);
    }

    public function emailExists(string $email, ?LeadLifecycle $lifecycle = null): bool
    {
        if ($lifecycle) {
            return CrmContactResolver::queryFor($lifecycle)->where('email', $email)->exists();
        }

        return Prospect::query()->where('email', $email)->exists()
            || Lead::query()->where('email', $email)->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: ?string}
     */
    private function resolveName(array $data): array
    {
        $firstName = trim((string) Arr::get($data, 'first_name', ''));
        $lastName = trim((string) Arr::get($data, 'last_name', ''));

        if ($firstName !== '') {
            return [$firstName, $lastName !== '' ? $lastName : null];
        }

        $fullName = trim((string) Arr::get($data, 'name', ''));

        if ($fullName === '') {
            return ['Website', null];
        }

        $parts = preg_split('/\s+/', $fullName, 2) ?: [];

        return [
            $parts[0],
            $parts[1] ?? null,
        ];
    }

    private function resolveLeadSourceId(?string $source): ?int
    {
        $slug = Str::slug($source ?: 'website');

        $leadSource = LeadSource::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $source ? Str::headline(str_replace('-', ' ', $slug)) : 'Website',
                'is_active' => true,
                'sort_order' => 999,
            ],
        );

        return $leadSource->id;
    }

    private function resolveFunnel(?LandingPage $landingPage): ?Funnel
    {
        if ($landingPage?->funnel_id) {
            return Funnel::query()->find($landingPage->funnel_id);
        }

        return Funnel::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    private function resolveStage(?Funnel $funnel): ?FunnelStage
    {
        if (! $funnel) {
            return null;
        }

        return FunnelStage::query()
            ->where('funnel_id', $funnel->id)
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $formSettings
     */
    private function resolveAssignee(array $formSettings): ?int
    {
        return $this->assignment->resolve(Arr::get($formSettings, 'assignment'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildCaptureDescription(array $data, ?LandingPage $landingPage): string
    {
        $pieces = array_filter([
            $landingPage ? 'Page: '.$landingPage->title : null,
            Arr::get($data, 'form_context') ? 'Form: '.Arr::get($data, 'form_context') : null,
            Arr::get($data, 'interested_in') ? 'Interest: '.Arr::get($data, 'interested_in') : null,
            Arr::get($data, 'referrer_name') ? 'Referrer: '.Arr::get($data, 'referrer_name') : null,
            Arr::get($data, 'preferred_time') ? 'Preferred time: '.Arr::get($data, 'preferred_time') : null,
        ]);

        return $pieces !== [] ? implode(' · ', $pieces) : 'Submitted via public capture form.';
    }

    private function notifyStakeholders(Prospect $prospect, ?LandingPage $landingPage, ?int $assigneeId): void
    {
        $sourceLabel = $landingPage?->title ?? 'Website';

        if ($assigneeId) {
            $user = User::query()->find($assigneeId);

            if ($user) {
                $user->notify(new CrmLeadCapturedNotification($prospect, $sourceLabel));

                return;
            }
        }

        User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['super-admin', 'admin']))
            ->each(fn (User $user) => $user->notify(new CrmLeadCapturedNotification($prospect, $sourceLabel)));
    }
}
