<?php

namespace App\Support;

use App\Enums\BusinessLine;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class BusinessLineResolver
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function forLead(array $data, User $actor): string
    {
        if ($line = Arr::get($data, 'business_line')) {
            return self::assertAllowed((string) $line, $actor);
        }

        return self::defaultForUser($actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function forCalendarEvent(array $data, User $actor, ?Lead $lead, CalendarEventType $type): string
    {
        if ($line = Arr::get($data, 'business_line')) {
            return self::assertAllowed((string) $line, $actor);
        }

        if ($lead?->business_line) {
            return $lead->business_line instanceof BusinessLine
                ? $lead->business_line->value
                : (string) $lead->business_line;
        }

        if ($mapped = config('business.calendar_type_lines.'.$type->slug)) {
            return self::assertAllowed($mapped, $actor);
        }

        return self::defaultForUser($actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function forRelatedContact(array $data, User $actor, Lead|Prospect|Customer|Recruit|null $contact): string
    {
        if ($line = Arr::get($data, 'business_line')) {
            return self::assertAllowed((string) $line, $actor);
        }

        if ($contact?->business_line) {
            return $contact->business_line instanceof BusinessLine
                ? $contact->business_line->value
                : (string) $contact->business_line;
        }

        return self::defaultForUser($actor);
    }

    /**
     * @param  array<string, mixed>  $data
     * @deprecated Use forRelatedContact()
     */
    public static function forRelatedLead(array $data, User $actor, ?Lead $lead): string
    {
        return self::forRelatedContact($data, $actor, $lead);
    }

    public static function forLandingPageSlug(string $slug): string
    {
        return config('business.landing_page_lines.'.$slug, BusinessLine::H2s->value);
    }

    public static function defaultForUser(User $actor): string
    {
        return BusinessLineContext::current($actor);
    }

    private static function assertAllowed(string $line, User $actor): string
    {
        $allowed = array_map(
            fn (BusinessLine $businessLine) => $businessLine->value,
            BusinessLineContext::linesForUser($actor),
        );

        if (! in_array($line, $allowed, true)) {
            throw ValidationException::withMessages([
                'business_line' => 'You are not assigned to this business line.',
            ]);
        }

        return $line;
    }
}
