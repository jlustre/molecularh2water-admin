<?php

namespace App\Services\Portal;

use App\Contracts\Portal\PortalDashboardSectionProvider;
use App\Models\User;
use App\Support\Portal\Dashboard\PortalDashboardSection;
use App\Support\Portal\PortalNavigation;

class PortalDashboardService
{
    /**
     * @return list<PortalDashboardSection>
     */
    public function sections(User $user): array
    {
        $sections = [];

        foreach ($this->providers() as $provider) {
            $section = $provider->section($user);

            if ($section !== null && $section->cards !== []) {
                $sections[] = $section;
            }
        }

        usort(
            $sections,
            fn (PortalDashboardSection $a, PortalDashboardSection $b) => $a->priority <=> $b->priority,
        );

        return $sections;
    }

    /**
     * @return list<array{label: string, route: string, description: string}>
     */
    public function quickLinks(User $user): array
    {
        $preferred = [
            'admin.dashboard' => 'Admin control panel',
        ];

        $links = collect(PortalNavigation::links($user))
            ->keyBy('route')
            ->filter(fn (array $link) => $link['route'] !== 'dashboard');

        $ordered = [];

        foreach ($preferred as $route => $description) {
            $link = $links->get($route);

            if ($link) {
                $ordered[] = [
                    'label' => $link['label'],
                    'route' => $link['href'],
                    'description' => $description,
                ];
            }
        }

        return array_slice($ordered, 0, 6);
    }

    /**
     * @return list<array{type: string, label: string, href?: string, navigate?: bool, action?: string, tone?: string}>
     */
    public function quickActions(User $user): array
    {
        $actions = array_map(fn (array $link) => [
            'type' => 'link',
            'label' => $link['label'],
            'href' => $link['route'],
            'description' => $link['description'],
            'navigate' => ! str_contains($link['route'], '/admin'),
        ], $this->quickLinks($user));

        if ($user->hasPermission('invites.manage')) {
            $actions[] = [
                'type' => 'modal',
                'label' => 'Member Invites',
                'action' => 'openMemberInvites',
                'tone' => 'emerald',
            ];
        }

        if ($user->hasPermission('prospects.view')) {
            $actions[] = [
                'type' => 'modal',
                'label' => 'Prospects',
                'action' => 'openProspects',
                'tone' => 'rose',
            ];
        }

        if ($user->hasPermission('leads.view')) {
            $actions[] = [
                'type' => 'modal',
                'label' => 'Demos',
                'action' => 'openDemos',
                'tone' => 'violet',
            ];
        }

        if ($user->hasPermission('calendar.view')) {
            $actions[] = [
                'type' => 'modal',
                'label' => 'Phone Calls',
                'action' => 'openPhoneCalls',
                'tone' => 'blue',
            ];
            $actions[] = [
                'type' => 'modal',
                'label' => 'Meetings',
                'action' => 'openMeetings',
                'tone' => 'indigo',
            ];
        }

        if ($user->hasPermission('appointments.view')) {
            $actions[] = [
                'type' => 'modal',
                'label' => 'Appointments',
                'action' => 'openAppointments',
                'tone' => 'cyan',
            ];
        }

        if ($user->hasPermission('tasks.view')) {
            $actions[] = [
                'type' => 'modal',
                'label' => 'Tasks',
                'action' => 'openTasks',
                'tone' => 'amber',
            ];
        }

        if ($user->hasPermission('clients.view')) {
            $actions[] = [
                'type' => 'modal',
                'label' => 'Referrals',
                'action' => 'openReferrals',
                'tone' => 'orange',
            ];
        }

        return $actions;
    }

    public function hasQuickActions(User $user): bool
    {
        return $this->quickActions($user) !== [];
    }

    /**
     * @return list<PortalDashboardSectionProvider>
     */
    private function providers(): array
    {
        return collect(config('portal.dashboard_section_providers', []))
            ->map(fn (string $class) => app($class))
            ->filter(fn ($provider) => $provider instanceof PortalDashboardSectionProvider)
            ->values()
            ->all();
    }
}
