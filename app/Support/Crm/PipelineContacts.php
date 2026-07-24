<?php

namespace App\Support\Crm;

use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PipelineContacts
{
    /**
     * @return list<class-string<Model>>
     */
    public static function modelClasses(): array
    {
        return [
            Lead::class,
            Prospect::class,
            Customer::class,
            Recruit::class,
        ];
    }

    /**
     * @param  Collection<int, int>|list<int>  $stageIds
     * @return Collection<int, Collection<int, Model>>
     */
    public static function forStages(Collection|array $stageIds, ?User $user = null, ?string $lifecycleFilter = null): Collection
    {
        $stageIds = collect($stageIds)->filter()->values();

        if ($stageIds->isEmpty()) {
            return collect();
        }

        $contacts = collect();
        $classes = self::modelClassesForLifecycle($lifecycleFilter);

        foreach ($classes as $modelClass) {
            $query = CrmScope::contacts(
                $modelClass::query()->whereIn('funnel_stage_id', $stageIds->all()),
                $user,
            )->with('assignedUser')->orderByDesc('updated_at');

            $contacts = $contacts->concat($query->get());
        }

        return $contacts
            ->groupBy('funnel_stage_id')
            ->map(fn (Collection $group) => $group->sortByDesc('updated_at')->values());
    }

    public static function countForStage(int $stageId, ?User $user = null): int
    {
        $total = 0;

        foreach (self::modelClasses() as $modelClass) {
            $total += CrmScope::contacts(
                $modelClass::query()->where('funnel_stage_id', $stageId),
                $user,
            )->count();
        }

        return $total;
    }

    public static function findAccessible(string $type, int $id, ?User $user = null): Model
    {
        $modelClass = match ($type) {
            'lead' => Lead::class,
            'prospect' => Prospect::class,
            'customer' => Customer::class,
            'recruit' => Recruit::class,
            default => null,
        };

        abort_unless($modelClass, 404);

        return CrmScope::contacts($modelClass::query(), $user)->findOrFail($id);
    }

    /**
     * @return list<class-string<Model>>
     */
    private static function modelClassesForLifecycle(?string $lifecycleFilter): array
    {
        return match ($lifecycleFilter) {
            'lead' => [Lead::class],
            'prospect' => [Prospect::class],
            'client' => [Customer::class],
            'recruit' => [Recruit::class],
            default => self::modelClasses(),
        };
    }
}
