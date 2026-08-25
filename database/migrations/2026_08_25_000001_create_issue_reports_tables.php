<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code')->unique();
            $table->string('reporter_name');
            $table->string('reporter_email');
            $table->string('reporter_phone')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source');
            $table->string('site');
            $table->string('category');
            $table->string('severity');
            $table->string('title');
            $table->text('description');
            $table->string('page_url')->nullable();
            $table->text('steps_to_reproduce')->nullable();
            $table->text('expected_behavior')->nullable();
            $table->text('actual_behavior')->nullable();
            $table->string('browser')->nullable();
            $table->string('device')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->string('status')->default('new');
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

        Schema::create('issue_report_status_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_report_id')->constrained('issue_reports')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('note')->nullable();
            $table->boolean('notified_reporter')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_report_status_updates');
        Schema::dropIfExists('issue_reports');
    }
};
