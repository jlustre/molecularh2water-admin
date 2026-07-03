<?php

namespace App\Livewire\Portal;

use App\Services\Portal\PortalDashboardService;
use Livewire\Component;

class QuickLinks extends Component
{
    public function getListeners(): array
    {
        return [
            'demo-scheduled' => 'refreshDashboard',
            'phone-call-scheduled' => 'refreshDashboard',
            'meeting-scheduled' => 'refreshDashboard',
            'task-created' => 'refreshDashboard',
            'prospect-created' => 'refreshDashboard',
            'appointment-scheduled' => 'refreshDashboard',
            'referral-created' => 'refreshDashboard',
        ];
    }

    public function refreshDashboard(): void
    {
        $this->dispatch('crm-dashboard-refresh');
    }

    public function openMemberInvites(): void
    {
        $this->dispatch('open-member-invites');
    }

    public function openProspects(): void
    {
        $this->dispatch('open-prospects');
    }

    public function openDemos(): void
    {
        $this->dispatch('open-demos');
    }

    public function openPhoneCalls(): void
    {
        $this->dispatch('open-phone-calls');
    }

    public function openMeetings(): void
    {
        $this->dispatch('open-meetings');
    }

    public function openTasks(): void
    {
        $this->dispatch('open-tasks');
    }

    public function openAppointments(): void
    {
        $this->dispatch('open-appointments');
    }

    public function openReferrals(): void
    {
        $this->dispatch('open-referrals');
    }

    public function render(PortalDashboardService $dashboard)
    {
        $user = auth()->user();

        return view('livewire.portal.quick-links', [
            'actions' => $dashboard->quickActions($user),
            'hasActions' => $dashboard->hasQuickActions($user),
        ]);
    }
}
