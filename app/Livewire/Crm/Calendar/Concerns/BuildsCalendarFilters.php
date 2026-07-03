<?php

namespace App\Livewire\Crm\Calendar\Concerns;

use App\Enums\Crm\CalendarEventCategory;

trait BuildsCalendarFilters
{
    /**
     * @return array<string, mixed>
     */
    protected function calendarFilters(): array
    {
        return [
            'user_id' => $this->filter_user_id,
            'event_type_id' => $this->filter_event_type_id,
            'show_category' => $this->filter_shows_only ? CalendarEventCategory::Show->value : null,
            'status' => $this->filter_status,
            'lead_id' => $this->lead_id ?? null,
            'show_tasks' => $this->filter_shows_only ? false : $this->show_tasks,
            'show_appointments' => $this->filter_shows_only ? false : $this->show_appointments,
        ];
    }
}
