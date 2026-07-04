<?php

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\Lifecycle;
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
 *
 * Safe to re-run on partial production schemas (missing FKs, indexes, or columns).
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
        if (! Schema::hasTable('leads')) {
            return;
        }

        if (! Schema::hasColumn('leads', 'lifecycle_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->foreignId('lifecycle_id')->nullable()->after('business_line')->constrained('lifecycles')->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('leads', 'referred_by_type')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->nullableMorphs('referred_by');
            });
        }

        if (! Schema::hasColumn('leads', 'lifecycle')) {
            return;
        }

        $select = ['id', 'lifecycle'];
        if (Schema::hasColumn('leads', 'referred_by_lead_id')) {
            $select[] = 'referred_by_lead_id';
        }

        foreach (DB::table('leads')->select($select)->get() as $lead) {
            DB::table('leads')->where('id', $lead->id)->update([
                'lifecycle_id' => $lifecycleIds[$lead->lifecycle] ?? $lifecycleIds['lead'],
            ]);

            if (! empty($lead->referred_by_lead_id)) {
                $referrer = DB::table('leads')->find($lead->referred_by_lead_id);

                if ($referrer) {
                    DB::table('leads')->where('id', $lead->id)->update([
                        'referred_by_type' => $this->morphTypeForLifecycle($referrer->lifecycle ?? 'lead'),
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

            if (Schema::hasColumn($tableName, 'contact_type')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->nullableMorphs('contact');
            });
        }
    }

    private function migrateChildLeadIds(): void
    {
        if (! Schema::hasTable('leads')) {
            return;
        }

        $leadLifecycles = Schema::hasColumn('leads', 'lifecycle')
            ? DB::table('leads')->pluck('lifecycle', 'id')
            : collect();

        foreach ($this->childTables as $tableName) {
            if (
                ! Schema::hasTable($tableName)
                || ! Schema::hasColumn($tableName, 'lead_id')
                || ! Schema::hasColumn($tableName, 'contact_type')
            ) {
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
        $leadLifecycles = Schema::hasTable('leads') && Schema::hasColumn('leads', 'lifecycle')
            ? DB::table('leads')->pluck('lifecycle', 'id')
            : collect();

        if (Schema::hasTable('lead_tag') && ! Schema::hasTable('crm_contact_tag')) {
            Schema::create('crm_contact_tag', function (Blueprint $table) {
                $table->id();
                $table->morphs('contact');
                $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['contact_type', 'contact_id', 'tag_id']);
            });

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
        }

        if (Schema::hasTable('lead_tag')) {
            Schema::drop('lead_tag');
        }

        if (Schema::hasTable('lead_user') && ! Schema::hasTable('crm_contact_user')) {
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
        }

        if (Schema::hasTable('lead_user')) {
            Schema::drop('lead_user');
        }
    }

    private function migrateReferrals(): void
    {
        if (! Schema::hasTable('referrals')) {
            return;
        }

        if (! Schema::hasColumn('referrals', 'referrer_type')) {
            Schema::table('referrals', function (Blueprint $table) {
                $table->nullableMorphs('referrer');
            });
        }

        if (! Schema::hasColumn('referrals', 'referred_type')) {
            Schema::table('referrals', function (Blueprint $table) {
                $table->nullableMorphs('referred');
            });
        }

        $hasLegacyColumns = Schema::hasColumn('referrals', 'referrer_lead_id')
            || Schema::hasColumn('referrals', 'referred_lead_id');

        if ($hasLegacyColumns && Schema::hasTable('leads')) {
            $leadLifecycles = Schema::hasColumn('leads', 'lifecycle')
                ? DB::table('leads')->pluck('lifecycle', 'id')
                : collect();

            $select = ['id'];
            if (Schema::hasColumn('referrals', 'referrer_lead_id')) {
                $select[] = 'referrer_lead_id';
            }
            if (Schema::hasColumn('referrals', 'referred_lead_id')) {
                $select[] = 'referred_lead_id';
            }

            foreach (DB::table('referrals')->select($select)->get() as $referral) {
                $updates = [];

                if (isset($referral->referrer_lead_id) && $referral->referrer_lead_id) {
                    $referrerLifecycle = $leadLifecycles[$referral->referrer_lead_id] ?? 'lead';
                    $updates['referrer_type'] = $this->morphTypeForLifecycle($referrerLifecycle);
                    $updates['referrer_id'] = $referral->referrer_lead_id;
                }

                if (isset($referral->referred_lead_id) && $referral->referred_lead_id) {
                    $referredLifecycle = $leadLifecycles[$referral->referred_lead_id] ?? 'lead';
                    $updates['referred_type'] = $this->morphTypeForLifecycle($referredLifecycle);
                    $updates['referred_id'] = $referral->referred_lead_id;
                }

                if ($updates !== []) {
                    DB::table('referrals')->where('id', $referral->id)->update($updates);
                }
            }
        }

        $this->dropColumnWithConstraints('referrals', 'referrer_lead_id');
        $this->dropColumnWithConstraints('referrals', 'referred_lead_id');

        // Explicit known index names (in case they outlive the columns on partial schemas).
        $this->dropIndexIfExists('referrals', 'referrals_referred_lead_id_unique');
        $this->dropIndexIfExists('referrals', 'referrals_referrer_lead_id_status_index');
        $this->dropForeignKeyIfExists('referrals', 'referrals_referrer_lead_id_foreign');
        $this->dropForeignKeyIfExists('referrals', 'referrals_referred_lead_id_foreign');

        if (
            Schema::hasColumn('referrals', 'referred_type')
            && Schema::hasColumn('referrals', 'referred_id')
            && ! $this->indexExists('referrals', 'referrals_referred_type_referred_id_unique')
        ) {
            Schema::table('referrals', function (Blueprint $table) {
                $table->unique(['referred_type', 'referred_id']);
            });
        }
    }

    /**
     * @param  array<string, int>  $lifecycleIds
     */
    private function splitLeadsByLifecycle(array $lifecycleIds): void
    {
        if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', 'lifecycle')) {
            return;
        }

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
            if (! Schema::hasTable($table)) {
                continue;
            }

            $records = DB::table('leads')->where('lifecycle', $lifecycle)->get();

            foreach ($records as $record) {
                if (DB::table($table)->where('id', $record->id)->exists()) {
                    continue;
                }

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
        $ids = DB::table($table)->pluck('id');

        foreach ($this->childTables as $childTable) {
            if (! Schema::hasTable($childTable) || ! Schema::hasColumn($childTable, 'contact_type')) {
                continue;
            }

            DB::table($childTable)
                ->where('contact_type', 'lead')
                ->whereIn('contact_id', $ids)
                ->update(['contact_type' => $morphType]);
        }

        if (Schema::hasTable('crm_contact_tag')) {
            DB::table('crm_contact_tag')
                ->where('contact_type', 'lead')
                ->whereIn('contact_id', $ids)
                ->update(['contact_type' => $morphType]);
        }

        if (Schema::hasTable('crm_contact_user')) {
            DB::table('crm_contact_user')
                ->where('contact_type', 'lead')
                ->whereIn('contact_id', $ids)
                ->update(['contact_type' => $morphType]);
        }

        if (Schema::hasTable('referrals')) {
            if (Schema::hasColumn('referrals', 'referrer_type')) {
                DB::table('referrals')
                    ->where('referrer_type', 'lead')
                    ->whereIn('referrer_id', $ids)
                    ->update(['referrer_type' => $morphType]);
            }

            if (Schema::hasColumn('referrals', 'referred_type')) {
                DB::table('referrals')
                    ->where('referred_type', 'lead')
                    ->whereIn('referred_id', $ids)
                    ->update(['referred_type' => $morphType]);
            }
        }
    }

    /**
     * @param  array<string, int>  $lifecycleIds
     */
    private function finalizeLeadsTable(array $lifecycleIds): void
    {
        $this->dropColumnWithConstraints('leads', 'referred_by_lead_id');
        $this->dropForeignKeyIfExists('leads', 'leads_referred_by_lead_id_foreign');

        if (! Schema::hasColumn('leads', 'lifecycle')) {
            return;
        }

        // Index may be missing if the create migration failed partway or used a prefix fallback.
        $this->dropIndexIfExists('leads', 'leads_lifecycle_status_index');
        $this->dropIndexesOnColumn('leads', 'lifecycle');

        Schema::table('leads', function (Blueprint $table) {
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

            // Known composite indexes first (safe even when FK is absent).
            foreach ($indexesByTable[$tableName] ?? [] as $indexName) {
                $this->dropIndexIfExists($tableName, $indexName);
            }

            // Drops any FK name on lead_id, remaining indexes, then the column.
            $this->dropColumnWithConstraints($tableName, 'lead_id');
        }
    }

    /**
     * Drop any foreign keys on a column, any non-primary indexes involving it, then the column.
     */
    private function dropColumnWithConstraints(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        // SQLite cannot ALTER DROP a column that participates in an (often unnamed) FK.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->sqliteDropColumn($table, $column);

            return;
        }

        $this->dropForeignKeysOnColumn($table, $column);
        $this->dropIndexesOnColumn($table, $column);

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->dropColumn($column);
        });
    }

    /**
     * Rebuild a SQLite table without the given column and any FK clauses that reference it.
     */
    private function sqliteDropColumn(string $table, string $column): void
    {
        $createSql = DB::selectOne(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?",
            [$table]
        )?->sql;

        if (! is_string($createSql) || $createSql === '') {
            return;
        }

        $remainingColumns = collect(Schema::getColumnListing($table))
            ->reject(fn (string $name) => $name === $column)
            ->values()
            ->all();

        if ($remainingColumns === []) {
            return;
        }

        $tempTable = $table.'__tmp_drop_col';
        $quotedColumn = preg_quote($column, '/');

        // Remove FK clauses that reference the dropped column (quoted or bare table names).
        $createSql = preg_replace(
            '/,\s*foreign key\("'.$quotedColumn.'"\)\s+references\s+["\w]+\s*\("[^"]+"\)(?:\s+on\s+(?:delete|update)\s+(?:set\s+null|set\s+default|cascade|restrict|no\s+action))*/i',
            '',
            $createSql
        ) ?? $createSql;

        // Remove the column definition (first column or subsequent).
        $createSql = preg_replace(
            '/\(\s*"'.$quotedColumn.'"\s+[^,]+,\s*/i',
            '(',
            $createSql
        ) ?? $createSql;

        $createSql = preg_replace(
            '/,\s*"'.$quotedColumn.'"\s+[^,)]+/i',
            '',
            $createSql
        ) ?? $createSql;

        $createSql = preg_replace(
            '/CREATE TABLE ["`]?'.preg_quote($table, '/').'["`]?/i',
            'CREATE TABLE "'.$tempTable.'"',
            $createSql,
            1
        ) ?? $createSql;

        Schema::disableForeignKeyConstraints();

        try {
            Schema::dropIfExists($tempTable);
            DB::statement($createSql);

            $quotedColumns = collect($remainingColumns)
                ->map(fn (string $name) => '"'.str_replace('"', '""', $name).'"')
                ->implode(', ');

            DB::statement("INSERT INTO \"{$tempTable}\" ({$quotedColumns}) SELECT {$quotedColumns} FROM \"{$table}\"");
            Schema::drop($table);
            DB::statement("ALTER TABLE \"{$tempTable}\" RENAME TO \"{$table}\"");
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * Drop every foreign key that references the given column (any constraint name).
     */
    private function dropForeignKeysOnColumn(string $table, string $column): void
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            $name = $foreignKey['name'] ?? null;

            if (is_string($name) && $name !== '' && in_array($column, $foreignKey['columns'], true)) {
                $this->dropForeignKeyIfExists($table, $name);
            }
        }

        // Also try Laravel's conventional name in case the driver omits a constraint.
        $this->dropForeignKeyIfExists($table, "{$table}_{$column}_foreign");
    }

    private function dropForeignKeyIfExists(string $table, string $foreignKey): void
    {
        if (! $this->foreignKeyExists($table, $foreignKey)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($foreignKey) {
            $blueprint->dropForeign($foreignKey);
        });
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        foreach (Schema::getForeignKeys($table) as $constraint) {
            if ($constraint['name'] === $foreignKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * Drop every non-primary index that includes the given column.
     */
    private function dropIndexesOnColumn(string $table, string $column): void
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['primary'] ?? false) === true) {
                continue;
            }

            if (in_array($column, $index['columns'], true)) {
                $this->dropIndexIfExists($table, $index['name']);
            }
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }

        return false;
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
