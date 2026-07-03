<?php

namespace App\Livewire\Portal;

use App\Services\Crm\DashboardStatsService;
use App\Services\Portal\PortalDashboardService;
use App\Support\Portal\PortalRoleLabel;
use Livewire\Component;

class Dashboard extends Component
{
    public function getListeners(): array
    {
        return array_merge(parent::getListeners(), [
            'business-line-changed' => '$refresh',
            'crm-dashboard-refresh' => '$refresh',
            'phone-call-scheduled' => '$refresh',
            'meeting-scheduled' => '$refresh',
            'task-created' => '$refresh',
            'demo-scheduled' => '$refresh',
            'prospect-created' => '$refresh',
            'appointment-scheduled' => '$refresh',
            'invite-created' => '$refresh',
            'referral-created' => '$refresh',
        ]);
    }

    public function render(
        PortalDashboardService $dashboard,
        DashboardStatsService $crmStats,
    ) {
        $user = auth()->user();

        $crmDetail = $user?->hasPermission('leads.view')
            ? $crmStats->get($user)
            : null;

        return view('livewire.portal.dashboard', [
            'sections' => $dashboard->sections($user),
            'roleLabel' => PortalRoleLabel::for($user),
            'headline' => PortalRoleLabel::headlineFor($user),
            'crmDetail' => $crmDetail,
            'showPipeline' => (bool) $user?->hasPermission('pipeline.view'),
            'showEvents' => (bool) $user?->hasPermission('calendar.view'),
            'showTasks' => (bool) $user?->hasPermission('tasks.view'),
            'showActivities' => (bool) $user?->hasPermission('activities.view'),
        ])->layout('layouts.portal', ['header' => 'Dashboard']);
    }
}
