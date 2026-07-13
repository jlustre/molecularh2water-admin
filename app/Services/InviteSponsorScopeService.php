<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class InviteSponsorScopeService
{
    public function __construct(
        protected SponsorHierarchyService $hierarchy,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function optionsFor(User $actor): Collection
    {
        if ($actor->isSuperAdmin() || $actor->hasPermission('users.view')) {
            return $this->hierarchy->eligibleSponsors();
        }

        return $this->hierarchy
            ->descendants($actor, true)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function assertInScope(User $actor, int $sponsorUserId): User
    {
        $allowedIds = $this->optionsFor($actor)->pluck('id')->map(fn ($id) => (int) $id);

        if (! $allowedIds->contains($sponsorUserId)) {
            throw ValidationException::withMessages([
                'sponsorUserId' => 'Select a sponsor within your network.',
            ]);
        }

        return User::query()->findOrFail($sponsorUserId);
    }
}
