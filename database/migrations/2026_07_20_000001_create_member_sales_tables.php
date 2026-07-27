<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('crm_products', 'kind')) {
            Schema::table('crm_products', function (Blueprint $table) {
                $table->string('kind')->default('product')->after('sku');
                $table->index('kind');
            });
        }

        if (! Schema::hasColumn('crm_products', 'inventory_quantity')) {
            Schema::table('crm_products', function (Blueprint $table) {
                $table->unsignedInteger('inventory_quantity')->default(0)->after('unit_price');
            });
        }

        if (! Schema::hasTable('crm_product_categories')) {
            Schema::create('crm_product_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('kind')->default('product');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['kind', 'is_active']);
            });
        }

        if (! Schema::hasColumn('crm_products', 'crm_product_category_id')) {
            Schema::table('crm_products', function (Blueprint $table) {
                $table->foreignId('crm_product_category_id')
                    ->nullable()
                    ->after('kind')
                    ->constrained('crm_product_categories')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('member_sales')) {
            Schema::create('member_sales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->nullableMorphs('contact');
                $table->string('customer_name')->nullable();
                $table->string('customer_phone')->nullable();
                $table->string('customer_email')->nullable();
                $table->string('status')->default('application_started');
                $table->string('business_line')->default('h2s');
                $table->text('notes')->nullable();
                $table->timestamp('application_started_at')->nullable();
                $table->timestamp('financing_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('gifts_total', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'status']);
                $table->index(['business_line', 'status']);
            });
        } elseif (! Schema::hasColumn('member_sales', 'deleted_at')) {
            Schema::table('member_sales', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('member_sale_items')) {
            Schema::create('member_sale_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('member_sale_id')->constrained('member_sales')->cascadeOnDelete();
                $table->foreignId('crm_product_id')->nullable()->constrained('crm_products')->nullOnDelete();
                $table->string('item_kind')->default('product');
                $table->string('name');
                $table->string('sku')->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('line_total', 12, 2)->default(0);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['member_sale_id', 'item_kind']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('member_sale_items');
        Schema::dropIfExists('member_sales');

        if (Schema::hasColumn('crm_products', 'crm_product_category_id')) {
            Schema::table('crm_products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('crm_product_category_id');
            });
        }

        if (Schema::hasColumn('crm_products', 'kind')) {
            Schema::table('crm_products', function (Blueprint $table) {
                $table->dropIndex(['kind']);
                $table->dropColumn('kind');
            });
        }

        if (Schema::hasColumn('crm_products', 'inventory_quantity')) {
            Schema::table('crm_products', function (Blueprint $table) {
                $table->dropColumn('inventory_quantity');
            });
        }

        Schema::dropIfExists('crm_product_categories');
    }
};
