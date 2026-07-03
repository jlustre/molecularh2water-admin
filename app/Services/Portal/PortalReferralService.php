<?php

namespace App\Services\Portal;

use App\Models\Crm\Customer;
use App\Models\Crm\Prospect;
use App\Models\Crm\Referral;
use App\Models\User;
use App\Services\Crm\ReferralService;
use App\Support\Crm\CrmScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PortalReferralService
{
    /**
     * @return Collection<int, Referral>
     */
    public function recentReferrals(?User $user = null, int $limit = 30): Collection
    {
        $user ??= auth()->user();

        if (! $user) {
            return collect();
        }

        return Referral::query()
            ->with(['referrer', 'referred.stage', 'loggedBy'])
            ->whereHasMorph(
                'referrer',
                [Prospect::class, Customer::class],
                fn ($query) => CrmScope::contacts($query, $user),
            )
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Prospect|Customer>
     */
    public function searchReferrers(string $query, ?User $user = null, int $limit = 8): Collection
    {
        $user ??= auth()->user();
        $term = trim($query);

        if (! $user || strlen($term) < 3) {
            return collect();
        }

        $like = '%'.$term.'%';
        $nameFilter = function ($builder) use ($like) {
            $builder->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like);
        };

        $prospects = CrmScope::contacts(Prospect::query(), $user)
            ->where($nameFilter)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit($limit)
            ->get(['id', 'first_name', 'last_name', 'phone', 'email', 'lifecycle_id']);

        $remaining = max(0, $limit - $prospects->count());

        $customers = $remaining > 0
            ? CrmScope::contacts(Customer::query(), $user)
                ->where($nameFilter)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit($remaining)
                ->get(['id', 'first_name', 'last_name', 'phone', 'email', 'lifecycle_id'])
            : collect();

        return $prospects->concat($customers)->values();
    }

    public function referrerById(int $contactId, ?User $user = null): Prospect|Customer|null
    {
        $user ??= auth()->user();

        if (! $user) {
            return null;
        }

        return CrmScope::contacts(Prospect::query(), $user)->find($contactId)
            ?? CrmScope::contacts(Customer::query(), $user)->find($contactId);
    }

    /**
     * @param  array{
     *     referrer_lead_id: int,
     *     first_name: string,
     *     last_name?: string|null,
     *     email?: string|null,
     *     phone?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function record(array $data, User $actor): Referral
    {
        $referrer = $this->referrerById((int) $data['referrer_lead_id'], $actor);

        if (! $referrer) {
            throw ValidationException::withMessages([
                'referrer_lead_id' => 'Select a valid referring person.',
            ]);
        }

        if (! Gate::forUser($actor)->allows('update', $referrer)) {
            abort(403);
        }

        return app(ReferralService::class)->recordReferral($referrer, [
            'first_name' => trim($data['first_name']),
            'last_name' => filled($data['last_name'] ?? null) ? trim((string) $data['last_name']) : null,
            'email' => filled($data['email'] ?? null) ? trim((string) $data['email']) : null,
            'phone' => filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
            'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
        ], $actor);
    }
}
