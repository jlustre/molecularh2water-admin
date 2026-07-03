<?php

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Lifecycle;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BREAKING CHANGE:
 * - Splits monolithic leads table by lifecycle into leads/prospects/customers/recruits.
 * - Replaces lead_id FKs with polymorphic contact_type/contact_id on child tables.
 * - Renames lead_tag/lead_user pivots to crm_contact_tag/crm_contact_user.
 * - Referrals use polymorphic referrer/referred contacts.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $childTables = [
        'activities',
        'tasks',
        'appointments',
        'timeline_events',
        'pipeline_stage_histories',
        'demonstrations',
        'consultations',
        'quotations',
        'orders',
        'deliveries',
        'installations',
        'followup_sequence_enrollments',
    ];

    public function up(): void
    {
        $lifecycleIds = $this->lifecycleIds();

        $this->addLifecycleIdToLeads($lifecycleIds);
        $this->addPolymorphicColumnsToChildTables();
        $this->migrateChildLeadIds();
        $this->migratePivotTables();
        $this->migrateReferrals();
        $this->splitLeadsByLifecycle($lifecycleIds);
        $this->finalizeLeadsTable($lifecycleIds);
        $this->dropLeadIdColumns();
        Lifecycle::flushCache();
    }

    public function down(): void
    {
        throw new RuntimeException('This migration cannot be reversed automatically.');
    }

    /**
     * @return array<string, int>
     */
    private function lifecycleIds(): array
    {
        return DB::table('lifecycles')->pluck('id', 'slug')->all();
    }

    /**
     * @param  array<string, int>  $lifecycleIds
     */
    private function addLifecycleIdToLeads(array $lifecycleIds): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('lifecycle_id')->nullable()->after('business_line')->constrained('lifecycles')->restrictOnDelete();
            $table->nullableMorphs('referred_by');
        });

        foreach (DB::table('leads')->select('id', 'lifecycle', 'referred_by_lead_id')->get() as $lead) {
            DB::table('leads')->where('id', $lead->id)->update([
                'lifecycle_id' => $lifecycleIds[$lead->lifecycle] ?? $lifecycleIds['lead'],
            ]);

            if ($lead->referred_by_lead_id) {
                $referrer = DB::table('leads')->find($lead->referred_by_lead_id);

                if ($referrer) {
                    DB::table('leads')->where('id', $lead->id)->update([
                        'referred_by_type' => $this->morphTypeForLifecycle($referrer->lifecycle),
                        'referred_by_id' => $referrer->id,
                    ]);
                }
            }
        }
    }

    private function addPolymorphicColumnsToChildTables(): void
    {
        foreach ($this->childTables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'lead_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->nullableMorphs('contact');
            });
        }
    }

    private function migrateChildLeadIds(): void
    {
        $leadLifecycles = DB::table('leads')->pluck('lifecycle', 'id');

        foreach ($this->childTables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'lead_id')) {
                continue;
            }

            foreach (DB::table($tableName)->whereNotNull('lead_id')->select('id', 'lead_id')->get() as $row) {
                $lifecycle = $leadLifecycles[$row->lead_id] ?? 'lead';

                DB::table($tableName)->where('id', $row->id)->update([
                    'contact_type' => $this->morphTypeForLifecycle($lifecycle),
                    'contact_id' => $row->lead_id,
                ]);
            }
        }
    }

    private function migratePivotTables(): void
    {
        if (! Schema::hasTable('lead_tag')) {
            return;
        }

        Schema::create('crm_contact_tag', function (Blueprint $table) {
            $table->id();
            $table->morphs('contact');
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['contact_type', 'contact_id', 'tag_id']);
        });

        $leadLifecycles = DB::table('leads')->pluck('lifecycle', 'id');

        foreach (DB::table('lead_tag')->get() as $row) {
            $lifecycle = $leadLifecycles[$row->lead_id] ?? 'lead';

            DB::table('crm_contact_tag')->insert([
                'contact_type' => $this->morphTypeForLifecycle($lifecycle),
                'contact_id' => $row->lead_id,
                'tag_id' => $row->tag_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::drop('lead_tag');

        if (! Schema::hasTable('lead_user')) {
            return;
        }

        Schema::create('crm_contact_user', function (Blueprint $table) {
            $table->id();
            $table->morphs('contact');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['contact_type', 'contact_id', 'user_id']);
        });

        foreach (DB::table('lead_user')->get() as $row) {
            $lifecycle = $leadLifecycles[$row->lead_id] ?? 'lead';

            DB::table('crm_contact_user')->insert([
                'contact_type' => $this->morphTypeForLifecycle($lifecycle),
                'contact_id' => $row->lead_id,
                'user_id' => $row->user_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::drop('lead_user');
    }

    private function migrateReferrals(): void
    {
        if (! Schema::hasTable('referrals')) {
            return;
        }

        Schema::table('referrals', function (Blueprint $table) {
            $table->nullableMorphs('referrer');
            $table->nullableMorphs('referred');
        });

        $leadLifecycles = DB::table('leads')->pluck('lifecycle', 'id');

        foreach (DB::table('referrals')->get() as $referral) {
            $referrerLifecycle = $leadLifecycles[$referral->referrer_lead_id] ?? 'lead';
            $referredLifecycle = $leadLifecycles[$referral->referred_lead_id] ?? 'lead';

            DB::table('referrals')->where('id', $referral->id)->update([
                'referrer_type' => $this->morphTypeForLifecycle($referrerLifecycle),
                'referrer_id' => $referral->referrer_lead_id,
                'referred_type' => $this->morphTypeForLifecycle($referredLifecycle),
                'referred_id' => $referral->referred_lead_id,
            ]);
        }

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropForeign(['referrer_lead_id']);
            $table->dropForeign(['referred_lead_id']);
            $table->dropUnique(['referred_lead_id']);
            $table->dropIndex(['referrer_lead_id', 'status']);
            $table->dropColumn(['referrer_lead_id', 'referred_lead_id']);
            $table->unique(['referred_type', 'referred_id']);
        });
    }

    /**
     * @param  array<string, int>  $lifecycleIds
     */
    private function splitLeadsByLifecycle(array $lifecycleIds): void
    {
        $columns = [
            'business_line', 'lifecycle_id', 'status', 'temperature', 'score',
            'first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'country',
            'company', 'occupation', 'spouse_name', 'spouse_occupation', 'best_time_to_contact',
            'lead_source_id', 'funnel_id', 'funnel_stage_id', 'lost_reason_id',
            'assigned_user_id', 'referred_by_type', 'referred_by_id', 'team_id',
            'interested_in', 'message', 'lost_reason', 'last_contacted_at', 'next_follow_up_at',
            'converted_at', 'consent_given', 'metadata', 'created_at', 'updated_at', 'deleted_at',
        ];

        $mapping = [
            'prospect' => 'prospects',
            'client' => 'customers',
            'recruit' => 'recruits',
        ];

        foreach ($mapping as $lifecycle => $table) {
            $records = DB::table('leads')->where('lifecycle', $lifecycle)->get();

            foreach ($records as $record) {
                $payload = [];

                foreach ($columns as $column) {
                    if (property_exists($record, $column)) {
                        $payload[$column] = $record->{$column};
                    }
                }

                $payload['lifecycle_id'] = $lifecycleIds[$lifecycle];

                if ($record->referred_by_type && $record->referred_by_id) {
                    $payload['referred_by_type'] = $record->referred_by_type;
                    $payload['referred_by_id'] = $record->referred_by_id;
                }

                DB::table($table)->insert(array_merge($payload, ['id' => $record->id]));
            }

            $this->updateMorphReferencesForLifecycle($lifecycle, $table);
            DB::table('leads')->where('lifecycle', $lifecycle)->delete();
        }

        DB::table('leads')->update([
            'lifecycle_id' => $lifecycleIds['lead'],
        ]);
    }

    private function updateMorphReferencesForLifecycle(string $lifecycle, string $table): void
    {
        $morphType = $this->morphTypeForLifecycle($lifecycle);

        foreach ($this->childTables as $childTable) {
            if (! Schema::hasTable($childTable) || ! Schema::hasColumn($childTable, 'contact_type')) {
                continue;
            }

            DB::table($childTable)
                ->where('contact_type', 'lead')
                ->whereIn('contact_id', DB::table($table)->pluck('id'))
                ->update(['contact_type' => $morphType]);
        }

        if (Schema::hasTable('crm_contact_tag')) {
            DB::table('crm_contact_tag')
                ->where('contact_type', 'lead')
                ->whereIn('contact_id', DB::table($table)->pluck('id'))
                ->update(['contact_type' => $morphType]);
        }

        if (Schema::hasTable('crm_contact_user')) {
            DB::table('crm_contact_user')
                ->where('contact_type', 'lead')
                ->whereIn('contact_id', DB::table($table)->pluck('id'))
                ->update(['contact_type' => $morphType]);
        }

        if (Schema::hasTable('referrals')) {
            DB::table('referrals')
                ->where('referrer_type', 'lead')
                ->whereIn('referrer_id', DB::table($table)->pluck('id'))
                ->update(['referrer_type' => $morphType]);

            DB::table('referrals')
                ->where('referred_type', 'lead')
                ->whereIn('referred_id', DB::table($table)->pluck('id'))
                ->update(['referred_type' => $morphType]);
        }
    }

    /**
     * @param  array<string, int>  $lifecycleIds
     */
    private function finalizeLeadsTable(array $lifecycleIds): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'referred_by_lead_id')) {
                $table->dropConstrainedForeignId('referred_by_lead_id');
            }

            $table->dropIndex(['lifecycle', 'status']);
            $table->dropColumn('lifecycle');
        });
    }

    private function dropLeadIdColumns(): void
    {
        $indexesByTable = [
            'timeline_events' => ['timeline_events_lead_id_created_at_index'],
            'activities' => ['activities_lead_id_completed_at_index'],
            'consultations' => ['consultations_lead_id_conducted_at_index'],
            'quotations' => ['quotations_lead_id_status_index'],
            'orders' => ['orders_lead_id_status_index'],
            'demonstrations' => ['demonstrations_lead_id_scheduled_at_index'],
            'followup_sequence_enrollments' => ['followup_sequence_enrollments_lead_id_status_index'],
            'pipeline_stage_histories' => ['pipeline_stage_histories_lead_id_created_at_index'],
        ];

        foreach ($this->childTables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'lead_id')) {
                continue;
            }

            foreach ($indexesByTable[$tableName] ?? [] as $indexName) {
                $this->dropIndexIfExists($indexName);
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('lead_id');
            });
        }
    }

    private function dropIndexIfExists(string $indexName): void
    {
        try {
            DB::statement('DROP INDEX IF EXISTS '.$indexName);
        } catch (\Throwable) {
            // Ignore missing indexes across database engines.
        }
    }

    private function morphTypeForLifecycle(string $lifecycle): string
    {
        return match ($lifecycle) {
            LeadLifecycle::Prospect->value => 'prospect',
            LeadLifecycle::Client->value => 'customer',
            LeadLifecycle::Recruit->value => 'recruit',
            default => 'lead',
        };
    }
};
