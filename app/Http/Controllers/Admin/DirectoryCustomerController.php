<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Crm\EngagementType;
use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Crm\Customer;
use App\Models\Crm\Lifecycle;
use App\Models\User;
use App\Support\BusinessLineResolver;
use App\Support\UsStates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DirectoryCustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Customer::query()
            ->with([
                'assignedUser',
                'orders' => fn ($orders) => $orders->with(['items.product'])->latest(),
            ])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereRaw("concat(coalesce(first_name, ''), ' ', coalesce(last_name, '')) like ?", ["%{$search}%"])
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhereHas('assignedUser', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('orders', fn ($orderQuery) => $orderQuery->where('order_number', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('state') && in_array($request->state, UsStates::abbreviations(), true)) {
            $query->where('state', $request->state);
        }

        if ($request->filled('engagement_type') && array_key_exists($request->engagement_type, EngagementType::options())) {
            $query->where('engagement_type', $request->engagement_type);
        }

        return view('admin.customers.index', [
            'customers' => $query
                ->paginate((int) $request->integer('per_page', 15))
                ->withQueryString(),
            'states' => UsStates::options(),
            'engagementTypes' => EngagementType::options(),
            'totalCount' => Customer::query()->count(),
            'customerTypeCount' => Customer::query()->where('engagement_type', EngagementType::Customer)->count(),
            'bothTypeCount' => Customer::query()->where('engagement_type', EngagementType::Both)->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.customers.create', [
            'customer' => new Customer([
                'engagement_type' => EngagementType::Customer,
                'state' => 'CA',
                'country' => 'US',
            ]),
            'states' => UsStates::options(),
            'engagementTypes' => EngagementType::options(),
            'consultants' => $this->consultants(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Customer::query()->create($this->validated($request));

        return redirect()
            ->route('admin.customers.index')
            ->with('status', 'Customer created.');
    }

    public function edit(Customer $customer): View
    {
        return view('admin.customers.edit', [
            'customer' => $customer->load('assignedUser'),
            'states' => UsStates::options(),
            'engagementTypes' => EngagementType::options(),
            'consultants' => $this->consultants(),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validated($request, $customer));

        return redirect()
            ->route('admin.customers.index')
            ->with('status', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()
            ->route('admin.customers.index')
            ->with('status', 'Customer removed from the active list.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Customer $customer = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', Rule::in(UsStates::abbreviations())],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'engagement_type' => ['required', 'string', Rule::in(array_keys(EngagementType::options()))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        [$firstName, $lastName] = $this->splitName($data['name']);

        $metadata = is_array($customer?->metadata) ? $customer->metadata : [];
        if (filled(Arr::get($data, 'postal_code'))) {
            $metadata['postal_code'] = $data['postal_code'];
        } else {
            unset($metadata['postal_code']);
        }

        return [
            'lifecycle_id' => Lifecycle::idFor(LeadLifecycle::Client),
            'business_line' => $customer?->business_line?->value
                ?? BusinessLineResolver::defaultForUser($request->user()),
            'status' => LeadStatus::Customer->value,
            'engagement_type' => $data['engagement_type'],
            'temperature' => $customer?->temperature?->value ?? 'cold',
            'score' => $customer?->score ?? 0,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => Arr::get($data, 'email') ?: null,
            'phone' => Arr::get($data, 'phone') ?: null,
            'address' => Arr::get($data, 'street_address') ?: null,
            'city' => Arr::get($data, 'city') ?: null,
            'state' => Arr::get($data, 'state') ?: null,
            'country' => $customer?->country ?: 'US',
            'assigned_user_id' => Arr::get($data, 'assigned_user_id') ?: null,
            'message' => Arr::get($data, 'notes') ?: null,
            'metadata' => $metadata === [] ? null : $metadata,
            'consent_given' => $customer?->consent_given ?? true,
            'converted_at' => $customer?->converted_at ?? now(),
        ];
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [
            $parts[0] ?? 'Customer',
            $parts[1] ?? null,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function consultants()
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', ['consultant', 'admin', 'super-admin']))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
