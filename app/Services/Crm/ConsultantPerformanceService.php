<?php

namespace App\Services\Crm;

use App\Models\Crm\ConsultantPerformanceDaily;
use App\Models\User;
use App\Services\MemberOverviewAccess;
use App\Support\Crm\CrmScope;
use App\Support\Crm\TeamScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ConsultantPerformanceService
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodRange(string $period, Carbon $focus): array
    {
        $focus = $focus->copy()->startOfDay();

        return match ($period) {
            'week' => [$focus->copy()->startOfWeek(), $focus->copy()->endOfWeek()],
            'month' => [$focus->copy()->startOfMonth(), $focus->copy()->endOfMonth()],
            default => [$focus->copy()->startOfDay(), $focus->copy()->endOfDay()],
        };
    }

    /**
     * @return array<string, int>
     */
    public function totalsFor(User $subject, Carbon $periodStart, Carbon $periodEnd): array
    {
        $totals = array_fill_keys(ConsultantPerformanceDaily::metricKeys(), 0);

        $rows = ConsultantPerformanceDaily::query()
            ->where('user_id', $subject->id)
            ->whereDate('stat_date', '>=', $periodStart->toDateString())
            ->whereDate('stat_date', '<=', $periodEnd->toDateString())
            ->get(ConsultantPerformanceDaily::metricKeys());

        foreach ($rows as $row) {
            foreach (ConsultantPerformanceDaily::metricKeys() as $key) {
                $totals[$key] += (int) $row->{$key};
            }
        }

        return $totals;
    }

    public function adjust(
        User $actor,
        User $subject,
        string $metric,
        int $delta,
        ?Carbon $forDate = null,
    ): ConsultantPerformanceDaily {
        $this->assertCanManageSubject($actor, $subject);

        if (! in_array($metric, ConsultantPerformanceDaily::metricKeys(), true)) {
            throw ValidationException::withMessages([
                'metric' => 'Unknown performance metric.',
            ]);
        }

        $date = ($forDate ?? now())->copy()->startOfDay();
        $dateString = $date->toDateString();

        $row = ConsultantPerformanceDaily::query()
            ->where('user_id', $subject->id)
            ->whereDate('stat_date', $dateString)
            ->first();

        if (! $row) {
            $row = ConsultantPerformanceDaily::query()->create([
                'user_id' => $subject->id,
                'stat_date' => $dateString,
                ...array_fill_keys(ConsultantPerformanceDaily::metricKeys(), 0),
            ]);
        }

        $next = max(0, (int) $row->{$metric} + $delta);
        $row->update([$metric => $next]);

        return $row->fresh();
    }

    public function canManageSubject(User $actor, User $subject): bool
    {
        if ((int) $actor->id === (int) $subject->id) {
            return $actor->hasPermission('activities.manage')
                || $actor->hasPermission('calendar.manage');
        }

        if (! ($actor->hasPermission('activities.manage') || $actor->hasPermission('calendar.manage'))) {
            return false;
        }

        if (CrmScope::userCanViewAll($actor)) {
            return true;
        }

        if (! CrmScope::userCanViewTeam($actor)) {
            return false;
        }

        return TeamScope::visibleUserIds($actor)->contains((int) $subject->id);
    }

    public function assertCanManageSubject(User $actor, User $subject): void
    {
        if (! $this->canManageSubject($actor, $subject)) {
            abort(403, 'You cannot update performance counters for this consultant.');
        }
    }

    public function assertCanViewSubject(User $actor, User $subject): void
    {
        if ((int) $actor->id === (int) $subject->id) {
            return;
        }

        if (CrmScope::userCanViewAll($actor)) {
            return;
        }

        if (CrmScope::userCanViewTeam($actor) && TeamScope::visibleUserIds($actor)->contains((int) $subject->id)) {
            return;
        }

        if (app(MemberOverviewAccess::class)->canView($actor, $subject)) {
            return;
        }

        abort(403, 'You cannot view performance counters for this consultant.');
    }

    /**
     * Consultants a manager/admin may pick (includes self).
     *
     * @return Collection<int, User>
     */
    public function selectableSubjects(User $actor): Collection
    {
        if (CrmScope::userCanViewAll($actor)) {
            return User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['consultant', 'manager', 'team-admin', 'admin']))
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        if (CrmScope::userCanViewTeam($actor)) {
            $ids = TeamScope::visibleUserIds($actor);

            return User::query()
                ->whereIn('id', $ids)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return collect([$actor]);
    }

    public function canPickSubjects(User $actor): bool
    {
        return CrmScope::userCanViewAll($actor) || CrmScope::userCanViewTeam($actor);
    }

    /**
     * Date that +/- buttons write to for the current period view.
     */
    public function adjustDateForPeriod(string $period, Carbon $focus): Carbon
    {
        if ($period === 'day') {
            return $focus->copy()->startOfDay();
        }

        return now()->startOfDay();
    }
}
