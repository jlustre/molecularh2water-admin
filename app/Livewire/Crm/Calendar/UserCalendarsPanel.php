<?php

namespace App\Livewire\Crm\Calendar;

use App\Models\Crm\UserCalendar;
use App\Models\User;
use App\Services\Crm\UserCalendarService;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class UserCalendarsPanel extends Component
{
    public string $newName = '';

    public string $newColor = 'teal';

    public ?int $sharingCalendarId = null;

    public ?int $shareUserId = null;

    public ?int $editingCalendarId = null;

    public string $editName = '';

    public string $editColor = 'teal';

    /** @var array<string, bool> calendar id => visible */
    public array $calendarVisibility = [];

    public function mount(UserCalendarService $calendars): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.view'), 403);

        if (Schema::hasTable('user_calendars') && auth()->user()) {
            $calendars->ensureDefaults(auth()->user());
            $this->syncVisibilityState($calendars);
        }
    }

    public function updatedCalendarVisibility(mixed $value, string $key): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.view'), 403);

        $calendarId = (int) $key;
        $calendar = UserCalendar::query()->findOrFail($calendarId);
        $calendars = app(UserCalendarService::class);

        $calendars->toggleVisibility($calendar, auth()->user(), (bool) $value);
        $this->calendarVisibility[(string) $calendarId] = (bool) $value;
        $this->notifyCalendarUpdated();
    }

    public function createCalendar(UserCalendarService $calendars): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        $this->validate([
            'newName' => ['required', 'string', 'max:80'],
            'newColor' => ['required', 'string', 'in:'.implode(',', $calendars->availableColors())],
        ]);

        $calendars->create(auth()->user(), [
            'name' => $this->newName,
            'color' => $this->newColor,
            'kind' => 'custom',
        ]);

        $this->reset('newName');
        $this->newColor = 'teal';
        $this->syncVisibilityState($calendars);
        $this->notifyCalendarUpdated();
        $this->dispatch('calendar-status', message: 'Calendar created.');
    }

    public function addHolidayCalendar(string $kind, UserCalendarService $calendars): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        $calendar = $calendars->addHolidayCalendar(auth()->user(), $kind);
        $this->syncVisibilityState($calendars);
        $this->notifyCalendarUpdated();
        $this->dispatch('calendar-status', message: $calendar->name.' added to your calendars.');
    }

    public function startEdit(int $calendarId, UserCalendarService $calendars): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        $calendar = UserCalendar::query()
            ->where('user_id', auth()->id())
            ->findOrFail($calendarId);

        abort_if($calendar->isHolidayKind(), 403);

        $this->editingCalendarId = $calendar->id;
        $this->editName = $calendar->name;
        $this->editColor = $calendar->color;
        $this->sharingCalendarId = null;
    }

    public function saveEdit(UserCalendarService $calendars): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);
        abort_unless($this->editingCalendarId, 404);

        $this->validate([
            'editName' => ['required', 'string', 'max:80'],
            'editColor' => ['required', 'string', 'in:'.implode(',', $calendars->availableColors())],
        ]);

        $calendar = UserCalendar::query()
            ->where('user_id', auth()->id())
            ->findOrFail($this->editingCalendarId);

        abort_if($calendar->isHolidayKind(), 403);

        $calendars->update($calendar, auth()->user(), [
            'name' => $this->editName,
            'color' => $this->editColor,
        ]);

        $this->editingCalendarId = null;
        $this->notifyCalendarUpdated();
        $this->dispatch('calendar-status', message: 'Calendar updated.');
    }

    public function cancelEdit(): void
    {
        $this->editingCalendarId = null;
    }

    public function deleteCalendar(int $calendarId, UserCalendarService $calendars): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        $calendar = UserCalendar::query()
            ->where('user_id', auth()->id())
            ->findOrFail($calendarId);

        abort_if($calendar->isHolidayKind(), 403);

        $calendars->delete($calendar, auth()->user());
        $this->syncVisibilityState($calendars);
        $this->notifyCalendarUpdated();
        $this->dispatch('calendar-status', message: 'Calendar deleted.');
    }

    public function openShare(int $calendarId): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

        $calendar = UserCalendar::query()
            ->where('user_id', auth()->id())
            ->findOrFail($calendarId);

        abort_if($calendar->isHolidayKind(), 403);

        $this->sharingCalendarId = $calendar->id;
        $this->shareUserId = null;
        $this->editingCalendarId = null;
    }

    public function closeShare(): void
    {
        $this->sharingCalendarId = null;
        $this->shareUserId = null;
    }

    public function shareCalendar(UserCalendarService $calendars): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);
        abort_unless($this->sharingCalendarId, 404);

        $this->validate([
            'shareUserId' => ['required', 'integer', 'exists:users,id'],
        ]);

        $calendar = UserCalendar::query()
            ->where('user_id', auth()->id())
            ->findOrFail($this->sharingCalendarId);

        $shareWith = User::query()->findOrFail($this->shareUserId);
        $calendars->share($calendar, auth()->user(), $shareWith);

        $this->shareUserId = null;
        $this->dispatch('calendar-status', message: 'Calendar shared with '.$shareWith->name.'.');
    }

    public function unshareCalendar(int $userId, UserCalendarService $calendars): void
    {
        abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);
        abort_unless($this->sharingCalendarId, 404);

        $calendar = UserCalendar::query()
            ->where('user_id', auth()->id())
            ->findOrFail($this->sharingCalendarId);

        $shareWith = User::query()->findOrFail($userId);
        $calendars->unshare($calendar, auth()->user(), $shareWith);

        $this->dispatch('calendar-status', message: 'Share removed.');
    }

    public function render(UserCalendarService $calendars)
    {
        $user = auth()->user();
        $list = $user && Schema::hasTable('user_calendars')
            ? $calendars->ensureDefaults($user)
            : collect();

        if ($user && Schema::hasTable('user_calendars') && $this->calendarVisibility === []) {
            $this->syncVisibilityState($calendars);
        }

        $hasUs = $list->contains(fn (UserCalendar $calendar) => $calendar->kind === 'us_holidays');
        $hasCa = $list->contains(fn (UserCalendar $calendar) => $calendar->kind === 'ca_holidays');

        $shareTargets = User::query()
            ->where('id', '!=', $user?->id)
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['consultant', 'manager', 'team-admin', 'admin', 'member']))
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name']);

        $sharingCalendar = $this->sharingCalendarId
            ? $list->firstWhere('id', $this->sharingCalendarId)
            : null;

        return view('livewire.crm.calendar.user-calendars-panel', [
            'calendars' => $list,
            'colors' => $calendars->availableColors(),
            'colorClasses' => config('calendar.type_colors', []),
            'hasUsHolidays' => $hasUs,
            'hasCaHolidays' => $hasCa,
            'shareTargets' => $shareTargets,
            'sharingCalendar' => $sharingCalendar,
            'canManage' => (bool) $user?->hasPermission('calendar.manage'),
        ]);
    }

    private function syncVisibilityState(UserCalendarService $calendars): void
    {
        $user = auth()->user();

        if (! $user) {
            $this->calendarVisibility = [];

            return;
        }

        $visibleIds = $calendars->visibleCalendarIds($user);
        $state = [];

        foreach ($calendars->calendarsForUser($user) as $calendar) {
            $state[(string) $calendar->id] = in_array((int) $calendar->id, $visibleIds, true);
        }

        $this->calendarVisibility = $state;
    }

    private function notifyCalendarUpdated(): void
    {
        $this->dispatch('calendar-updated')->to(CalendarGrid::class);
        $this->dispatch('calendar-updated')->to(CalendarWidgets::class);
    }
}
