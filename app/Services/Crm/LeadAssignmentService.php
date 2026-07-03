<?php

namespace App\Services\Crm;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class LeadAssignmentService
{
    public function resolve(?string $strategy = null): ?int
    {
        $strategy ??= config('crm.landing_pages.assignment', 'none');

        return match ($strategy) {
            'round_robin' => $this->roundRobinAssignee(),
            default => null,
        };
    }

    private function roundRobinAssignee(): ?int
    {
        $userIds = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('slug', ['consultant', 'manager']);
            })
            ->orderBy('id')
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return null;
        }

        $index = (int) Cache::get('crm.lead_assignment.index', 0);
        $assigneeId = (int) $userIds[$index % $userIds->count()];
        Cache::forever('crm.lead_assignment.index', $index + 1);

        return $assigneeId;
    }
}
