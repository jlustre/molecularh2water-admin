<?php

namespace App\Support\Crm;

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Lifecycle;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CrmContactResolver
{
    public static function modelClassFor(LeadLifecycle|string $lifecycle): string
    {
        $slug = $lifecycle instanceof LeadLifecycle ? $lifecycle->value : $lifecycle;

        return match ($slug) {
            LeadLifecycle::Prospect->value => Prospect::class,
            LeadLifecycle::Client->value => Customer::class,
            LeadLifecycle::Recruit->value => Recruit::class,
            default => Lead::class,
        };
    }

    public static function lifecycleForModel(Model $contact): LeadLifecycle
    {
        if (method_exists($contact, 'lifecycleSlug')) {
            return $contact->lifecycleSlug();
        }

        return match ($contact::class) {
            Prospect::class => LeadLifecycle::Prospect,
            Customer::class => LeadLifecycle::Client,
            Recruit::class => LeadLifecycle::Recruit,
            default => LeadLifecycle::Lead,
        };
    }

    /**
     * @return Builder<Lead|Prospect|Customer|Recruit>
     */
    public static function queryFor(LeadLifecycle|string $lifecycle): Builder
    {
        $class = self::modelClassFor($lifecycle);

        return $class::query();
    }

    public static function lifecycleFromRouteName(?string $routeName): LeadLifecycle
    {
        $routeName ??= '';

        if (str_contains($routeName, 'prospects')) {
            return LeadLifecycle::Prospect;
        }

        if (str_contains($routeName, 'customers') || str_contains($routeName, 'clients')) {
            return LeadLifecycle::Client;
        }

        if (str_contains($routeName, 'recruits')) {
            return LeadLifecycle::Recruit;
        }

        return LeadLifecycle::Lead;
    }

    public static function defaultLifecycleId(LeadLifecycle|string $lifecycle): int
    {
        return Lifecycle::idFor($lifecycle);
    }

    /**
     * @return Lead|Prospect|Customer|Recruit
     */
    public static function resolve(string $type, int $id): Lead|Prospect|Customer|Recruit
    {
        $class = self::modelClassForMorph($type);

        return $class::query()->findOrFail($id);
    }

    public static function modelClassForMorph(string $type): string
    {
        return match ($type) {
            'prospect' => Prospect::class,
            'customer' => Customer::class,
            'recruit' => Recruit::class,
            default => Lead::class,
        };
    }
}
