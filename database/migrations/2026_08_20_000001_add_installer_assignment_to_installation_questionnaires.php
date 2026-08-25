<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installation_questionnaires', function (Blueprint $table) {
            if (! Schema::hasColumn('installation_questionnaires', 'installer_id')) {
                $table->foreignId('installer_id')
                    ->nullable()
                    ->constrained('installers')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('installation_questionnaires', 'assigned_by_user_id')) {
                $table->foreignId('assigned_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('installation_questionnaires', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable();
            }

            if (! Schema::hasColumn('installation_questionnaires', 'assignment_notes')) {
                $table->text('assignment_notes')->nullable();
            }
        });

        Schema::table('installer_installations', function (Blueprint $table) {
            if (! Schema::hasColumn('installer_installations', 'installation_questionnaire_id')) {
                $table->foreignId('installation_questionnaire_id')
                    ->nullable()
                    ->constrained('installation_questionnaires')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('installer_installations', function (Blueprint $table) {
            if (Schema::hasColumn('installer_installations', 'installation_questionnaire_id')) {
                $table->dropConstrainedForeignId('installation_questionnaire_id');
            }
        });

        Schema::table('installation_questionnaires', function (Blueprint $table) {
            if (Schema::hasColumn('installation_questionnaires', 'assigned_by_user_id')) {
                $table->dropConstrainedForeignId('assigned_by_user_id');
            }

            if (Schema::hasColumn('installation_questionnaires', 'installer_id')) {
                $table->dropConstrainedForeignId('installer_id');
            }

            $columns = collect(['assigned_at', 'assignment_notes'])
                ->filter(fn (string $column) => Schema::hasColumn('installation_questionnaires', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
