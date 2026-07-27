<?php

namespace App\Support\Crm;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use stdClass;

class CalendarMonthLayout
{
    /**
     * Enrich feed entries with span metadata used by month/week/day views.
     *
     * @param  Collection<int, stdClass>  $entries
     * @return Collection<int, stdClass>
     */
    public static function decorate(Collection $entries): Collection
    {
        return $entries->map(function (stdClass $entry) {
            $start = Carbon::parse($entry->start_at)->startOfDay();
            $end = Carbon::parse($entry->end_at ?? $entry->start_at)->startOfDay();

            if ($end->lt($start)) {
                $end = $start->copy();
            }

            $entry->is_all_day = (bool) ($entry->is_all_day ?? false);
            $entry->span_start = $start;
            $entry->span_end = $end;
            $entry->spans_multiple_days = $end->gt($start);
            $entry->is_bar = $entry->is_all_day || $entry->spans_multiple_days;

            return $entry;
        });
    }

    /**
     * Group entries onto every calendar day they cover.
     *
     * @param  Collection<int, stdClass>  $entries
     * @return Collection<string, Collection<int, stdClass>>
     */
    public static function entriesByDate(Collection $entries, Carbon $rangeStart, Carbon $rangeEnd): Collection
    {
        $byDate = collect();
        $rangeStartDay = $rangeStart->copy()->startOfDay();
        $rangeEndDay = $rangeEnd->copy()->startOfDay();

        foreach ($entries as $entry) {
            $cursor = $entry->span_start->copy()->max($rangeStartDay);
            $last = $entry->span_end->copy()->min($rangeEndDay);

            while ($cursor->lte($last)) {
                $key = $cursor->format('Y-m-d');
                if (! $byDate->has($key)) {
                    $byDate[$key] = collect();
                }
                $byDate[$key]->push($entry);
                $cursor->addDay();
            }
        }

        return $byDate;
    }

    /**
     * Build week rows with continuous bar lanes for the month grid.
     *
     * @param  Collection<int, stdClass>  $entries
     * @return list<array{
     *     start: Carbon,
     *     end: Carbon,
     *     days: list<Carbon>,
     *     lanes: list<list<array{entry: stdClass, start_col: int, span: int, continues_before: bool, continues_after: bool}>>,
     *     timed_by_date: array<string, Collection<int, stdClass>>
     * }>
     */
    public static function weeks(Carbon $monthFocus, Collection $entries): array
    {
        $gridStart = $monthFocus->copy()->startOfMonth()->startOfWeek()->startOfDay();
        $gridEnd = $monthFocus->copy()->endOfMonth()->endOfWeek()->startOfDay();
        $decorated = static::decorate($entries);
        $weeks = [];

        $weekStart = $gridStart->copy();
        while ($weekStart->lte($gridEnd)) {
            $weekEnd = $weekStart->copy()->addDays(6);
            $days = collect(range(0, 6))->map(fn (int $i) => $weekStart->copy()->addDays($i))->all();

            $barEvents = $decorated
                ->filter(fn (stdClass $entry) => $entry->is_bar
                    && $entry->span_start->lte($weekEnd)
                    && $entry->span_end->gte($weekStart))
                ->sortBy([
                    fn (stdClass $entry) => $entry->span_start->timestamp,
                    fn (stdClass $entry) => -($entry->span_start->diffInDays($entry->span_end) + 1),
                    fn (stdClass $entry) => strtolower((string) $entry->title),
                ])
                ->values();

            $lanes = static::assignLanes($barEvents, $weekStart, $weekEnd);

            $timedByDate = [];
            foreach ($days as $day) {
                $key = $day->format('Y-m-d');
                $timedByDate[$key] = $decorated
                    ->filter(fn (stdClass $entry) => ! $entry->is_bar
                        && $entry->span_start->equalTo($day->copy()->startOfDay()))
                    ->sortBy(fn (stdClass $entry) => $entry->start_at?->timestamp ?? 0)
                    ->values();
            }

            $weeks[] = [
                'start' => $weekStart->copy(),
                'end' => $weekEnd->copy(),
                'days' => $days,
                'lanes' => $lanes,
                'timed_by_date' => $timedByDate,
            ];

            $weekStart->addWeek();
        }

        return $weeks;
    }

    /**
     * @param  Collection<int, stdClass>  $barEvents
     * @return list<list<array{entry: stdClass, start_col: int, span: int, continues_before: bool, continues_after: bool}>>
     */
    private static function assignLanes(Collection $barEvents, Carbon $weekStart, Carbon $weekEnd): array
    {
        $laneEnds = [];
        $lanes = [];

        foreach ($barEvents as $entry) {
            $startCol = (int) max(0, $weekStart->diffInDays($entry->span_start, false));
            $endCol = (int) min(6, $weekStart->diffInDays($entry->span_end, false));

            if ($entry->span_start->lt($weekStart)) {
                $startCol = 0;
            }
            if ($entry->span_end->gt($weekEnd)) {
                $endCol = 6;
            }
            if ($endCol < $startCol) {
                $endCol = $startCol;
            }

            $segment = [
                'entry' => $entry,
                'start_col' => $startCol,
                'span' => $endCol - $startCol + 1,
                'continues_before' => $entry->span_start->lt($weekStart),
                'continues_after' => $entry->span_end->gt($weekEnd),
            ];

            $laneIndex = null;
            foreach ($laneEnds as $index => $occupiedThrough) {
                if ($startCol > $occupiedThrough) {
                    $laneIndex = $index;
                    break;
                }
            }

            if ($laneIndex === null) {
                $laneIndex = count($laneEnds);
                $laneEnds[$laneIndex] = $endCol;
                $lanes[$laneIndex] = [];
            } else {
                $laneEnds[$laneIndex] = $endCol;
            }

            $lanes[$laneIndex][] = $segment;
        }

        return array_values($lanes);
    }
}
