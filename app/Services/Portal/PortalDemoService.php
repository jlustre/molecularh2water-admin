<?php

namespace App\Services\Portal;

use App\Enums\Crm\DemonstrationStatus;
use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\Customer;
use App\Models\Crm\Demonstration;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use App\Services\Crm\DemonstrationService;
use App\Services\Crm\DashboardStatsService;
use App\Services\Crm\LeadService;
use App\Support\Crm\CrmScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PortalDemoService
{
    /**
     * @return Collection<int, Demonstration>
     */
    public function upcomingDemos(?User $user = null, int $limit = 25): Collection
    {
        $user ??= auth()->user();

        if (! $user || ! Schema::hasTable('demonstrations')) {
            return collect();
        }

        return Demonstration::query()
            ->with(['lead', 'demonstrator', 'calendarEvent.type'])
            ->forAccessibleContacts($user)
            ->whereIn('status', [
                DemonstrationStatus::Scheduled->value,
                DemonstrationStatus::Confirmed->value,
            ])
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->where('scheduled_at', '<=', now()->addDays(14))
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Lead|Prospect|Customer|Recruit>
     */
    public function leadOptions(?User $user = null): Collection
    {
        $user ??= auth()->user();

        if (! $user) {
            return collect();
        }

        return $this->demoContactClasses()
            ->flatMap(function (string $class) use ($user) {
                return CrmScope::contacts($class::query(), $user)
                    ->orderBy('first_name')
                    ->orderBy('last_name')
                    ->limit(250)
                    ->get(['id', 'first_name', 'last_name', 'email', 'company']);
            })
            ->sortBy(fn (Model $contact) => strtolower($contact->fullName()))
            ->values()
            ->take(250);
    }

    /**
     * @param  array{
     *     lead_id: int,
     *     type: string,
     *     demo_when: string,
     *     duration_minutes?: int,
     *     venue?: string|null,
     *     notes?: string|null,
     *     contact_email?: string|null
     * }  $data
     */
    public function schedule(array $data, User $actor): Demonstration
    {
        $lead = filled($data['contact_type'] ?? null)
            ? $this->findContactByTypeAndId((string) $data['contact_type'], (int) $data['lead_id'], $actor)
            : $this->findContactById((int) $data['lead_id'], $actor);

        if (! $lead) {
            throw ValidationException::withMessages([
                'lead_id' => 'Please choose a valid contact from the list.',
            ]);
        }

        [$scheduledAt, $durationMinutes] = $this->resolveDemoWindow(
            $data['demo_when'],
            (int) ($data['duration_minutes'] ?? 60),
        );

        $demo = $this->withSyncAutomation(fn () => app(DemonstrationService::class)->schedule($lead, [
            'type' => $data['type'],
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $durationMinutes,
            'venue' => filled($data['venue'] ?? null) ? trim((string) $data['venue']) : null,
            'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
        ], $actor));

        app(DashboardStatsService::class)->notifyChanged($actor);

        $contactEmail = filled($data['contact_email'] ?? null) ? trim((string) $data['contact_email']) : null;

        if ($contactEmail) {
            if (! filled($lead->email)) {
                $lead->update(['email' => $contactEmail]);
            }

            app(DemoInviteMailService::class)->send($demo, $lead, $actor, $contactEmail);
        }

        return $demo;
    }

    /**
     * @return Collection<int, Lead|Prospect|Customer|Recruit>
     */
    public function searchContacts(string $query, ?User $user = null, int $limit = 8): Collection
    {
        $user ??= auth()->user();
        $term = trim($query);

        if (! $user || strlen($term) < 3) {
            return collect();
        }

        $like = '%'.$term.'%';

        return $this->searchableContactClasses()
            ->flatMap(function (string $class) use ($user, $like, $limit) {
                return CrmScope::contacts($class::query(), $user)
                    ->where(function ($builder) use ($like) {
                        $builder->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like);
                    })
                    ->orderBy('first_name')
                    ->orderBy('last_name')
                    ->limit($limit)
                    ->get(['id', 'first_name', 'last_name', 'email', 'phone']);
            })
            ->sortBy(fn (Model $contact) => strtolower($contact->fullName()))
            ->values()
            ->take($limit);
    }

    public function findContactMatch(string $name, ?User $user = null): Lead|Prospect|Customer|Recruit|null
    {
        $user ??= auth()->user();
        $normalized = strtolower(trim($name));

        if (! $user || $normalized === '') {
            return null;
        }

        return $this->searchableContactClasses()
            ->map(function (string $class) use ($user, $normalized) {
                return CrmScope::contacts($class::query(), $user)
                    ->get(['id', 'first_name', 'last_name', 'email', 'phone'])
                    ->first(fn (Model $contact) => strtolower($contact->fullName()) === $normalized);
            })
            ->filter()
            ->first();
    }

    /**
     * @param  array{
     *     first_name: string,
     *     last_name?: string|null,
     *     email?: string|null,
     *     phone?: string|null
     * }  $data
     */
    public function createProspect(array $data, User $actor): Lead|Prospect|Customer|Recruit
    {
        $lead = app(LeadService::class)->create([
            'first_name' => trim($data['first_name']),
            'last_name' => filled($data['last_name'] ?? null) ? trim((string) $data['last_name']) : null,
            'email' => filled($data['email'] ?? null) ? trim((string) $data['email']) : null,
            'phone' => filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
            'lifecycle' => LeadLifecycle::Lead->value,
        ], $actor);

        app(DashboardStatsService::class)->notifyChanged($actor);

        return $lead;
    }

    public function contactById(int $leadId, ?User $user = null): Lead|Prospect|Customer|Recruit|null
    {
        $user ??= auth()->user();

        if (! $user) {
            return null;
        }

        return $this->findContactById($leadId, $user, $this->searchableContactClasses()->all());
    }

    public function leadEmailForContact(?int $leadId, ?User $user = null): ?string
    {
        $lead = $leadId ? $this->contactById($leadId, $user) : null;

        return filled($lead?->email) ? (string) $lead->email : null;
    }

    /**
     * @return array{first_name: string, last_name: string}
     */
    public function parseContactName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
        ];
    }

    /**
     * @param  list<class-string<Lead|Prospect|Customer|Recruit>>|null  $classes
     */
    private function findContactById(int $id, User $actor, ?array $classes = null): Lead|Prospect|Customer|Recruit|null
    {
        foreach ($classes ?? $this->demoContactClasses()->all() as $class) {
            $contact = CrmScope::contacts($class::query(), $actor)->find($id);

            if ($contact) {
                return $contact;
            }
        }

        return null;
    }

    private function findContactByTypeAndId(string $type, int $id, User $actor): Lead|Prospect|Customer|Recruit|null
    {
        $class = match ($type) {
            'lead' => Lead::class,
            'prospect' => Prospect::class,
            'customer' => Customer::class,
            'recruit' => Recruit::class,
            default => null,
        };

        if (! $class) {
            return null;
        }

        return CrmScope::contacts($class::query(), $actor)->find($id);
    }

    /**
     * Prefer prospect/customer tables first — demos are usually booked against those,
     * and contact ids are not unique across morph types.
     *
     * @return Collection<int, class-string<Lead|Prospect|Customer|Recruit>>
     */
    private function demoContactClasses(): Collection
    {
        return collect([Prospect::class, Customer::class, Lead::class, Recruit::class]);
    }

    /**
     * @return Collection<int, class-string<Lead|Prospect|Customer>>
     */
    private function searchableContactClasses(): Collection
    {
        return collect([Lead::class, Prospect::class, Customer::class]);
    }

    /**
     * @return array{0: \Illuminate\Support\Carbon, 1: int}
     */
    private function resolveDemoWindow(string $preset, int $durationMinutes): array
    {
        $start = match ($preset) {
            'in_60' => now()->addHour()->ceilMinutes(15),
            'today_14' => now()->setTime(14, 0),
            'today_16' => now()->setTime(16, 0),
            'tomorrow_10' => now()->addDay()->setTime(10, 0),
            'tomorrow_14' => now()->addDay()->setTime(14, 0),
            'next_week' => now()->addWeek()->next('Monday')->setTime(10, 0),
            default => now()->addDay()->setTime(10, 0),
        };

        if ($start->isPast()) {
            $start = now()->addHour()->ceilMinutes(15);
        }

        return [$start, $durationMinutes];
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    private function withSyncAutomation(callable $callback): mixed
    {
        $previous = config('crm.automation.sync');

        config(['crm.automation.sync' => true]);

        try {
            return $callback();
        } finally {
            config(['crm.automation.sync' => $previous]);
        }
    }
}
