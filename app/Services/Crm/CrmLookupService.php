<?php

namespace App\Services\Crm;

use App\Models\Crm\ActivityType;
use App\Models\Crm\LeadSource;
use App\Models\Crm\LostReason;
use App\Models\Crm\Tag;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CrmLookupService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertLeadSource(?int $id, array $data): LeadSource
    {
        $name = trim((string) Arr::get($data, 'name'));
        $slug = Str::slug(Arr::get($data, 'slug') ?: $name);

        if ($id) {
            $source = LeadSource::query()->findOrFail($id);
            $source->update([
                'name' => $name,
                'slug' => $this->uniqueSourceSlug($slug, $id),
                'description' => Arr::get($data, 'description'),
                'is_active' => (bool) Arr::get($data, 'is_active', true),
                'sort_order' => (int) Arr::get($data, 'sort_order', $source->sort_order),
            ]);

            return $source->fresh();
        }

        return LeadSource::query()->create([
            'name' => $name,
            'slug' => $this->uniqueSourceSlug($slug),
            'description' => Arr::get($data, 'description'),
            'is_active' => (bool) Arr::get($data, 'is_active', true),
            'sort_order' => (int) Arr::get($data, 'sort_order', (LeadSource::query()->max('sort_order') ?? 0) + 1),
        ]);
    }

    public function deleteLeadSource(LeadSource $source): void
    {
        if ($source->leads()->exists()) {
            throw ValidationException::withMessages([
                'item' => 'Cannot delete a lead source that is in use.',
            ]);
        }

        $source->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertTag(?int $id, array $data): Tag
    {
        $name = trim((string) Arr::get($data, 'name'));
        $slug = Str::slug(Arr::get($data, 'slug') ?: $name);

        if ($id) {
            $tag = Tag::query()->findOrFail($id);
            $tag->update([
                'name' => $name,
                'slug' => $this->uniqueTagSlug($slug, $id),
                'color' => Arr::get($data, 'color'),
            ]);

            return $tag->fresh();
        }

        return Tag::query()->create([
            'name' => $name,
            'slug' => $this->uniqueTagSlug($slug),
            'color' => Arr::get($data, 'color'),
        ]);
    }

    public function deleteTag(Tag $tag): void
    {
        if ($tag->leads()->exists()) {
            throw ValidationException::withMessages([
                'item' => 'Cannot delete a tag that is assigned to records.',
            ]);
        }

        $tag->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertActivityType(?int $id, array $data): ActivityType
    {
        $name = trim((string) Arr::get($data, 'name'));
        $slug = Str::slug(Arr::get($data, 'slug') ?: $name);

        if ($id) {
            $type = ActivityType::query()->findOrFail($id);
            $type->update([
                'name' => $name,
                'slug' => $this->uniqueActivitySlug($slug, $id),
                'icon' => Arr::get($data, 'icon'),
                'is_active' => (bool) Arr::get($data, 'is_active', true),
                'sort_order' => (int) Arr::get($data, 'sort_order', $type->sort_order),
            ]);

            return $type->fresh();
        }

        return ActivityType::query()->create([
            'name' => $name,
            'slug' => $this->uniqueActivitySlug($slug),
            'icon' => Arr::get($data, 'icon'),
            'is_active' => (bool) Arr::get($data, 'is_active', true),
            'sort_order' => (int) Arr::get($data, 'sort_order', (ActivityType::query()->max('sort_order') ?? 0) + 1),
        ]);
    }

    public function deleteActivityType(ActivityType $type): void
    {
        if ($type->activities()->exists()) {
            throw ValidationException::withMessages([
                'item' => 'Cannot delete an activity type that has logged activities.',
            ]);
        }

        $type->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertLostReason(?int $id, array $data): LostReason
    {
        $name = trim((string) Arr::get($data, 'name'));
        $slug = Str::slug(Arr::get($data, 'slug') ?: $name);

        if ($id) {
            $reason = LostReason::query()->findOrFail($id);
            $reason->update([
                'name' => $name,
                'slug' => $this->uniqueLostReasonSlug($slug, $id),
                'description' => Arr::get($data, 'description'),
                'requires_detail' => (bool) Arr::get($data, 'requires_detail', false),
                'is_active' => (bool) Arr::get($data, 'is_active', true),
                'sort_order' => (int) Arr::get($data, 'sort_order', $reason->sort_order),
            ]);

            return $reason->fresh();
        }

        return LostReason::query()->create([
            'name' => $name,
            'slug' => $this->uniqueLostReasonSlug($slug),
            'description' => Arr::get($data, 'description'),
            'requires_detail' => (bool) Arr::get($data, 'requires_detail', false),
            'is_active' => (bool) Arr::get($data, 'is_active', true),
            'sort_order' => (int) Arr::get($data, 'sort_order', (LostReason::query()->max('sort_order') ?? 0) + 1),
        ]);
    }

    public function deleteLostReason(LostReason $reason): void
    {
        if ($reason->leads()->exists()) {
            throw ValidationException::withMessages([
                'item' => 'Cannot delete a lost reason that is assigned to records.',
            ]);
        }

        $reason->delete();
    }

    /**
     * @return array{lost_reason_id: ?int, lost_reason: ?string}
     */
    public function resolveLeadLostReason(?int $reasonId, ?string $detail = null, bool $required = false): array
    {
        if (! $reasonId) {
            if ($required) {
                throw ValidationException::withMessages([
                    'lost_reason_id' => 'A lost reason is required.',
                ]);
            }

            return ['lost_reason_id' => null, 'lost_reason' => null];
        }

        $reason = LostReason::query()->where('is_active', true)->find($reasonId);

        if (! $reason) {
            throw ValidationException::withMessages([
                'lost_reason_id' => 'Invalid lost reason.',
            ]);
        }

        $detail = trim((string) $detail);

        if ($reason->requires_detail && blank($detail)) {
            throw ValidationException::withMessages([
                'lost_reason_detail' => 'Please describe the reason.',
            ]);
        }

        return [
            'lost_reason_id' => $reason->id,
            'lost_reason' => $reason->requires_detail ? $detail : $reason->name,
        ];
    }

    private function uniqueSourceSlug(string $slug, ?int $ignoreId = null): string
    {
        return $this->uniqueSlug(LeadSource::query(), $slug, $ignoreId);
    }

    private function uniqueTagSlug(string $slug, ?int $ignoreId = null): string
    {
        return $this->uniqueSlug(Tag::query(), $slug, $ignoreId);
    }

    private function uniqueActivitySlug(string $slug, ?int $ignoreId = null): string
    {
        return $this->uniqueSlug(ActivityType::query(), $slug, $ignoreId);
    }

    private function uniqueLostReasonSlug(string $slug, ?int $ignoreId = null): string
    {
        return $this->uniqueSlug(LostReason::query(), $slug, $ignoreId);
    }

    private function uniqueSlug($query, string $slug, ?int $ignoreId = null): string
    {
        $base = $slug !== '' ? $slug : 'item';
        $candidate = $base;
        $counter = 2;

        while ((clone $query)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
