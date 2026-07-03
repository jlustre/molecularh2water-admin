<?php

namespace App\Services\Crm;

use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Lifecycle;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use App\Support\Crm\CrmContactResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CrmContactTransferService
{
    public function __construct(
        private readonly TimelineService $timeline,
    ) {}

    /**
     * @param  Lead|Prospect|Customer|Recruit  $contact
     * @return Lead|Prospect|Customer|Recruit
     */
    public function transfer(Model $contact, LeadLifecycle $to, User $user): Model
    {
        $from = CrmContactResolver::lifecycleForModel($contact);

        if ($from === $to) {
            return $contact;
        }

        return DB::transaction(function () use ($contact, $from, $to, $user) {
            $targetClass = CrmContactResolver::modelClassFor($to);
            $attributes = collect($contact->getAttributes())
                ->except(['id', 'created_at', 'updated_at', 'deleted_at'])
                ->put('lifecycle_id', Lifecycle::idFor($to))
                ->put('converted_at', now())
                ->all();

            if ($to === LeadLifecycle::Client) {
                $attributes['status'] = LeadStatus::Customer->value;
            }

            /** @var Lead|Prospect|Customer|Recruit $target */
            $target = $targetClass::query()->create($attributes);

            $this->reassignMorphChildren($contact, $target);
            $this->reassignPivots($contact, $target);
            $this->reassignReferrals($contact, $target);

            $this->timeline->log(
                $target,
                'lifecycle_changed',
                "Converted to {$to->label()}",
                "Lifecycle changed from {$from->label()} to {$to->label()}.",
                ['from' => $from->value, 'to' => $to->value],
                $user,
            );

            $contact->delete();

            return $target->fresh(['source', 'stage', 'assignedUser', 'tags', 'funnel', 'lifecycleRecord']);
        });
    }

    private function reassignMorphChildren(Model $from, Model $to): void
    {
        $tables = [
            'activities', 'tasks', 'appointments', 'timeline_events',
            'pipeline_stage_histories', 'demonstrations', 'consultations',
            'quotations', 'orders', 'deliveries', 'installations',
            'followup_sequence_enrollments',
        ];

        foreach ($tables as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            DB::table($table)
                ->where('contact_type', $from->getMorphClass())
                ->where('contact_id', $from->id)
                ->update([
                    'contact_type' => $to->getMorphClass(),
                    'contact_id' => $to->id,
                ]);
        }

        DB::table('notes')
            ->where('noteable_type', $from->getMorphClass())
            ->where('noteable_id', $from->id)
            ->update([
                'noteable_type' => $to->getMorphClass(),
                'noteable_id' => $to->id,
            ]);
    }

    private function reassignPivots(Model $from, Model $to): void
    {
        foreach (['crm_contact_tag', 'crm_contact_user'] as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            DB::table($table)
                ->where('contact_type', $from->getMorphClass())
                ->where('contact_id', $from->id)
                ->update([
                    'contact_type' => $to->getMorphClass(),
                    'contact_id' => $to->id,
                ]);
        }
    }

    private function reassignReferrals(Model $from, Model $to): void
    {
        if (! DB::getSchemaBuilder()->hasTable('referrals')) {
            return;
        }

        DB::table('referrals')
            ->where('referrer_type', $from->getMorphClass())
            ->where('referrer_id', $from->id)
            ->update([
                'referrer_type' => $to->getMorphClass(),
                'referrer_id' => $to->id,
            ]);

        DB::table('referrals')
            ->where('referred_type', $from->getMorphClass())
            ->where('referred_id', $from->id)
            ->update([
                'referred_type' => $to->getMorphClass(),
                'referred_id' => $to->id,
            ]);

        DB::table($from->getTable())
            ->where('referred_by_type', $from->getMorphClass())
            ->where('referred_by_id', $from->id)
            ->update([
                'referred_by_type' => $to->getMorphClass(),
                'referred_by_id' => $to->id,
            ]);
    }
}
