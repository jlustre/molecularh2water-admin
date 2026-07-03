<?php

namespace App\Support\Portal\Dashboard\Providers;

use App\Contracts\Portal\PortalDashboardSectionProvider;
use App\Models\Crm\Task;
use App\Models\User;
use App\Services\Crm\DashboardStatsService;
use App\Support\Crm\CrmRoutes;
use App\Support\Crm\CrmScope;
use App\Support\Portal\Dashboard\PortalDashboardCard;
use App\Support\Portal\Dashboard\PortalDashboardSection;
use Illuminate\Support\Facades\Schema;

class CrmMetricsSectionProvider implements PortalDashboardSectionProvider
{
    public function __construct(
        private DashboardStatsService $stats,
    ) {}

    public function priority(): int
    {
        return 40;
    }

    public function section(User $user): ?PortalDashboardSection
    {
        if (! $user->hasPermission('leads.view') || ! Schema::hasTable('leads')) {
            return null;
        }

        $metrics = $this->stats->get($user);
        $openTasks = Schema::hasTable('tasks')
            ? CrmScope::tasks(Task::query(), $user)
                ->whereIn('status', ['pending', 'in_progress'])
                ->count()
            : 0;

        $cards = $this->cardsForRole($user, $metrics, $openTasks);

        if ($cards === []) {
            return null;
        }

        return new PortalDashboardSection(
            key: 'crm',
            title: $this->titleFor($user),
            description: $this->descriptionFor($user),
            priority: $this->priority(),
            cards: $cards,
        );
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return list<PortalDashboardCard>
     */
    private function cardsForRole(User $user, array $metrics, int $openTasks): array
    {
        $leadsRoute = $user->hasPermission('leads.view')
            ? route(CrmRoutes::name('leads.index'))
            : null;

        if ($user->canViewAllCrmRecords() || $user->hasRole(['admin', 'team-admin'])) {
            return [
                $this->numericCard('Total Leads', $metrics['totalLeads'], 'Across the organization', $leadsRoute, 'users', 'teal'),
                $this->numericCard('New Leads (7d)', $metrics['newLeads'], 'Captured this week', $leadsRoute, 'sparkles', 'cyan'),
                $this->numericCard('Follow-Ups Today', $metrics['followUpsDueToday'], 'Due for outreach today', $leadsRoute, 'bell', 'amber'),
                $this->numericCard('Closed Sales (Month)', $metrics['closedSales'], 'Won opportunities this month', $leadsRoute, 'chart', 'emerald'),
            ];
        }

        if ($user->hasPermission('crm.records.view-team') || $user->hasRole('manager')) {
            return [
                $this->numericCard('Team Leads', $metrics['totalLeads'], 'Visible in your manager scope', $leadsRoute, 'users', 'teal'),
                $this->numericCard('Hot Prospects', $metrics['hotProspects'], 'High-intent contacts', $leadsRoute, 'fire', 'amber'),
                $this->numericCard('Follow-Ups Today', $metrics['followUpsDueToday'], 'Needs attention today', $leadsRoute, 'bell', 'cyan'),
                $this->numericCard('Conversion Rate', $metrics['conversionRate'].'%', 'Month-to-date close rate', $user->hasPermission('reports.view') ? route(CrmRoutes::name('reports.index')) : null, 'chart', 'emerald'),
            ];
        }

        return [
            $this->numericCard('My Leads', $metrics['totalLeads'], 'Assigned to you', $leadsRoute, 'users', 'teal'),
            $this->numericCard('Follow-Ups Today', $metrics['followUpsDueToday'], 'Due for outreach today', $leadsRoute, 'bell', 'amber'),
            $this->numericCard('Demos Today', $metrics['demosToday'], 'On today’s calendar', $user->hasPermission('calendar.view') ? route(CrmRoutes::name('calendar.index')) : null, 'calendar', 'cyan'),
            $this->numericCard('Open Tasks', $openTasks, 'Pending or in progress', $user->hasPermission('tasks.view') ? route(CrmRoutes::name('tasks.index')) : null, 'check', 'indigo'),
        ];
    }

    private function numericCard(
        string $label,
        int|float|string $value,
        string $hint,
        ?string $route,
        string $icon,
        string $tone,
    ): PortalDashboardCard {
        $formatted = is_numeric($value)
            ? number_format((float) $value, is_float($value) && floor($value) != $value ? 1 : 0)
            : (string) $value;

        return new PortalDashboardCard(
            label: $label,
            value: $formatted,
            hint: $hint,
            route: $route,
            tone: $tone,
            icon: $icon,
        );
    }

    private function titleFor(User $user): string
    {
        return match (true) {
            $user->canViewAllCrmRecords() => 'Organization CRM',
            $user->hasRole('manager') => 'Team CRM Performance',
            default => 'My CRM Snapshot',
        };
    }

    private function descriptionFor(User $user): string
    {
        return match (true) {
            $user->canViewAllCrmRecords() => 'Executive-level pipeline and activity metrics.',
            $user->hasRole('manager') => 'Team-scoped leads, prospects, and follow-up pressure.',
            default => 'Personal leads, demos, and tasks for today’s field work.',
        };
    }
}
