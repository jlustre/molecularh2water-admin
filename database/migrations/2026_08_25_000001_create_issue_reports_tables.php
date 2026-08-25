<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('issue_reports')) {
            Schema::create('issue_reports', function (Blueprint $table) {
                $table->id();
                $table->string('reference_code', 40)->unique();
                $table->string('reporter_name');
                $table->string('reporter_email');
                $table->string('reporter_phone')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                // Short lengths for enum-like values keep composite indexes under
                // MySQL's 1000-byte limit on utf8mb4 (32*4 + 32*4 = 256 bytes).
                $table->string('source', 32);
                $table->string('site', 32);
                $table->string('category', 32);
                $table->string('severity', 32);
                $table->string('title');
                $table->text('description');
                $table->string('page_url')->nullable();
                $table->text('steps_to_reproduce')->nullable();
                $table->text('expected_behavior')->nullable();
                $table->text('actual_behavior')->nullable();
                $table->string('browser')->nullable();
                $table->string('device')->nullable();
                $table->string('screenshot_path')->nullable();
                $table->string('status', 32)->default('new');
                $table->text('admin_notes')->nullable();
                $table->text('resolution_summary')->nullable();
                $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('status_changed_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamp('last_reporter_notified_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'created_at']);
                $table->index(['site', 'severity']);
                $table->index('category');
                $table->index('reporter_email');
            });
        } else {
            $this->ensureRemainingIndexes();
        }

        if (! Schema::hasTable('issue_report_status_updates')) {
            Schema::create('issue_report_status_updates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('issue_report_id')->constrained('issue_reports')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('from_status', 32)->nullable();
                $table->string('to_status', 32);
                $table->text('note')->nullable();
                $table->boolean('notified_reporter')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_report_status_updates');
        Schema::dropIfExists('issue_reports');
    }

    /**
     * Finish a partial production run. The table already exists with long
     * varchar columns (defaultStringLength 191). Prefix lengths keep the key
     * under 1000 bytes on utf8mb4 (32*4 + 32*4 = 256).
     */
    private function ensureRemainingIndexes(): void
    {
        $this->addIndexIfMissing(
            'issue_reports',
            'issue_reports_site_severity_index',
            'ALTER TABLE `issue_reports` ADD INDEX `issue_reports_site_severity_index` (`site`(32), `severity`(32))'
        );
        $this->addIndexIfMissing(
            'issue_reports',
            'issue_reports_category_index',
            'ALTER TABLE `issue_reports` ADD INDEX `issue_reports_category_index` (`category`(32))'
        );
        $this->addIndexIfMissing(
            'issue_reports',
            'issue_reports_reporter_email_index',
            'ALTER TABLE `issue_reports` ADD INDEX `issue_reports_reporter_email_index` (`reporter_email`(191))'
        );
    }

    private function addIndexIfMissing(string $table, string $index, string $statement): void
    {
        if ($this->indexExists($table, $index)) {
            return;
        }

        DB::statement($statement);
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
