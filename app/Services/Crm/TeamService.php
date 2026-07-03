<?php

namespace App\Services\Crm;

use App\Models\Crm\Team;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeamService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $memberIds
     */
    public function create(array $data, array $memberIds = []): Team
    {
        $team = Team::query()->create([
            'name' => trim((string) Arr::get($data, 'name')),
            'slug' => $this->uniqueSlug(Arr::get($data, 'slug') ?: Arr::get($data, 'name')),
            'description' => Arr::get($data, 'description'),
            'manager_id' => Arr::get($data, 'manager_id') ?: null,
            'is_active' => (bool) Arr::get($data, 'is_active', true),
        ]);

        $this->syncMembers($team, $memberIds);

        return $team->fresh(['manager', 'users']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $memberIds
     */
    public function update(Team $team, array $data, array $memberIds = []): Team
    {
        $team->update([
            'name' => trim((string) Arr::get($data, 'name', $team->name)),
            'slug' => $this->uniqueSlug(
                Arr::get($data, 'slug', $team->slug),
                $team->id,
            ),
            'description' => Arr::get($data, 'description', $team->description),
            'manager_id' => Arr::get($data, 'manager_id', $team->manager_id),
            'is_active' => (bool) Arr::get($data, 'is_active', $team->is_active),
        ]);

        $this->syncMembers($team, $memberIds);

        return $team->fresh(['manager', 'users']);
    }

    public function delete(Team $team): void
    {
        if ($team->leads()->exists()) {
            throw ValidationException::withMessages([
                'item' => 'Cannot delete a team with assigned leads.',
            ]);
        }

        $team->delete();
    }

    /**
     * @param  list<int>  $memberIds
     */
    private function syncMembers(Team $team, array $memberIds): void
    {
        $memberIds = collect($memberIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $sync = $memberIds->mapWithKeys(fn (int $id) => [
            $id => ['role' => $team->manager_id === $id ? 'lead' : 'member'],
        ])->all();

        $team->users()->sync($sync);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'team';
        $candidate = $base;
        $counter = 2;

        while (Team::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
