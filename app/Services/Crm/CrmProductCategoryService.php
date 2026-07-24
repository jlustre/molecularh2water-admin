<?php

namespace App\Services\Crm;

use App\Enums\Crm\CrmProductKind;
use App\Models\Crm\CrmProductCategory;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CrmProductCategoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): CrmProductCategory
    {
        abort_unless($actor->hasPermission('products.manage'), 403);

        $name = trim((string) Arr::get($data, 'name'));
        $slug = trim((string) Arr::get($data, 'slug', Str::slug($name)));

        return CrmProductCategory::query()->create([
            'name' => $name,
            'slug' => $slug,
            'kind' => Arr::get($data, 'kind', CrmProductKind::Product->value),
            'description' => Arr::get($data, 'description'),
            'is_active' => (bool) Arr::get($data, 'is_active', true),
            'sort_order' => (int) Arr::get($data, 'sort_order', 0),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CrmProductCategory $category, array $data, User $actor): CrmProductCategory
    {
        abort_unless($actor->hasPermission('products.manage'), 403);

        $name = trim((string) Arr::get($data, 'name', $category->name));
        $slug = trim((string) Arr::get($data, 'slug', $category->slug));

        $category->update([
            'name' => $name,
            'slug' => $slug ?: Str::slug($name),
            'kind' => Arr::get($data, 'kind', $category->kind?->value ?? CrmProductKind::Product->value),
            'description' => Arr::get($data, 'description', $category->description),
            'is_active' => array_key_exists('is_active', $data)
                ? (bool) $data['is_active']
                : $category->is_active,
            'sort_order' => (int) Arr::get($data, 'sort_order', $category->sort_order),
        ]);

        return $category->fresh();
    }

    public function delete(CrmProductCategory $category, User $actor): void
    {
        abort_unless($actor->hasPermission('products.manage'), 403);

        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Cannot delete a category that still has products or gifts assigned.',
            ]);
        }

        $category->delete();
    }
}
