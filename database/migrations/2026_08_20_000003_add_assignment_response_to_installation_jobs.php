<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installer_installations', function (Blueprint $table) {
            if (! Schema::hasColumn('installer_installations', 'assignment_response')) {
                $table->string('assignment_response')->nullable()->after('status');
            }

            if (! Schema::hasColumn('installer_installations', 'assignment_responded_at')) {
                $table->timestamp('assignment_responded_at')->nullable()->after('assignment_response');
            }

            if (! Schema::hasColumn('installer_installations', 'assignment_rejection_reason')) {
                $table->string('assignment_rejection_reason')->nullable()->after('assignment_responded_at');
            }

            if (! Schema::hasColumn('installer_installations', 'assignment_rejection_notes')) {
                $table->text('assignment_rejection_notes')->nullable()->after('assignment_rejection_reason');
            }
        });

        Schema::table('installation_questionnaires', function (Blueprint $table) {
            if (! Schema::hasColumn('installation_questionnaires', 'assignment_response')) {
                $table->string('assignment_response')->nullable()->after('assignment_notes');
            }

            if (! Schema::hasColumn('installation_questionnaires', 'assignment_responded_at')) {
                $table->timestamp('assignment_responded_at')->nullable()->after('assignment_response');
            }

            if (! Schema::hasColumn('installation_questionnaires', 'assignment_rejection_reason')) {
                $table->string('assignment_rejection_reason')->nullable()->after('assignment_responded_at');
            }

            if (! Schema::hasColumn('installation_questionnaires', 'assignment_rejection_notes')) {
                $table->text('assignment_rejection_notes')->nullable()->after('assignment_rejection_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('installer_installations', function (Blueprint $table) {
            $columns = collect([
                'assignment_response',
                'assignment_responded_at',
                'assignment_rejection_reason',
                'assignment_rejection_notes',
            ])->filter(fn (string $column) => Schema::hasColumn('installer_installations', $column))->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('installation_questionnaires', function (Blueprint $table) {
            $columns = collect([
                'assignment_response',
                'assignment_responded_at',
                'assignment_rejection_reason',
                'assignment_rejection_notes',
            ])->filter(fn (string $column) => Schema::hasColumn('installation_questionnaires', $column))->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
