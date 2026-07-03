<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SponsorHierarchyService
{
    /**
     * @return Collection<int, User>
     */
    public function upline(User $user): Collection
    {
        $chain = collect();
        $current = $user->sponsor;

        while ($current) {
            if ($chain->contains(fn (User $member) => $member->id === $current->id)) {
                break;
            }

            $chain->push($current);
            $current = $current->sponsor;
        }

        return $chain;
    }

    /**
     * @return Collection<int, User>
     */
    public function descendants(User $user, bool $includeSelf = false): Collection
    {
        $all = $includeSelf ? collect([$user]) : collect();
        $queue = collect([$user->id]);

        while ($queue->isNotEmpty()) {
            $children = User::query()
                ->whereIn('sponsor_id', $queue->all())
                ->orderBy('name')
                ->get();

            $queue = $children->pluck('id');
            $all = $all->merge($children);
        }

        return $all->unique('id')->values();
    }

    /**
     * @return list<array{id: int, name: string, email: string, children: list<mixed>}>
     */
    public function treeFor(User $root): array
    {
        $root->loadMissing(['sponsoredUsers' => fn ($query) => $query->orderBy('name')]);

        return $this->node($root);
    }

    /**
     * @return list<array{id: int, name: string, email: string, children: list<mixed>}>
     */
    public function forestForAdmin(): array
    {
        return User::query()
            ->whereNull('sponsor_id')
            ->orderBy('name')
            ->get()
            ->map(fn (User $root) => $this->treeFor($root))
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, User>
     */
    public function eligibleSponsors(?User $subject = null): Collection
    {
        $query = User::query()->orderBy('name');

        if ($subject) {
            $blockedIds = $this->descendants($subject, true)->pluck('id');
            $query->whereNotIn('id', $blockedIds);
        }

        return $query->get(['id', 'name', 'email']);
    }

    public function assertValidSponsor(?User $subject, ?int $sponsorId, bool $isSuperAdmin = false): void
    {
        if ($isSuperAdmin || ($subject && $subject->hasRole('super-admin'))) {
            return;
        }

        if (! $sponsorId) {
            throw ValidationException::withMessages([
                'sponsor_id' => 'Every member must have a sponsor, except super-admins.',
            ]);
        }

        if ($subject && (int) $sponsorId === (int) $subject->id) {
            throw ValidationException::withMessages([
                'sponsor_id' => 'A member cannot sponsor themselves.',
            ]);
        }

        $blockedIds = $subject
            ? $this->descendants($subject, true)->pluck('id')->all()
            : [];

        if (in_array($sponsorId, $blockedIds, true)) {
            throw ValidationException::withMessages([
                'sponsor_id' => 'A sponsor cannot be part of this member\'s downline.',
            ]);
        }

        if (! User::query()->whereKey($sponsorId)->exists()) {
            throw ValidationException::withMessages([
                'sponsor_id' => 'The selected sponsor does not exist.',
            ]);
        }
    }

    /**
     * @return array{id: int, name: string, email: string, children: list<mixed>}
     */
    private function node(User $user): array
    {
        $user->loadMissing(['sponsoredUsers' => fn ($query) => $query->orderBy('name')]);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'children' => $user->sponsoredUsers
                ->map(fn (User $child) => $this->node($child))
                ->values()
                ->all(),
        ];
    }
}
