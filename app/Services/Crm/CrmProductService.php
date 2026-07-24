<?php

namespace App\Services\Crm;

use App\Enums\Crm\CrmProductKind;
use App\Models\Crm\CrmProduct;
use App\Models\Crm\CrmProductCategory;
use App\Models\User;
use Illuminate\Support\Arr;

class CrmProductService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): CrmProduct
    {
        abort_unless($actor->hasPermission('products.manage'), 403);

        $category = $this->resolveCategory($data);

        return CrmProduct::query()->create([
            'sku' => trim((string) Arr::get($data, 'sku')),
            'name' => trim((string) Arr::get($data, 'name')),
            'kind' => Arr::get($data, 'kind', CrmProductKind::Product->value),
            'crm_product_category_id' => $category?->id,
            'category' => $category?->name,
            'description' => Arr::get($data, 'description'),
            'unit_price' => Arr::get($data, 'unit_price', 0),
            'inventory_quantity' => max(0, (int) Arr::get($data, 'inventory_quantity', 0)),
            'reorder_level' => max(0, (int) Arr::get($data, 'reorder_level', 5)),
            'is_active' => (bool) Arr::get($data, 'is_active', true),
            'sort_order' => (int) Arr::get($data, 'sort_order', 0),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CrmProduct $product, array $data, User $actor): CrmProduct
    {
        abort_unless($actor->hasPermission('products.manage'), 403);

        $category = $this->resolveCategory($data, $product);

        $product->update([
            'sku' => trim((string) Arr::get($data, 'sku', $product->sku)),
            'name' => trim((string) Arr::get($data, 'name', $product->name)),
            'kind' => Arr::get($data, 'kind', $product->kind?->value ?? CrmProductKind::Product->value),
            'crm_product_category_id' => $category?->id,
            'category' => $category?->name ?? Arr::get($data, 'category', $product->category),
            'description' => Arr::get($data, 'description', $product->description),
            'unit_price' => Arr::get($data, 'unit_price', $product->unit_price),
            'reorder_level' => max(0, (int) Arr::get($data, 'reorder_level', $product->reorder_level ?? 5)),
            'is_active' => array_key_exists('is_active', $data)
                ? (bool) $data['is_active']
                : $product->is_active,
            'sort_order' => (int) Arr::get($data, 'sort_order', $product->sort_order),
        ]);

        if (array_key_exists('inventory_quantity', $data)) {
            app(InventoryService::class)->setOnHand(
                $product->fresh(),
                (int) $data['inventory_quantity'],
                $actor,
                'Updated via product form',
            );
        }

        return $product->fresh(['productCategory']);
    }

    public function delete(CrmProduct $product, User $actor): void
    {
        abort_unless($actor->hasPermission('products.manage'), 403);

        if ($product->isReferenced()) {
            $product->update(['is_active' => false]);

            return;
        }

        $product->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveCategory(array $data, ?CrmProduct $product = null): ?CrmProductCategory
    {
        $categoryId = Arr::get($data, 'crm_product_category_id');

        if ($categoryId) {
            return CrmProductCategory::query()->find($categoryId);
        }

        if ($product?->crm_product_category_id) {
            return $product->productCategory;
        }

        return null;
    }
}
