<?php

namespace App\Support\Portal\Dashboard\Providers;

use App\Contracts\Portal\PortalDashboardSectionProvider;
use App\Models\Crm\Appointment;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\Prospect;
use App\Models\Crm\Referral;
use App\Models\Crm\Task;
use App\Models\RegistrationInvite;
use App\Models\User;
use App\Services\Crm\DashboardStatsService;
use App\Services\Portal\MeetingService;
use App\Services\Portal\PhoneCallService;
use App\Support\Crm\CalendarScope;
use App\Support\Crm\CrmScope;
use App\Support\Portal\Dashboard\PortalDashboardCard;
use App\Support\Portal\Dashboard\PortalDashboardSection;
use App\Support\Portal\PortalRainbowTone;
use Illuminate\Support\Facades\Schema;

class NetworkSectionProvider implements PortalDashboardSectionProvider
{
    public function __construct(
        private DashboardStatsService $stats,
        private PhoneCallService $phoneCalls,
        private MeetingService $meetings,
    ) {}

    public function priority(): int
    {
        return 20;
    }

    public function section(User $user): ?PortalDashboardSection
    {
        $cards = [];
        $metrics = $user->hasPermission('leads.view') && Schema::hasTable('leads')
            ? $this->stats->get($user)
            : null;

        if ($user->hasPermission('sponsors.view-tree')) {
            $cards[] = new PortalDashboardCard(
                label: 'Team Members',
                value: (string) $user->sponsoredUsers()->count(),
                hint: 'Direct members in your sponsor tree',
                route: route('portal.team'),
                tone: 'teal',
                icon: 'users',
            );
        }

        if ($user->hasPermission('invites.manage')) {
            $activeInvites = RegistrationInvite::query()
                ->where('sponsor_id', $user->id)
                ->available()
                ->count();

            $cards[] = new PortalDashboardCard(
                label: 'Member Invites',
                value: (string) $activeInvites,
                hint: 'Active codes ready to share',
                tone: PortalRainbowTone::forAction('open-member-invites'),
                icon: 'ticket',
                action: 'open-member-invites',
            );
        }

        if ($user->hasPermission('prospects.view') && Schema::hasTable('prospects')) {
            $prospectCount = $metrics['activeProspects']
                ?? CrmScope::contacts(Prospect::query(), $user)->count();

            $cards[] = new PortalDashboardCard(
                label: 'Prospects',
                value: number_format((int) $prospectCount),
                hint: 'Prospects in your workspace',
                tone: PortalRainbowTone::forAction('open-prospects'),
                icon: 'user-plus',
                action: 'open-prospects',
            );
        }

        if ($user->hasPermission('leads.view')) {
            $demoCount = (int) ($metrics['demosToday'] ?? 0);

            $cards[] = new PortalDashboardCard(
                label: 'Demos',
                value: number_format($demoCount),
                hint: 'Demos scheduled for today',
                tone: PortalRainbowTone::forAction('open-demos'),
                icon: 'play',
                action: 'open-demos',
            );
        }

        if ($user->hasPermission('calendar.view') && Schema::hasTable('calendar_events')) {
            $cards[] = new PortalDashboardCard(
                label: 'Phone Calls',
                value: number_format($this->upcomingEventCount($user, $this->phoneCalls->phoneCallTypeSlugs())),
                hint: 'Upcoming phone calls',
                tone: PortalRainbowTone::forAction('open-phone-calls'),
                icon: 'bell',
                action: 'open-phone-calls',
            );

            $cards[] = new PortalDashboardCard(
                label: 'Meetings',
                value: number_format($this->upcomingEventCount($user, $this->meetings->meetingTypeSlugs())),
                hint: 'Upcoming meetings',
                tone: PortalRainbowTone::forAction('open-meetings'),
                icon: 'users',
                action: 'open-meetings',
            );
        }

        if ($user->hasPermission('appointments.view') && Schema::hasTable('appointments')) {
            $appointmentCount = $metrics['appointmentsToday']
                ?? CrmScope::appointments(Appointment::query(), $user)
                    ->whereDate('starts_at', now()->toDateString())
                    ->count();

            $cards[] = new PortalDashboardCard(
                label: 'Appointments',
                value: number_format((int) $appointmentCount),
                hint: 'Appointments scheduled today',
                tone: PortalRainbowTone::forAction('open-appointments'),
                icon: 'calendar',
                action: 'open-appointments',
            );
        }

        if ($user->hasPermission('tasks.view') && Schema::hasTable('tasks')) {
            $openTasks = CrmScope::tasks(Task::query(), $user)
                ->whereIn('status', ['pending', 'in_progress'])
                ->count();

            $cards[] = new PortalDashboardCard(
                label: 'Tasks',
                value: number_format($openTasks),
                hint: 'Open tasks needing action',
                tone: PortalRainbowTone::forAction('open-tasks'),
                icon: 'check',
                action: 'open-tasks',
            );
        }

        if ($user->hasPermission('clients.view') && Schema::hasTable('referrals')) {
            $referralQuery = Referral::query();

            if (! $user->canViewAllCrmRecords()) {
                $referralQuery->where('user_id', $user->id);
            }

            $cards[] = new PortalDashboardCard(
                label: 'Referrals/Leads',
                value: number_format($referralQuery->count()),
                hint: 'Referrals and leads logged in CRM',
                tone: PortalRainbowTone::forAction('open-referrals'),
                icon: 'sparkles',
                action: 'open-referrals',
            );
        }

        if ($cards === []) {
            return null;
        }

        return new PortalDashboardSection(
            key: 'network',
            title: 'Network & Growth',
            description: 'Quick-action metrics with matching colors for your most-used portal tools.',
            priority: $this->priority(),
            cards: $cards,
        );
    }

    /**
     * @param  list<string>  $typeSlugs
     */
    private function upcomingEventCount(User $user, array $typeSlugs): int
    {
        if ($typeSlugs === []) {
            return 0;
        }

        return CalendarScope::events(CalendarEvent::query(), $user)
            ->whereHas('type', fn ($query) => $query->whereIn('slug', $typeSlugs))
            ->where('start_at', '>=', now())
            ->count();
    }
}
