<?php

namespace Database\Seeders;

use App\Enums\BusinessLine;
use App\Enums\Crm\CrmProductKind;
use App\Enums\Crm\MemberSaleStatus;
use App\Models\Crm\CrmProduct;
use App\Models\Crm\CrmProductCategory;
use App\Models\Crm\MemberSale;
use App\Models\Crm\MemberSaleItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class MemberSalesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CrmSeeder::class);

        $seller = User::query()
            ->where('email', 'jclustre@gmail.com')
            ->orWhere('name', 'like', '%Lustre%')
            ->first();

        if (! $seller) {
            $this->command?->warn('No Lustre user found. Skipping MemberSalesDemoSeeder.');

            return;
        }

        $machineCategory = CrmProductCategory::query()->updateOrCreate(
            ['slug' => 'h2-machines'],
            [
                'name' => 'H2 Machines',
                'kind' => CrmProductKind::Product,
                'description' => 'Hydrogen water machines',
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        $giftCategory = CrmProductCategory::query()->updateOrCreate(
            ['slug' => 'customer-gifts'],
            [
                'name' => 'Customer Gifts',
                'kind' => CrmProductKind::Gift,
                'description' => 'Gifts for customers and referrals',
                'is_active' => true,
                'sort_order' => 2,
            ],
        );

        $product = CrmProduct::query()->where('sku', 'H2-ULTRA-PRO')->first();
        $gift = CrmProduct::query()->where('sku', 'GIFT-WELCOME')->first();

        if ($product) {
            $product->update([
                'crm_product_category_id' => $machineCategory->id,
                'category' => $machineCategory->name,
                'inventory_quantity' => 12,
            ]);
        }

        if ($gift) {
            $gift->update([
                'crm_product_category_id' => $giftCategory->id,
                'category' => $giftCategory->name,
                'inventory_quantity' => 20,
            ]);
        }

        $sale = MemberSale::query()->updateOrCreate(
            [
                'user_id' => $seller->id,
                'customer_name' => 'Alex Rivera',
            ],
            [
                'customer_phone' => '555-0101',
                'customer_email' => 'alex.rivera@example.com',
                'status' => MemberSaleStatus::Financing,
                'business_line' => BusinessLine::Both,
                'notes' => 'Seeded financing sale for widget testing.',
                'application_started_at' => now()->subDays(3),
                'financing_at' => now()->subDay(),
                'subtotal' => (float) ($product?->unit_price ?? 3499),
                'gifts_total' => (float) ($gift?->unit_price ?? 75),
                'total' => (float) (($product?->unit_price ?? 3499) + ($gift?->unit_price ?? 75)),
                'created_by' => $seller->id,
            ],
        );

        $sale->items()->delete();

        if ($product) {
            MemberSaleItem::query()->create([
                'member_sale_id' => $sale->id,
                'crm_product_id' => $product->id,
                'item_kind' => CrmProductKind::Product,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantity' => 1,
                'unit_price' => $product->unit_price,
                'line_total' => $product->unit_price,
                'sort_order' => 1,
            ]);
        }

        if ($gift) {
            MemberSaleItem::query()->create([
                'member_sale_id' => $sale->id,
                'crm_product_id' => $gift->id,
                'item_kind' => CrmProductKind::Gift,
                'name' => $gift->name,
                'sku' => $gift->sku,
                'quantity' => 1,
                'unit_price' => $gift->unit_price,
                'line_total' => $gift->unit_price,
                'sort_order' => 2,
            ]);
        }

        MemberSale::query()->updateOrCreate(
            [
                'user_id' => $seller->id,
                'customer_name' => 'Jordan Kim',
            ],
            [
                'customer_phone' => '555-0102',
                'status' => MemberSaleStatus::Delivered,
                'business_line' => BusinessLine::Both,
                'subtotal' => 2199,
                'gifts_total' => 50,
                'total' => 2249,
                'delivered_at' => now()->subDay(),
                'application_started_at' => now()->subDays(10),
                'financing_at' => now()->subDays(8),
                'approved_at' => now()->subDays(5),
                'created_by' => $seller->id,
            ],
        );

        $this->command?->info('Member sales demo data ready for '.$seller->name);
    }
}
