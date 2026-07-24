<?php

namespace App\Livewire\Crm;

use App\Models\Crm\ConsultantPerformanceDaily;
use App\Models\User;
use App\Services\Crm\ConsultantPerformanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class ConsultantPerformancePanel extends Component
{
    public string $focusDate = '';

    public ?int $subjectUserId = null;

    public bool $lockSubject = false;

    public function mount(?int $subjectUserId = null, bool $lockSubject = false): void
    {
        $actor = auth()->user();

        abort_unless(
            $actor?->hasPermission('activities.view')
                || $actor?->hasPermission('calendar.view')
                || ($lockSubject && app(\App\Services\MemberOverviewAccess::class)->canBrowse($actor)),
            403,
        );

        $this->lockSubject = $lockSubject;
        $this->focusDate = now()->toDateString();
        $this->subjectUserId = $subjectUserId ?: Auth::id();
    }

    public function goToday(): void
    {
        $this->focusDate = now()->toDateString();
    }

    public function updatedFocusDate(): void
    {
        if (blank($this->focusDate) || ! strtotime($this->focusDate)) {
            $this->focusDate = now()->toDateString();
        } else {
            $this->focusDate = Carbon::parse($this->focusDate)->toDateString();
        }
    }

    public function updatedSubjectUserId(): void
    {
        if ($this->lockSubject) {
            return;
        }

        $actor = Auth::user();
        $subject = $this->resolveSubject();

        if ($actor && $subject) {
            app(ConsultantPerformanceService::class)->assertCanViewSubject($actor, $subject);
            $this->dispatch('performance-subject-changed', subjectUserId: $subject->id);
        }
    }

    #[On('performance-subject-changed')]
    public function syncSubject(?int $subjectUserId): void
    {
        if ($this->lockSubject) {
            return;
        }

        if ($subjectUserId && $subjectUserId !== $this->subjectUserId) {
            $this->subjectUserId = $subjectUserId;
        }
    }

    public function increment(string $metric, ConsultantPerformanceService $performance): void
    {
        $this->adjust($metric, 1, $performance);
    }

    public function decrement(string $metric, ConsultantPerformanceService $performance): void
    {
        $this->adjust($metric, -1, $performance);
    }

    public function render(ConsultantPerformanceService $performance)
    {
        $actor = Auth::user();
        $subject = $this->resolveSubject();
        $performance->assertCanViewSubject($actor, $subject);

        $focus = Carbon::parse($this->focusDate ?: now()->toDateString());
        [$start, $end] = $performance->periodRange('day', $focus);
        $totals = $performance->totalsFor($subject, $start, $end);
        $canEdit = $performance->canManageSubject($actor, $subject);
        $canPick = ! $this->lockSubject && $performance->canPickSubjects($actor);
        $subjects = $canPick ? $performance->selectableSubjects($actor) : collect([$actor]);

        return view('livewire.crm.consultant-performance-panel', [
            'totals' => $totals,
            'labels' => ConsultantPerformanceDaily::metricLabels(),
            'canEdit' => $canEdit,
            'canPick' => $canPick,
            'subjects' => $subjects,
            'subject' => $subject,
            'selectedDateLabel' => $focus->format('l, M j, Y'),
        ]);
    }

    private function adjust(string $metric, int $delta, ConsultantPerformanceService $performance): void
    {
        $actor = Auth::user();
        abort_unless($actor, 403);

        $subject = $this->resolveSubject();
        $forDate = Carbon::parse($this->focusDate ?: now()->toDateString());

        $performance->adjust($actor, $subject, $metric, $delta, $forDate);
        $this->dispatch('performance-counters-updated');
    }

    private function resolveSubject(): User
    {
        $actor = Auth::user();
        $id = $this->subjectUserId ?: $actor?->id;

        return User::query()->findOrFail($id);
    }
}
