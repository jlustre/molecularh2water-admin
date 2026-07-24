<?php

namespace App\Services\Portal;

use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\Crm\LeadSource;
use App\Models\Crm\Lifecycle;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\Referral;
use App\Models\User;
use App\Services\Crm\ReferralService;
use App\Support\Crm\CrmScope;
use Illuminate\Database\Eloquent\Model;
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
                [Lead::class, Prospect::class, Customer::class, Recruit::class],
                fn ($query) => CrmScope::contacts($query, $user),
            )
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Lead|Prospect|Customer|Recruit>
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

        $results = collect();

        foreach ([Prospect::class, Customer::class, Lead::class, Recruit::class] as $modelClass) {
            $remaining = $limit - $results->count();

            if ($remaining <= 0) {
                break;
            }

            $batch = CrmScope::contacts($modelClass::query(), $user)
                ->where($nameFilter)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit($remaining)
                ->get(['id', 'first_name', 'last_name', 'phone', 'email', 'lifecycle_id']);

            $results = $results->concat($batch);
        }

        return $results->values();
    }

    public function referrerByKey(string $type, int $contactId, ?User $user = null): Lead|Prospect|Customer|Recruit|null
    {
        $user ??= auth()->user();

        if (! $user) {
            return null;
        }

        $modelClass = match ($type) {
            'lead' => Lead::class,
            'prospect' => Prospect::class,
            'customer' => Customer::class,
            'recruit' => Recruit::class,
            default => null,
        };

        if (! $modelClass) {
            return null;
        }

        return CrmScope::contacts($modelClass::query(), $user)->find($contactId);
    }

    /**
     * @deprecated Use referrerByKey()
     */
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
     *     referrer_lead_id?: int|null,
     *     referrer_type?: string|null,
     *     referrer_name?: string|null,
     *     first_name: string,
     *     last_name?: string|null,
     *     email?: string|null,
     *     phone?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function record(array $data, User $actor): Referral
    {
        $referrer = $this->resolveReferrer($data, $actor);

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

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveReferrer(array $data, User $actor): Model
    {
        $type = $data['referrer_type'] ?? null;
        $id = $data['referrer_lead_id'] ?? null;

        if ($type && $id) {
            $referrer = $this->referrerByKey((string) $type, (int) $id, $actor);

            if (! $referrer) {
                throw ValidationException::withMessages([
                    'referrer_lead_id' => 'Select a valid referring person.',
                ]);
            }

            return $referrer;
        }

        $name = trim((string) ($data['referrer_name'] ?? ''));

        if (strlen($name) < 2) {
            throw ValidationException::withMessages([
                'referrer_search' => 'Enter or select the referring person.',
            ]);
        }

        return $this->createExternalReferrer($name, $actor);
    }

    private function createExternalReferrer(string $fullName, User $actor): Lead
    {
        [$firstName, $lastName] = $this->splitName($fullName);
        $referralSource = LeadSource::query()->where('slug', 'referral')->first();
        $referralFunnel = Funnel::query()
            ->where('slug', config('crm.referral_funnel_slug', 'referral-funnel'))
            ->first();
        $entryStage = $referralFunnel
            ? FunnelStage::query()
                ->where('funnel_id', $referralFunnel->id)
                ->where('slug', config('crm.referral_entry_stage', 'referral-received'))
                ->first()
            : null;

        return Lead::query()->create([
            'lifecycle_id' => Lifecycle::idFor(LeadLifecycle::Lead),
            'status' => LeadStatus::New,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'message' => 'External referrer — not an existing customer or member at time of referral.',
            'lead_source_id' => $referralSource?->id,
            'funnel_id' => $referralFunnel?->id,
            'funnel_stage_id' => $entryStage?->id,
            'assigned_user_id' => $actor->id,
            'business_line' => 'both',
        ]);
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function splitName(string $fullName): array
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $fullName) ?? $fullName);
        $parts = explode(' ', $normalized, 2);

        return [$parts[0], $parts[1] ?? null];
    }
}
