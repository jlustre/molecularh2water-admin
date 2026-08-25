<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InstallerInstallationStatus;
use App\Enums\InstallerStatus;
use App\Http\Controllers\Controller;
use App\Models\Crm\Customer;
use App\Models\Installer;
use App\Models\InstallerInstallation;
use App\Support\UsStates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstallerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Installer::query()
            ->withCount('installations')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && array_key_exists($request->status, InstallerStatus::options())) {
            $query->where('status', $request->status);
        }

        return view('admin.installers.index', [
            'installers' => $query
                ->paginate((int) $request->integer('per_page', 15))
                ->withQueryString(),
            'statuses' => InstallerStatus::options(),
            'totalCount' => Installer::query()->count(),
            'activeCount' => Installer::query()->where('status', InstallerStatus::Active)->count(),
            'archivedCount' => Installer::query()->where('status', InstallerStatus::Archived)->count(),
            'installationCount' => InstallerInstallation::query()->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.installers.create', [
            'installer' => new Installer([
                'status' => InstallerStatus::Active,
                'state' => 'CA',
            ]),
            'statuses' => InstallerStatus::options(),
            'states' => UsStates::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $installer = Installer::query()->create($this->validatedInstaller($request));

        return redirect()
            ->route('admin.installers.show', $installer)
            ->with('status', 'Installer created.');
    }

    public function show(Request $request, Installer $installer): View
    {
        $historyQuery = InstallerInstallation::query()
            ->with('questionnaire')
            ->where('installer_id', $installer->id)
            ->latest('scheduled_at');

        if (
            $request->filled('history_status')
            && array_key_exists($request->history_status, InstallerInstallationStatus::options())
        ) {
            $historyQuery->where('status', $request->history_status);
        }

        if ($request->filled('history_search')) {
            $search = $request->string('history_search')->toString();

            $historyQuery->where(function ($query) use ($search) {
                $query->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('street_address', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $statusCounts = InstallerInstallation::query()
            ->where('installer_id', $installer->id)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('admin.installers.show', [
            'installer' => $installer,
            'installations' => $historyQuery
                ->paginate((int) $request->integer('per_page', 10), ['*'], 'history_page')
                ->withQueryString(),
            'installationStatuses' => InstallerInstallationStatus::options(),
            'statusCounts' => $statusCounts,
            'directoryCustomers' => Customer::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
            'states' => UsStates::options(),
            'installation' => new InstallerInstallation([
                'status' => InstallerInstallationStatus::Scheduled,
                'scheduled_at' => now()->addDay()->startOfHour(),
            ]),
        ]);
    }

    public function edit(Installer $installer): View
    {
        return view('admin.installers.edit', [
            'installer' => $installer,
            'statuses' => InstallerStatus::options(),
            'states' => UsStates::options(),
        ]);
    }

    public function update(Request $request, Installer $installer): RedirectResponse
    {
        $attributes = $this->validatedInstaller($request, $installer);

        if (($attributes['status'] ?? null) === InstallerStatus::Archived->value) {
            $attributes['archived_at'] = $installer->archived_at ?? now();
        }

        if (($attributes['status'] ?? null) === InstallerStatus::Active->value) {
            $attributes['archived_at'] = null;
        }

        $installer->update($attributes);

        return redirect()
            ->route('admin.installers.show', $installer)
            ->with('status', 'Installer updated.');
    }

    public function archive(Installer $installer): RedirectResponse
    {
        $installer->archive();

        return redirect()
            ->route('admin.installers.index')
            ->with('status', 'Installer archived.');
    }

    public function restore(Installer $installer): RedirectResponse
    {
        $installer->restoreFromArchive();

        return redirect()
            ->route('admin.installers.show', $installer)
            ->with('status', 'Installer restored.');
    }

    public function destroy(Installer $installer): RedirectResponse
    {
        $installer->delete();

        return redirect()
            ->route('admin.installers.index')
            ->with('status', 'Installer and installation history deleted.');
    }

    public function storeInstallation(Request $request, Installer $installer): RedirectResponse
    {
        $installer->installations()->create(
            $this->validatedInstallation($request)
        );

        return redirect()
            ->route('admin.installers.show', $installer)
            ->with('status', 'Installation record added.');
    }

    public function updateInstallation(
        Request $request,
        Installer $installer,
        InstallerInstallation $installation,
    ): RedirectResponse {
        abort_unless($installation->installer_id === $installer->id, 404);

        $installation->update($this->validatedInstallation($request, $installation));

        return redirect()
            ->route('admin.installers.show', $installer)
            ->with('status', 'Installation record updated.');
    }

    public function destroyInstallation(
        Installer $installer,
        InstallerInstallation $installation,
    ): RedirectResponse {
        abort_unless($installation->installer_id === $installer->id, 404);

        $installation->delete();

        return redirect()
            ->route('admin.installers.show', $installer)
            ->with('status', 'Installation record deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedInstaller(Request $request, ?Installer $installer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', Rule::in(UsStates::abbreviations())],
            'status' => ['required', 'string', Rule::in(array_keys(InstallerStatus::options()))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedInstallation(
        Request $request,
        ?InstallerInstallation $installation = null,
    ): array {
        $attributes = $request->validate([
            'crm_customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'status' => ['required', 'string', Rule::in(array_keys(InstallerInstallationStatus::options()))],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', Rule::in(UsStates::abbreviations())],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'scheduled_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'cancelled_at' => ['nullable', 'date'],
            'rescheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $status = InstallerInstallationStatus::from($attributes['status']);

        if ($status === InstallerInstallationStatus::Completed && empty($attributes['completed_at'])) {
            $attributes['completed_at'] = now();
        }

        if ($status === InstallerInstallationStatus::Cancelled && empty($attributes['cancelled_at'])) {
            $attributes['cancelled_at'] = now();
        }

        if ($status === InstallerInstallationStatus::Rescheduled && empty($attributes['rescheduled_at'])) {
            $attributes['rescheduled_at'] = now();
        }

        return $attributes;
    }
}
