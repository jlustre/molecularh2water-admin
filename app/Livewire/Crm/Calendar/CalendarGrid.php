<?php

namespace App\Livewire\Crm\Calendar;

use App\Services\Crm\CalendarQueryService;
use App\Support\Crm\CalendarMonthLayout;
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

    public ?string $selectedDay = null;

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

    public function openDay(string $date): void
    {
        $this->selectedDay = Carbon::parse($date)->toDateString();
    }

    public function closeDay(): void
    {
        $this->selectedDay = null;
    }

    public function openDetails(string $kind, int $id): void
    {
        $this->selectedDay = null;
        $this->dispatch('open-calendar-details', kind: $kind, id: $id);
    }

    public function openCreate(?string $date = null): void
    {
        $this->selectedDay = null;
        $this->dispatch('open-calendar-create', date: $date);
    }

    public function openMonth(string $date): void
    {
        $this->selectedDay = null;
        $this->dispatch('calendar-focus-month', date: Carbon::parse($date)->toDateString());
    }

    public function render(CalendarQueryService $calendar)
    {
        $focus = Carbon::parse($this->focusDate);
        [$rangeStart, $rangeEnd] = $this->resolveRange($focus);

        $entries = CalendarMonthLayout::decorate(
            $calendar->entries($rangeStart, $rangeEnd, $this->filters)
        );
        $entriesByDate = CalendarMonthLayout::entriesByDate($entries, $rangeStart, $rangeEnd);
        $countsByDate = $entriesByDate->map->count();
        $monthWeeks = $this->view === 'month'
            ? CalendarMonthLayout::weeks($focus, $entries)
            : [];

        $selectedDayEntries = collect();
        $selectedDayLabel = null;

        if ($this->selectedDay) {
            $selected = Carbon::parse($this->selectedDay);
            $selectedDayLabel = $selected->format('l, F j, Y');
            $selectedDayEntries = $entriesByDate->get($this->selectedDay, collect())
                ->sortBy([
                    fn ($entry) => $entry->is_all_day ? 0 : 1,
                    fn ($entry) => $entry->start_at?->timestamp ?? 0,
                ])
                ->values();
        }

        return view('livewire.crm.calendar.calendar-grid', [
            'focus' => $focus,
            'entries' => $entries,
            'entriesByDate' => $entriesByDate,
            'countsByDate' => $countsByDate,
            'monthWeeks' => $monthWeeks,
            'selectedDayEntries' => $selectedDayEntries,
            'selectedDayLabel' => $selectedDayLabel,
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
            'year' => [$focus->copy()->startOfYear(), $focus->copy()->endOfYear()],
            default => [$focus->copy()->startOfMonth()->startOfWeek(), $focus->copy()->endOfMonth()->endOfWeek()],
        };
    }
}
