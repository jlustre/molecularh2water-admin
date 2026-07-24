<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('crm_products', 'reorder_level')) {
            Schema::table('crm_products', function (Blueprint $table) {
                $table->unsignedInteger('reorder_level')->default(5)->after('inventory_quantity');
            });
        }

        if (! Schema::hasColumn('crm_products', 'reserved_quantity')) {
            Schema::table('crm_products', function (Blueprint $table) {
                $table->unsignedInteger('reserved_quantity')->default(0)->after('reorder_level');
            });
        }

        if (
            Schema::hasTable('member_sales')
            && ! Schema::hasColumn('member_sales', 'inventory_reserved')
        ) {
            Schema::table('member_sales', function (Blueprint $table) {
                $table->boolean('inventory_reserved')->default(false)->after('total');
            });
        }

        if (
            Schema::hasTable('member_sales')
            && ! Schema::hasColumn('member_sales', 'inventory_deducted')
        ) {
            Schema::table('member_sales', function (Blueprint $table) {
                $table->boolean('inventory_deducted')->default(false)->after('inventory_reserved');
            });
        }

        if (! Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('crm_product_id')->constrained('crm_products')->cascadeOnDelete();
                $table->string('type');
                $table->integer('quantity_delta');
                $table->unsignedInteger('quantity_before');
                $table->unsignedInteger('quantity_after');
                $table->unsignedInteger('reserved_before')->default(0);
                $table->unsignedInteger('reserved_after')->default(0);
                $table->string('reason')->nullable();
                $table->text('notes')->nullable();
                $table->nullableMorphs('reference');
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();

                $table->index(['crm_product_id', 'created_at']);
                $table->index(['type', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');

        if (
            Schema::hasTable('member_sales')
            && Schema::hasColumn('member_sales', 'inventory_deducted')
        ) {
            Schema::table('member_sales', function (Blueprint $table) {
                $table->dropColumn('inventory_deducted');
            });
        }

        if (
            Schema::hasTable('member_sales')
            && Schema::hasColumn('member_sales', 'inventory_reserved')
        ) {
            Schema::table('member_sales', function (Blueprint $table) {
                $table->dropColumn('inventory_reserved');
            });
        }

        if (Schema::hasColumn('crm_products', 'reserved_quantity')) {
            Schema::table('crm_products', function (Blueprint $table) {
                $table->dropColumn('reserved_quantity');
            });
        }

        if (Schema::hasColumn('crm_products', 'reorder_level')) {
            Schema::table('crm_products', function (Blueprint $table) {
                $table->dropColumn('reorder_level');
            });
        }
    }
};
