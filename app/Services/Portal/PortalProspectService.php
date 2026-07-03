<?php

namespace App\Services\Portal;

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\Prospect;
use App\Models\User;
use App\Services\Crm\DashboardStatsService;
use App\Services\Crm\LeadService;
use App\Support\Crm\CrmScope;
use Illuminate\Support\Collection;

class PortalProspectService
{
    /**
     * @return Collection<int, Prospect>
     */
    public function recentProspects(?User $user = null, int $limit = 30): Collection
    {
        $user ??= auth()->user();

        if (! $user) {
            return collect();
        }

        return CrmScope::contacts(Prospect::query(), $user)
            ->with(['stage', 'source', 'assignedUser'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
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
    public function create(array $data, User $actor): Prospect
    {
        /** @var Prospect $prospect */
        $prospect = app(LeadService::class)->create([
            'first_name' => trim($data['first_name']),
            'last_name' => filled($data['last_name'] ?? null) ? trim((string) $data['last_name']) : null,
            'email' => filled($data['email'] ?? null) ? trim((string) $data['email']) : null,
            'phone' => filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
            'company' => filled($data['company'] ?? null) ? trim((string) $data['company']) : null,
            'message' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
            'lifecycle' => LeadLifecycle::Prospect->value,
        ], $actor);

        app(DashboardStatsService::class)->notifyChanged($actor);

        return $prospect;
    }
}
