<?php

namespace App\Livewire\Crm\Calendar;

use App\Services\Crm\CalendarQueryService;
use Carbon\Carbon;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;
use Livewire\Component;

#[Isolate]
class CalendarGrid extends Component
{
    public string $view = 'month';

    public string $focusDate;

    /** @var array<string, mixed> */
    public array $filters = [];

    public bool $canManage = false;

    public function mount(string $focusDate, string $view = 'month', array $filters = [], bool $canManage = false): void
    {
        $this->focusDate = $focusDate;
        $this->view = $view;
        $this->filters = $filters;
        $this->canManage = $canManage;
    }

    #[On('calendar-updated')]
    public function refreshGrid(): void
    {
        // Re-render with current props.
    }

    #[On('business-line-changed')]
    public function refreshOnBusinessLine(): void
    {
        // Re-render with current business line scope.
    }

    public function openDetails(string $kind, int $id): void
    {
        $this->dispatch('open-calendar-details', kind: $kind, id: $id);
    }

    public function openCreate(?string $date = null): void
    {
        $this->dispatch('open-calendar-create', date: $date);
    }

    public function render(CalendarQueryService $calendar)
    {
        $focus = Carbon::parse($this->focusDate);
        [$rangeStart, $rangeEnd] = $this->resolveRange($focus);

        $entries = $calendar->entries($rangeStart, $rangeEnd, $this->filters);
        $entriesByDate = $entries->groupBy(fn ($entry) => $entry->start_at->format('Y-m-d'));

        return view('livewire.crm.calendar.calendar-grid', [
            'focus' => $focus,
            'entries' => $entries,
            'entriesByDate' => $entriesByDate,
            'typeColors' => config('calendar.type_colors', []),
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(Carbon $focus): array
    {
        return match ($this->view) {
            'week' => [$focus->copy()->startOfWeek(), $focus->copy()->endOfWeek()],
            'day' => [$focus->copy()->startOfDay(), $focus->copy()->endOfDay()],
            'agenda' => [$focus->copy()->startOfDay(), $focus->copy()->addDays(30)->endOfDay()],
            default => [$focus->copy()->startOfMonth()->startOfWeek(), $focus->copy()->endOfMonth()->endOfWeek()],
        };
    }
}
