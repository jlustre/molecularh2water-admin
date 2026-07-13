<?php

namespace App\Services\Portal;

use App\Contracts\Portal\PortalDashboardSectionProvider;
use App\Models\User;
use App\Support\Portal\Dashboard\PortalDashboardSection;
use App\Support\Portal\PortalRainbowTone;

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
        return [];
    }

    /**
     * @return list<array{type: string, label: string, description: string, href?: string, navigate?: bool, action?: string, tone?: string}>
     */
    public function quickActions(User $user): array
    {
        $actions = [];

        if ($user->hasPermission('invites.manage')) {
            $actions[] = [
                'type' => 'modal',
                'label' => 'Member Invites',
                'description' => 'Create and share registration invite codes when you want to add a new member under your sponsorship.',
                'action' => 'openMemberInvites',
                'tone' => PortalRainbowTone::forAction('openMemberInvites'),
            ];
        }

        if ($user->hasPermission('prospects.view')) {
            $actions[] = [
                'type' => 'modal',
                'label' => 'Prospects',
                'description' => 'Add or update a prospect when someone shows interest and you want to track them before they become a lead or customer.',
                'action' => 'openProspects',
                'tone' => PortalRainbowTone::forAction('openProspects'),
            ];
        }

        if ($user->hasPermission('leads.view')) {
            $actions[] = [
                'type' => 'modal',
                'label' => 'Demos',
                'description' => 'Schedule a product or water-awareness demo when a contact is ready to see the system in action.',
                'action' => 'openDemos',
                'tone' => PortalRainbowTone::forAction('openDemos'),
            ];
        }

        if ($user->hasPermission('calendar.view')) {
            $actions[] = [
                'type' => 'modal',
                'label' => 'Phone Calls',
                'description' => 'Log or schedule a phone call for follow-ups, check-ins, and outreach you need to complete by phone.',
                'action' => 'openPhoneCalls',
                'tone' => PortalRainbowTone::forAction('openPhoneCalls'),
            ];
            $actions[] = [
                'type' => 'modal',
                'label' => 'Meetings',
                'description' => 'Book an in-person or online meeting when you need a longer conversation, planning session, or team discussion.',
                'action' => 'openMeetings',
                'tone' => PortalRainbowTone::forAction('openMeetings'),
            ];
        }

        if ($user->hasPermission('appointments.view')) {
            $actions[] = [
                'type' => 'modal',
                'label' => 'Appointments',
                'description' => 'Set an appointment when a contact has a confirmed date and time for a visit, consultation, or delivery.',
                'action' => 'openAppointments',
                'tone' => PortalRainbowTone::forAction('openAppointments'),
            ];
        }

        if ($user->hasPermission('tasks.view')) {
            $actions[] = [
                'type' => 'modal',
                'label' => 'Tasks',
                'description' => 'Create a personal or CRM task for reminders, to-dos, and action items you do not want to forget.',
                'action' => 'openTasks',
                'tone' => PortalRainbowTone::forAction('openTasks'),
            ];
        }

        if ($user->hasPermission('clients.view')) {
            $actions[] = [
                'type' => 'modal',
                'label' => 'Referrals/Leads',
                'description' => 'Record a referral or lead introduction when a client or contact brings someone new, so you can track credit and follow-up.',
                'action' => 'openReferrals',
                'tone' => PortalRainbowTone::forAction('openReferrals'),
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
