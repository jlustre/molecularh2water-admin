<div>
<x-crm.calendar-panel tone="emerald">
    @if ($view === 'month')
        @include('livewire.crm.calendar.partials.month-view', [
            'focus' => $focus,
            'entriesByDate' => $entriesByDate,
            'typeColors' => $typeColors,
            'canManage' => $canManage,
        ])
    @elseif ($view === 'week')
        @include('livewire.crm.calendar.partials.week-view', [
            'focus' => $focus,
            'entries' => $entries,
            'typeColors' => $typeColors,
        ])
    @elseif ($view === 'day')
        @include('livewire.crm.calendar.partials.day-view', [
            'focus' => $focus,
            'entries' => $entries,
            'typeColors' => $typeColors,
        ])
    @else
        @include('livewire.crm.calendar.partials.agenda-view', [
            'entries' => $entries,
            'typeColors' => $typeColors,
        ])
    @endif
</x-crm.calendar-panel>
</div>