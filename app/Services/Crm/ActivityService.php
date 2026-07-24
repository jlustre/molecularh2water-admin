<?php

namespace App\Services\Crm;

use App\Models\Crm\Activity;
use App\Models\Crm\ActivityType;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use App\Support\BusinessLineResolver;
use App\Support\Crm\CrmContactResolver;
use Illuminate\Support\Arr;

class ActivityService
{
    public function __construct(
        private readonly TimelineService $timeline,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function log(array $data, User $user): Activity
    {
        $type = ActivityType::query()->findOrFail((int) $data['activity_type_id']);
        $contact = $this->resolveContact($data);
        $completedAt = Arr::get($data, 'completed_at', now());

        $activity = Activity::query()->create([
            'activity_type_id' => $type->id,
            'user_id' => $user->id,
            'contact_type' => $contact->getMorphClass(),
            'contact_id' => $contact->id,
            'business_line' => BusinessLineResolver::forRelatedContact($data, $user, $contact),
            'title' => trim((string) Arr::get($data, 'title', $type->name)),
            'description' => Arr::get($data, 'description'),
            'outcome' => Arr::get($data, 'outcome'),
            'next_action' => Arr::get($data, 'next_action'),
            'scheduled_at' => Arr::get($data, 'scheduled_at'),
            'completed_at' => $completedAt,
            'duration_minutes' => Arr::get($data, 'duration_minutes'),
            'metadata' => Arr::get($data, 'metadata'),
        ]);

        $contact->update([
            'last_contacted_at' => $completedAt,
        ]);

        if ($nextFollowUp = Arr::get($data, 'next_follow_up_at')) {
            $contact->update(['next_follow_up_at' => $nextFollowUp]);
        }

        $this->timeline->log(
            $contact,
            'activity_logged',
            $activity->title,
            $activity->description,
            [
                'activity_id' => $activity->id,
                'activity_type' => $type->slug,
                'outcome' => $activity->outcome,
            ],
            $user,
        );

        return $activity->fresh(['type', 'contact', 'user']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Activity $activity, array $data, User $user): Activity
    {
        $type = ActivityType::query()->findOrFail((int) $data['activity_type_id']);
        $contact = $this->resolveContact($data);
        $completedAt = Arr::get($data, 'completed_at') ?: $activity->completed_at ?: now();

        $activity->update([
            'activity_type_id' => $type->id,
            'contact_type' => $contact->getMorphClass(),
            'contact_id' => $contact->id,
            'title' => trim((string) Arr::get($data, 'title', $type->name)),
            'description' => Arr::get($data, 'description'),
            'outcome' => Arr::get($data, 'outcome'),
            'next_action' => Arr::get($data, 'next_action'),
            'completed_at' => $completedAt,
            'duration_minutes' => Arr::get($data, 'duration_minutes'),
        ]);

        if ($nextFollowUp = Arr::get($data, 'next_follow_up_at')) {
            $contact->update(['next_follow_up_at' => $nextFollowUp]);
        }

        $this->timeline->log(
            $contact,
            'activity_updated',
            'Activity updated',
            $activity->title,
            [
                'activity_id' => $activity->id,
                'activity_type' => $type->slug,
                'outcome' => $activity->outcome,
            ],
            $user,
        );

        return $activity->fresh(['type', 'contact', 'user']);
    }

    public function delete(Activity $activity, User $user): void
    {
        $contact = $activity->contact;

        $this->timeline->log(
            $contact,
            'activity_deleted',
            'Activity removed',
            $activity->title,
            ['activity_id' => $activity->id],
            $user,
        );

        $activity->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Lead|Prospect|Customer|Recruit
     */
    private function resolveContact(array $data): Lead|Prospect|Customer|Recruit
    {
        if (isset($data['contact_type'], $data['contact_id'])) {
            return CrmContactResolver::resolve((string) $data['contact_type'], (int) $data['contact_id']);
        }

        return CrmContactResolver::resolve('lead', (int) $data['lead_id']);
    }
}
