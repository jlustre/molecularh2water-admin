<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installation_questionnaires', function (Blueprint $table) {
            if (! Schema::hasColumn('installation_questionnaires', 'seller_id')) {
                $table->foreignId('seller_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        if (! Schema::hasColumn('installation_questionnaires', 'seller_id')) {
            return;
        }

        $questionnaires = DB::table('installation_questionnaires')
            ->whereNull('seller_id')
            ->whereNotNull('email')
            ->get(['id', 'email']);

        foreach ($questionnaires as $questionnaire) {
            $email = strtolower(trim((string) $questionnaire->email));

            if ($email === '') {
                continue;
            }

            $sellerId = null;

            if (Schema::hasTable('customers')) {
                $sellerId = DB::table('customers')
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->whereNotNull('assigned_user_id')
                    ->when(Schema::hasColumn('customers', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
                    ->orderByDesc('id')
                    ->value('assigned_user_id');
            }

            if (! $sellerId && Schema::hasTable('member_sales')) {
                $sellerId = DB::table('member_sales')
                    ->whereRaw('LOWER(customer_email) = ?', [$email])
                    ->whereNotNull('user_id')
                    ->when(Schema::hasColumn('member_sales', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
                    ->orderByDesc('id')
                    ->value('user_id');
            }

            if ($sellerId) {
                DB::table('installation_questionnaires')
                    ->where('id', $questionnaire->id)
                    ->update(['seller_id' => $sellerId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('installation_questionnaires', function (Blueprint $table) {
            if (Schema::hasColumn('installation_questionnaires', 'seller_id')) {
                $table->dropConstrainedForeignId('seller_id');
            }
        });
    }
};
