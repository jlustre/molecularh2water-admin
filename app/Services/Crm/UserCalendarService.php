<?php

namespace App\Services\Crm;

use App\Enums\Crm\CalendarEventStatus;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\UserCalendar;
use App\Models\Crm\UserCalendarShare;
use App\Models\Crm\UserCalendarVisibility;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserCalendarService
{
    /**
     * @return list<string>
     */
    public function availableColors(): array
    {
        return array_keys(config('calendar.type_colors', [
            'teal' => '',
            'indigo' => '',
            'rose' => '',
            'red' => '',
            'amber' => '',
            'emerald' => '',
            'blue' => '',
            'violet' => '',
            'orange' => '',
            'slate' => '',
        ]));
    }

    /**
     * @return Collection<int, UserCalendar>
     */
    public function ensureDefaults(User $user): Collection
    {
        if (! UserCalendar::query()->where('user_id', $user->id)->exists()) {
            $this->create($user, [
                'name' => 'Personal',
                'color' => 'teal',
                'kind' => 'personal',
                'is_default' => true,
            ]);

            $this->create($user, [
                'name' => 'Work',
                'color' => 'indigo',
                'kind' => 'work',
                'is_default' => false,
            ]);
        }

        return $this->calendarsForUser($user);
    }

    /**
     * Calendars the user owns or has shared access to.
     *
     * @return Collection<int, UserCalendar>
     */
    public function calendarsForUser(User $user): Collection
    {
        $owned = UserCalendar::query()
            ->where('user_id', $user->id)
            ->with(['sharedWithUsers:id,name', 'visibilities' => fn ($q) => $q->where('user_id', $user->id)])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $shared = UserCalendar::query()
            ->whereHas('shares', fn ($q) => $q->where('shared_with_user_id', $user->id))
            ->with([
                'owner:id,name',
                'visibilities' => fn ($q) => $q->where('user_id', $user->id),
            ])
            ->orderBy('name')
            ->get();

        return $owned->concat($shared)->unique('id')->values();
    }

    /**
     * Calendar IDs the user can access (owned + shared).
     *
     * @return list<int>
     */
    public function accessibleCalendarIds(User $user): array
    {
        $owned = UserCalendar::query()
            ->where('user_id', $user->id)
            ->pluck('id');

        $shared = UserCalendarShare::query()
            ->where('shared_with_user_id', $user->id)
            ->pluck('user_calendar_id');

        return $owned->merge($shared)->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /**
     * Calendar IDs currently visible on the grid for this user.
     *
     * @return list<int>
     */
    public function visibleCalendarIds(User $user): array
    {
        $accessible = collect($this->accessibleCalendarIds($user));

        if ($accessible->isEmpty()) {
            return [];
        }

        $hidden = UserCalendarVisibility::query()
            ->where('user_id', $user->id)
            ->whereIn('user_calendar_id', $accessible)
            ->where('is_visible', false)
            ->pluck('user_calendar_id')
            ->map(fn ($id) => (int) $id);

        return $accessible->diff($hidden)->values()->all();
    }

    /**
     * @param  array{name: string, color?: string, kind?: string, is_default?: bool}  $data
     */
    public function create(User $actor, array $data): UserCalendar
    {
        $color = $data['color'] ?? 'teal';
        if (! in_array($color, $this->availableColors(), true)) {
            $color = 'teal';
        }

        $calendar = UserCalendar::query()->create([
            'user_id' => $actor->id,
            'name' => trim($data['name']),
            'color' => $color,
            'kind' => $data['kind'] ?? 'custom',
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);

        UserCalendarVisibility::query()->updateOrCreate(
            ['user_id' => $actor->id, 'user_calendar_id' => $calendar->id],
            ['is_visible' => true],
        );

        return $calendar;
    }

    /**
     * @param  array{name?: string, color?: string}  $data
     */
    public function update(UserCalendar $calendar, User $actor, array $data): UserCalendar
    {
        $this->ensureOwner($calendar, $actor);
        $this->ensureNotHoliday($calendar);

        $payload = [];

        if (array_key_exists('name', $data)) {
            $payload['name'] = trim((string) $data['name']);
        }

        if (array_key_exists('color', $data)) {
            $color = (string) $data['color'];
            $payload['color'] = in_array($color, $this->availableColors(), true) ? $color : $calendar->color;
        }

        if ($payload !== []) {
            $calendar->update($payload);
        }

        return $calendar->refresh();
    }

    public function delete(UserCalendar $calendar, User $actor): void
    {
        $this->ensureOwner($calendar, $actor);
        $this->ensureNotHoliday($calendar);

        if ($calendar->is_default || in_array($calendar->kind, ['personal', 'work'], true)) {
            throw ValidationException::withMessages([
                'calendar' => 'Default Personal and Work calendars cannot be deleted.',
            ]);
        }

        $calendar->delete();
    }

    public function toggleVisibility(UserCalendar $calendar, User $actor, bool $visible): void
    {
        abort_unless(in_array($calendar->id, $this->accessibleCalendarIds($actor), true), 403);

        UserCalendarVisibility::query()->updateOrCreate(
            ['user_id' => $actor->id, 'user_calendar_id' => $calendar->id],
            ['is_visible' => $visible],
        );
    }

    public function share(UserCalendar $calendar, User $actor, User $shareWith): void
    {
        $this->ensureOwner($calendar, $actor);
        $this->ensureNotHoliday($calendar);

        if ($shareWith->id === $actor->id) {
            throw ValidationException::withMessages([
                'share_user_id' => 'You already own this calendar.',
            ]);
        }

        UserCalendarShare::query()->firstOrCreate([
            'user_calendar_id' => $calendar->id,
            'shared_with_user_id' => $shareWith->id,
        ]);

        UserCalendarVisibility::query()->firstOrCreate(
            ['user_id' => $shareWith->id, 'user_calendar_id' => $calendar->id],
            ['is_visible' => true],
        );
    }

    public function unshare(UserCalendar $calendar, User $actor, User $shareWith): void
    {
        $this->ensureOwner($calendar, $actor);

        UserCalendarShare::query()
            ->where('user_calendar_id', $calendar->id)
            ->where('shared_with_user_id', $shareWith->id)
            ->delete();
    }

    public function addHolidayCalendar(User $actor, string $kind): UserCalendar
    {
        abort_unless(in_array($kind, ['us_holidays', 'ca_holidays'], true), 422);

        $existing = UserCalendar::query()
            ->where('user_id', $actor->id)
            ->where('kind', $kind)
            ->first();

        if ($existing) {
            $this->toggleVisibility($existing, $actor, true);

            return $existing;
        }

        $calendar = $this->create($actor, [
            'name' => $kind === 'us_holidays' ? 'US Holidays' : 'Canadian Holidays',
            'color' => $kind === 'us_holidays' ? 'rose' : 'red',
            'kind' => $kind,
            'is_default' => false,
        ]);

        $this->seedHolidayEvents($calendar, $actor);

        return $calendar;
    }

    public function defaultCalendarId(User $user): ?int
    {
        return UserCalendar::query()
            ->where('user_id', $user->id)
            ->where('is_default', true)
            ->value('id')
            ?? UserCalendar::query()->where('user_id', $user->id)->value('id');
    }

    private function ensureOwner(UserCalendar $calendar, User $actor): void
    {
        abort_unless((int) $calendar->user_id === (int) $actor->id, 403);
    }

    private function ensureNotHoliday(UserCalendar $calendar): void
    {
        if ($calendar->isHolidayKind()) {
            throw ValidationException::withMessages([
                'calendar' => 'Holiday calendars can only be shown or hidden.',
            ]);
        }
    }

    private function seedHolidayEvents(UserCalendar $calendar, User $actor): void
    {
        $typeId = CalendarEventType::query()
            ->where('slug', 'personal-task')
            ->value('id')
            ?? CalendarEventType::query()->value('id');

        if (! $typeId) {
            return;
        }

        $years = [now()->year, now()->year + 1];
        $holidays = $calendar->kind === 'us_holidays'
            ? $this->usHolidays($years)
            : $this->canadianHolidays($years);

        DB::transaction(function () use ($calendar, $actor, $typeId, $holidays) {
            foreach ($holidays as $holiday) {
                CalendarEvent::query()->create([
                    'user_id' => $actor->id,
                    'user_calendar_id' => $calendar->id,
                    'business_line' => 'h2s',
                    'calendar_event_type_id' => $typeId,
                    'title' => $holiday['title'],
                    'description' => $holiday['title'],
                    'start_at' => $holiday['date']->copy()->startOfDay()->setTime(9, 0),
                    'end_at' => $holiday['date']->copy()->startOfDay()->setTime(10, 0),
                    'timezone' => config('calendar.default_timezone', config('app.timezone')),
                    'status' => CalendarEventStatus::Scheduled,
                    'priority' => 'normal',
                    'reminder_enabled' => false,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                    'metadata' => ['holiday' => true, 'calendar_kind' => $calendar->kind],
                ]);
            }
        });
    }

    /**
     * @param  list<int>  $years
     * @return list<array{title: string, date: Carbon}>
     */
    private function usHolidays(array $years): array
    {
        $items = [];

        foreach ($years as $year) {
            $items[] = ['title' => "New Year's Day", 'date' => Carbon::create($year, 1, 1)];
            $items[] = ['title' => 'Martin Luther King Jr. Day', 'date' => $this->nthWeekdayOfMonth($year, 1, Carbon::MONDAY, 3)];
            $items[] = ['title' => "Presidents' Day", 'date' => $this->nthWeekdayOfMonth($year, 2, Carbon::MONDAY, 3)];
            $items[] = ['title' => 'Memorial Day', 'date' => $this->lastWeekdayOfMonth($year, 5, Carbon::MONDAY)];
            $items[] = ['title' => 'Juneteenth', 'date' => Carbon::create($year, 6, 19)];
            $items[] = ['title' => 'Independence Day', 'date' => Carbon::create($year, 7, 4)];
            $items[] = ['title' => 'Labor Day', 'date' => $this->nthWeekdayOfMonth($year, 9, Carbon::MONDAY, 1)];
            $items[] = ['title' => 'Thanksgiving', 'date' => $this->nthWeekdayOfMonth($year, 11, Carbon::THURSDAY, 4)];
            $items[] = ['title' => 'Christmas Day', 'date' => Carbon::create($year, 12, 25)];
        }

        return $items;
    }

    /**
     * @param  list<int>  $years
     * @return list<array{title: string, date: Carbon}>
     */
    private function canadianHolidays(array $years): array
    {
        $items = [];

        foreach ($years as $year) {
            $items[] = ['title' => "New Year's Day", 'date' => Carbon::create($year, 1, 1)];
            $items[] = ['title' => 'Good Friday', 'date' => $this->easterSunday($year)->subDays(2)];
            $items[] = ['title' => 'Victoria Day', 'date' => $this->victoriaDay($year)];
            $items[] = ['title' => 'Canada Day', 'date' => Carbon::create($year, 7, 1)];
            $items[] = ['title' => 'Labour Day', 'date' => $this->nthWeekdayOfMonth($year, 9, Carbon::MONDAY, 1)];
            $items[] = ['title' => 'Thanksgiving (Canada)', 'date' => $this->nthWeekdayOfMonth($year, 10, Carbon::MONDAY, 2)];
            $items[] = ['title' => 'Remembrance Day', 'date' => Carbon::create($year, 11, 11)];
            $items[] = ['title' => 'Christmas Day', 'date' => Carbon::create($year, 12, 25)];
            $items[] = ['title' => 'Boxing Day', 'date' => Carbon::create($year, 12, 26)];
        }

        return $items;
    }

    private function nthWeekdayOfMonth(int $year, int $month, int $weekday, int $nth): Carbon
    {
        $date = Carbon::create($year, $month, 1)->startOfDay();

        while ($date->dayOfWeek !== $weekday) {
            $date->addDay();
        }

        $date->addWeeks($nth - 1);

        return $date;
    }

    private function lastWeekdayOfMonth(int $year, int $month, int $weekday): Carbon
    {
        $date = Carbon::create($year, $month, 1)->endOfMonth()->startOfDay();

        while ($date->dayOfWeek !== $weekday) {
            $date->subDay();
        }

        return $date;
    }

    private function victoriaDay(int $year): Carbon
    {
        $date = Carbon::create($year, 5, 24)->startOfDay();

        while ($date->dayOfWeek !== Carbon::MONDAY) {
            $date->subDay();
        }

        return $date;
    }

    private function easterSunday(int $year): Carbon
    {
        // Anonymous Gregorian algorithm
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day)->startOfDay();
    }
}
