<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InstallerStatus;
use App\Http\Controllers\Controller;
use App\Models\InstallationQuestionnaire;
use App\Models\Installer;
use App\Models\User;
use App\Services\Admin\InstallationQuestionnaireAssignment;
use App\Support\FrontendUrl;
use App\Support\UsStates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InstallationQuestionnaireController extends Controller
{
    public function __construct(
        private readonly InstallationQuestionnaireAssignment $assignments,
    ) {}

    public function index(Request $request): View
    {
        $query = InstallationQuestionnaire::query()
            ->with(['installer', 'assignedBy', 'seller'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('postal_code', 'like', "%{$search}%")
                    ->orWhere('property_type', 'like', "%{$search}%")
                    ->orWhereHas('installer', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('company', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%");
                    })
                    ->orWhereHas('seller', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('property_type')) {
            $query->where('property_type', $request->string('property_type')->toString());
        }

        if ($request->assignment === 'unassigned') {
            $query->unassigned();
        }

        if ($request->assignment === 'assigned') {
            $query->assigned();
        }

        if ($request->filled('seller_id')) {
            if ($request->seller_id === 'unassigned') {
                $query->whereNull('seller_id');
            } else {
                $query->where('seller_id', $request->integer('seller_id'));
            }
        }

        if ($request->filled('submitted')) {
            match ($request->submitted) {
                '7_days' => $query->where('created_at', '>=', now()->subDays(7)),
                '30_days' => $query->where('created_at', '>=', now()->subDays(30)),
                '90_days' => $query->where('created_at', '>=', now()->subDays(90)),
                default => null,
            };
        }

        $questionnaires = $query
            ->paginate((int) $request->integer('per_page', 10))
            ->withQueryString();

        return view('admin.installation-questionnaires.index', [
            'assignableInstallers' => $this->assignableInstallers(),
            'assignedCount' => InstallationQuestionnaire::query()->assigned()->count(),
            'installationUrl' => FrontendUrl::installationQuestionnaire(),
            'newSubmissions' => InstallationQuestionnaire::query()
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'propertyTypes' => InstallationQuestionnaire::query()
                ->select('property_type')
                ->distinct()
                ->orderBy('property_type')
                ->pluck('property_type'),
            'questionnaires' => $questionnaires,
            'sellers' => $this->consultants(),
            'totalSubmissions' => InstallationQuestionnaire::query()->count(),
            'unassignedCount' => InstallationQuestionnaire::query()->unassigned()->count(),
        ]);
    }

    public function show(InstallationQuestionnaire $installationQuestionnaire): View
    {
        $installationQuestionnaire->load([
            'installer',
            'assignedBy',
            'installerInstallations',
            'seller',
        ]);

        return view('admin.installation-questionnaires.show', [
            'assignableInstallers' => $this->assignableInstallers($installationQuestionnaire),
            'currentJob' => $installationQuestionnaire->currentInstallerInstallation(),
            'installationUrl' => FrontendUrl::installationQuestionnaire(),
            'questionnaire' => $installationQuestionnaire,
        ]);
    }

    public function edit(InstallationQuestionnaire $installationQuestionnaire): View
    {
        return view('admin.installation-questionnaires.edit', [
            'consultants' => $this->consultants($installationQuestionnaire),
            'questionnaire' => $installationQuestionnaire->loadMissing('seller'),
        ]);
    }

    public function update(
        Request $request,
        InstallationQuestionnaire $installationQuestionnaire,
    ): RedirectResponse {
        $attributes = $request->validate($this->validationRules());

        $installationQuestionnaire->update([
            ...$attributes,
            'existing_equipment' => $attributes['existing_equipment'] ?? [],
        ]);

        return redirect()
            ->route('admin.installation-questionnaires.show', $installationQuestionnaire)
            ->with('status', 'Installation questionnaire updated.');
    }

    public function destroy(InstallationQuestionnaire $installationQuestionnaire): RedirectResponse
    {
        foreach ($installationQuestionnaire->sinkPhotoItems() as $photo) {
            Storage::disk('public')->delete($photo['path']);
        }

        $installationQuestionnaire->delete();

        return redirect()
            ->route('admin.installation-questionnaires.index')
            ->with('status', 'Installation questionnaire deleted.');
    }

    public function assignInstaller(
        Request $request,
        InstallationQuestionnaire $installationQuestionnaire,
    ): RedirectResponse {
        $attributes = $request->validate([
            'installer_id' => [
                'required',
                'integer',
                Rule::exists('installers', 'id')->where('status', InstallerStatus::Active->value),
            ],
            'scheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $installer = Installer::query()->findOrFail($attributes['installer_id']);
        $wasAssigned = $installationQuestionnaire->isAssigned();

        $job = $this->assignments->assign(
            $installationQuestionnaire,
            $installer,
            $request->user(),
            filled($attributes['scheduled_at'] ?? null)
                ? Carbon::parse($attributes['scheduled_at'])
                : null,
            $attributes['notes'] ?? null,
        );

        $status = $wasAssigned
            ? "Installer updated to {$installer->name}."
            : "{$installer->name} assigned. A scheduled job was added to their installation history.";

        if ($this->assignments->sendOfferEmail($job)) {
            $status .= " An assignment email was sent to {$installer->email}.";
        } elseif (! filled($installer->email)) {
            $status .= ' No assignment email was sent because this installer has no email address.';
        } else {
            $status .= ' The assignment was saved, but the installer email could not be sent.';
        }

        return redirect()
            ->route('admin.installation-questionnaires.show', $installationQuestionnaire)
            ->with('status', $status);
    }

    public function unassignInstaller(
        InstallationQuestionnaire $installationQuestionnaire,
    ): RedirectResponse {
        $installerName = $installationQuestionnaire->installer?->name ?? 'the installer';

        $this->assignments->unassign($installationQuestionnaire);

        return redirect()
            ->route('admin.installation-questionnaires.show', $installationQuestionnaire)
            ->with('status', "{$installerName} was unassigned. Any open scheduled job was cancelled.");
    }

    public function photo(
        InstallationQuestionnaire $installationQuestionnaire,
        int $photo,
    ): BinaryFileResponse {
        $photos = $installationQuestionnaire->sinkPhotoItems();
        abort_unless(isset($photos[$photo]), 404);

        $path = $photos[$photo]['path'];
        abort_unless(Storage::disk('public')->exists($path), 404);

        $fileName = $photos[$photo]['original_name'] ?: basename($path);

        return response()->file(Storage::disk('public')->path($path), [
            'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    /**
     * @return Collection<int, Installer>
     */
    private function assignableInstallers(?InstallationQuestionnaire $questionnaire = null): Collection
    {
        $installers = Installer::query()
            ->where('status', InstallerStatus::Active)
            ->orderBy('name')
            ->get();

        if (! $questionnaire) {
            return $installers;
        }

        return $installers
            ->sortBy(function (Installer $installer) use ($questionnaire) {
                $nearby = UsStates::matches($installer->state, $questionnaire->state) ? 0 : 1;

                return sprintf('%d-%s', $nearby, strtolower($installer->name));
            })
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    private function consultants(?InstallationQuestionnaire $questionnaire = null): Collection
    {
        $consultants = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', [
                'consultant',
                'admin',
                'super-admin',
                'team-admin',
                'manager',
                'member',
            ]))
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($questionnaire?->seller && $consultants->doesntContain(fn (User $user) => $user->id === $questionnaire->seller_id)) {
            $consultants = $consultants
                ->push($questionnaire->seller)
                ->sortBy('name')
                ->values();
        }

        return $consultants;
    }

    /**
     * @return array<string, mixed>
     */
    private function validationRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'street_address' => ['required', 'string', 'max:255'],
            'street_address_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:40'],
            'country' => ['required', 'string', 'max:120'],
            'property_type' => [
                'required',
                'string',
                Rule::in([
                    'Single Family Home',
                    'Condo',
                    'Townhouse',
                    'Apartment',
                ]),
            ],
            'existing_equipment' => ['nullable', 'array'],
            'existing_equipment.*' => [
                'string',
                Rule::in([
                    'Under the sink Reverse Osmosis/Water Filter',
                    'Counter Alkaline Water Machine/Water Purifier',
                    'Water Softener',
                ]),
            ],
            'ownership' => ['nullable', 'string', Rule::in(['own', 'rent'])],
            'water_source' => [
                'required',
                'string',
                Rule::in([
                    'Municipal (connected to the city)',
                    'Well',
                    'Rainwater',
                    'None',
                    'Other',
                ]),
            ],
            'water_source_other' => ['nullable', 'string', 'max:255'],
            'special_requirements' => ['nullable', 'string', 'max:5000'],
            'additional_notes' => ['nullable', 'string', 'max:5000'],
            'seller_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ];
    }
}
