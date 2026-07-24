<?php

namespace App\Services\Portal;

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\User;
use App\Services\Crm\DashboardStatsService;
use App\Services\Crm\LeadService;
use App\Support\Crm\CrmScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PortalProspectService
{
    /**
     * @return Collection<int, Lead|Prospect>
     */
    public function recentProspects(?User $user = null, int $limit = 30): Collection
    {
        $user ??= auth()->user();

        if (! $user) {
            return collect();
        }

        return collect([Lead::class, Prospect::class])
            ->flatMap(fn (string $class) => CrmScope::contacts($class::query(), $user)
                ->with(['stage', 'source', 'assignedUser'])
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get())
            ->sortByDesc(fn (Model $contact) => $contact->created_at?->timestamp ?? 0)
            ->values()
            ->take($limit);
    }

    /**
     * @param  array{
     *     first_name: string,
     *     last_name?: string|null,
     *     email?: string|null,
     *     phone?: string|null,
     *     company?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function create(array $data, User $actor): Lead|Prospect
    {
        $contact = app(LeadService::class)->create([
            'first_name' => trim($data['first_name']),
            'last_name' => filled($data['last_name'] ?? null) ? trim((string) $data['last_name']) : null,
            'email' => filled($data['email'] ?? null) ? trim((string) $data['email']) : null,
            'phone' => filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
            'company' => filled($data['company'] ?? null) ? trim((string) $data['company']) : null,
            'message' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
            'lifecycle' => LeadLifecycle::Lead->value,
        ], $actor);

        app(DashboardStatsService::class)->notifyChanged($actor);

        return $contact;
    }
}
