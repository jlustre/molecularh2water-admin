<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers') && ! Schema::hasColumn('customers', 'engagement_type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('engagement_type', 1)->default('C')->after('status');
                $table->index('engagement_type');
            });
        }

        if (Schema::hasTable('recruits') && ! Schema::hasColumn('recruits', 'engagement_type')) {
            Schema::table('recruits', function (Blueprint $table) {
                $table->string('engagement_type', 1)->default('R')->after('status');
                $table->index('engagement_type');
            });
        }

        Schema::table('directory_customers', function (Blueprint $table) {
            if (! Schema::hasColumn('directory_customers', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('notes')->constrained('orders')->nullOnDelete();
            }

            if (! Schema::hasColumn('directory_customers', 'assigned_user_id')) {
                $table->foreignId('assigned_user_id')->nullable()->after('order_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('directory_customers', 'crm_customer_id')) {
                $table->foreignId('crm_customer_id')->nullable()->after('assigned_user_id')->constrained('customers')->nullOnDelete();
            }

            if (! Schema::hasColumn('directory_customers', 'engagement_type')) {
                $table->string('engagement_type', 1)->nullable()->after('crm_customer_id');
            }

            if (! Schema::hasColumn('directory_customers', 'products_summary')) {
                $table->json('products_summary')->nullable()->after('engagement_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('directory_customers', function (Blueprint $table) {
            if (Schema::hasColumn('directory_customers', 'order_id')) {
                $table->dropConstrainedForeignId('order_id');
            }

            if (Schema::hasColumn('directory_customers', 'assigned_user_id')) {
                $table->dropConstrainedForeignId('assigned_user_id');
            }

            if (Schema::hasColumn('directory_customers', 'crm_customer_id')) {
                $table->dropConstrainedForeignId('crm_customer_id');
            }

            $columns = collect(['engagement_type', 'products_summary'])
                ->filter(fn (string $column) => Schema::hasColumn('directory_customers', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'engagement_type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex(['engagement_type']);
                $table->dropColumn('engagement_type');
            });
        }

        if (Schema::hasTable('recruits') && Schema::hasColumn('recruits', 'engagement_type')) {
            Schema::table('recruits', function (Blueprint $table) {
                $table->dropIndex(['engagement_type']);
                $table->dropColumn('engagement_type');
            });
        }
    }
};
