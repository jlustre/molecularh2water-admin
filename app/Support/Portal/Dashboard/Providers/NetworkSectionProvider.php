<?php

namespace App\Support\Portal\Dashboard\Providers;

use App\Contracts\Portal\PortalDashboardSectionProvider;
use App\Models\User;
use App\Services\Crm\DashboardStatsService;
use App\Services\Portal\PhoneCallService;
use App\Support\Crm\CrmRoutes;
use App\Support\Portal\Dashboard\PortalDashboardCard;
use App\Support\Portal\Dashboard\PortalDashboardSection;
use App\Support\Portal\PortalRainbowTone;

class NetworkSectionProvider implements PortalDashboardSectionProvider
{
    public function __construct(
        private DashboardStatsService $stats,
        private PhoneCallService $phoneCalls,
    ) {}

    public function priority(): int
    {
        return 20;
    }

    public function section(User $user): ?PortalDashboardSection
    {
        $week = $this->stats->weeklyGrowth(
            $user,
            $this->phoneCalls->phoneCallTypeSlugs(),
        );

        $weekLabel = $week['weekStart']->format('M j').' – '.$week['weekEnd']->format('M j');
        $cards = [];

        if ($user->hasPermission('sponsors.view-tree')) {
            $cards[] = $this->card(
                'Team Members',
                $week['teamMembers'],
                'New direct recruits this week',
                route('portal.team'),
                'teal',
                'users',
            );
        }

        if ($user->hasPermission('prospects.view')) {
            $cards[] = $this->card(
                'Prospects',
                $week['prospects'],
                'New prospects this week',
                null,
                PortalRainbowTone::forAction('open-prospects'),
                'user-plus',
                'open-prospects',
            );
        }

        if ($user->hasPermission('leads.view') || $user->hasPermission('activities.view')) {
            $cards[] = $this->card(
                'Invites',
                $week['invites'],
                'Prospects invited to a presentation this week',
                null,
                'violet',
                'ticket',
            );
        }

        if ($user->hasPermission('leads.view')) {
            $leadsRoute = route(CrmRoutes::name('leads.index'));

            $cards[] = $this->card(
                'Leads',
                $week['leads'],
                'New leads this week',
                $leadsRoute,
                'cyan',
                'sparkles',
            );

            $cards[] = $this->card(
                'Follow-Ups',
                $week['followUps'],
                'Follow-ups due this week',
                $leadsRoute,
                'orange',
                'bell',
            );

            $cards[] = $this->card(
                'Schedule Presentations',
                $week['schedulePresentations'],
                'Presentations scheduled this week',
                null,
                PortalRainbowTone::forAction('open-demos'),
                'calendar',
                'open-demos',
            );

            $cards[] = $this->card(
                'Actual Presentation',
                $week['presentations'],
                'Presentations completed this week',
                null,
                'yellow',
                'play',
            );

            $cards[] = $this->card(
                'Closed Sales',
                $week['closedSales'],
                'Won opportunities this week',
                $leadsRoute,
                'emerald',
                'chart',
            );
        }

        if ($user->hasPermission('sales.view') || $user->hasPermission('sales.manage')) {
            $cards[] = $this->card(
                'Completed Sales',
                $week['completedSales'],
                'Member sales completed this week',
                route(CrmRoutes::name('sales.index')),
                'green',
                'check',
            );
        }

        if ($user->hasPermission('calendar.view')) {
            $cards[] = $this->card(
                'Phone Calls',
                $week['phoneCalls'],
                'Phone calls this week',
                null,
                PortalRainbowTone::forAction('open-phone-calls'),
                'bell',
                'open-phone-calls',
            );
        }

        if ($user->hasPermission('appointments.view')) {
            $cards[] = $this->card(
                'Appointments',
                $week['appointments'],
                'Appointments this week',
                null,
                PortalRainbowTone::forAction('open-appointments'),
                'calendar',
                'open-appointments',
            );
        }

        if ($user->hasPermission('tasks.view')) {
            $cards[] = $this->card(
                'Tasks',
                $week['tasks'],
                'Tasks due or created this week',
                null,
                PortalRainbowTone::forAction('open-tasks'),
                'check',
                'open-tasks',
            );
        }

        if ($cards === []) {
            return null;
        }

        return new PortalDashboardSection(
            key: 'network',
            title: 'Network & Growth This Week',
            description: 'Weekly network, pipeline, and field activity ('.$weekLabel.').',
            priority: $this->priority(),
            cards: $cards,
        );
    }

    private function card(
        string $label,
        int|float|string $value,
        string $hint,
        ?string $route,
        string $tone,
        string $icon,
        ?string $action = null,
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
            action: $action,
        );
    }
}
