<?php

namespace App\Livewire\Crm\Calendar;

use App\Enums\Crm\CalendarEventStatus;
use App\Livewire\Crm\Calendar\Concerns\BuildsCalendarFilters;
use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\CalendarEventType;
use App\Models\User;
use App\Support\Crm\CalendarScope;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class CalendarDashboard extends Component
{
    use BuildsCalendarFilters;
    use UsesCrmLayout;

    public string $view = 'month';

    public string $focusDate;

    public ?int $lead_id = null;

    public ?int $filter_user_id = null;

    public ?int $filter_event_type_id = null;

    public ?string $filter_status = null;

    public bool $filter_shows_only = false;

    public bool $show_tasks = true;

    public bool $show_appointments = true;

    public ?string $statusMessage = null;

    public function mount(?int $lead = null, ?string $date = null, ?string $view = null): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.view'), 403);

        $this->focusDate = $date ?: now()->toDateString();

        if ($lead) {
            $this->lead_id = $lead;
        }

        if ($view && in_array($view, $this->allowedViews(), true)) {
            $this->view = $view;
        }
    }

    public function setView(string $view): void
    {
        if (in_array($view, $this->allowedViews(), true)) {
            $this->view = $view;
        }
    }

    public function goToday(): void
    {
        $this->focusDate = now()->toDateString();
    }

    public function previous(): void
    {
        $date = Carbon::parse($this->focusDate);
        $this->focusDate = match ($this->view) {
            'week' => $date->subWeek()->toDateString(),
            'day' => $date->subDay()->toDateString(),
            'year' => $date->subYear()->toDateString(),
            default => $date->subMonth()->toDateString(),
        };
    }

    public function next(): void
    {
        $date = Carbon::parse($this->focusDate);
        $this->focusDate = match ($this->view) {
            'week' => $date->addWeek()->toDateString(),
            'day' => $date->addDay()->toDateString(),
            'year' => $date->addYear()->toDateString(),
            default => $date->addMonth()->toDateString(),
        };
    }

    #[On('calendar-focus-month')]
    public function focusMonth(string $date): void
    {
        $this->focusDate = Carbon::parse($date)->startOfMonth()->toDateString();
        $this->view = 'month';
    }

    public function openCreate(?string $date = null): void
    {
        $this->dispatch('open-calendar-create', date: $date, lead: $this->lead_id);
    }

    public function openCreateShow(string $slug, ?string $date = null): void
    {
        $this->dispatch('open-calendar-create-show', slug: $slug, date: $date);
    }

    #[On('calendar-status')]
    public function showStatus(string $message): void
    {
        $this->statusMessage = $message;
    }

    public function render()
    {
        $focus = Carbon::parse($this->focusDate);
        $filters = $this->calendarFilters();

        return view('livewire.crm.calendar.calendar-dashboard', [
            'focus' => $focus,
            'filters' => $filters,
            'eventTypes' => CalendarEventType::query()->active()->orderBy('sort_order')->get(),
            'eventTypesByCategory' => CalendarEventType::query()
                ->active()
                ->orderBy('sort_order')
                ->get()
                ->groupBy(fn (CalendarEventType $type) => $type->category?->label() ?? 'Other'),
            'statuses' => CalendarEventStatus::cases(),
            'assignableUsers' => $this->assignableUsers(),
            'canManage' => auth()->user()?->hasPermission('calendar.manage'),
            'canAssign' => CalendarScope::userCanViewAll() || CalendarScope::userCanViewTeam(),
        ])->layout($this->crmLayout());
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function assignableUsers()
    {
        if (! CalendarScope::userCanViewAll() && ! CalendarScope::userCanViewTeam()) {
            return collect([auth()->user()])->filter();
        }

        return User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['consultant', 'manager', 'team-admin', 'admin']))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return list<string>
     */
    private function allowedViews(): array
    {
        return ['year', 'month', 'week', 'day', 'agenda'];
    }
}
