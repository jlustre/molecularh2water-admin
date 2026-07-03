<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads')) {
            Schema::create('leads', function (Blueprint $table) {
                $table->id();
                // Short lengths for enum-like values keep the composite index under
                // MySQL's 1000-byte limit on utf8mb4 (32*4 + 32*4 = 256 bytes).
                $table->string('lifecycle', 32)->default('lead');
                $table->string('status', 32)->default('new');
                $table->string('temperature', 16)->default('cold');
                $table->unsignedSmallInteger('score')->default(0);
                $table->string('first_name');
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->nullable();
                $table->string('company')->nullable();
                $table->foreignId('lead_source_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('funnel_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('funnel_stage_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
                $table->string('interested_in')->nullable();
                $table->text('message')->nullable();
                $table->string('lost_reason')->nullable();
                $table->timestamp('last_contacted_at')->nullable();
                $table->timestamp('next_follow_up_at')->nullable();
                $table->timestamp('converted_at')->nullable();
                $table->boolean('consent_given')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['lifecycle', 'status']);
                $table->index(['assigned_user_id', 'next_follow_up_at']);
                $table->index('temperature');
            });
        } else {
            // Partial production run: table exists but index may be missing.
            $this->ensureLifecycleStatusIndex();
        }

        if (! Schema::hasTable('lead_tag')) {
            Schema::create('lead_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
                $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['lead_id', 'tag_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_tag');
        Schema::dropIfExists('leads');
    }

    /**
     * Add leads_lifecycle_status_index when the table already exists with long
     * varchar columns (defaultStringLength 191). Prefix lengths keep the key
     * under 1000 bytes on utf8mb4 (50*4 + 50*4 = 400).
     */
    private function ensureLifecycleStatusIndex(): void
    {
        if (! Schema::hasColumn('leads', 'lifecycle') || ! Schema::hasColumn('leads', 'status')) {
            return;
        }

        if ($this->indexExists('leads', 'leads_lifecycle_status_index')) {
            return;
        }

        DB::statement(
            'ALTER TABLE `leads` ADD INDEX `leads_lifecycle_status_index` (`lifecycle`(50), `status`(50))'
        );
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = Schema::getConnection()->getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(*) AS aggregate
             FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
