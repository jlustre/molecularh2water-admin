<?php

namespace App\Livewire\Crm;

use App\Enums\Crm\DeliveryStatus;
use App\Enums\Crm\InstallationStatus;
use App\Enums\Crm\OrderStatus;
use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\Delivery;
use App\Models\Crm\Installation;
use App\Models\Crm\Order;
use App\Models\User;
use App\Services\Crm\DeliveryService;
use App\Services\Crm\InstallationService;
use App\Support\Crm\CrmRoutes;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class FulfillmentManager extends Component
{
    use UsesCrmLayout;
    use WithFileUploads;
    use WithPagination;

    public string $activeTab = 'deliveries';

    public string $search = '';

    public string $statusFilter = '';

    public string $assigneeFilter = '';

    public string $datePreset = 'month_to_date';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 20;

    public bool $canManage = false;

    public bool $showScheduleDeliveryModal = false;

    public bool $showEditDeliveryModal = false;

    public bool $showCompleteDeliveryModal = false;

    public bool $showScheduleInstallationModal = false;

    public bool $showEditInstallationModal = false;

    public bool $showCompleteInstallationModal = false;

    public ?int $selectedOrderId = null;

    public ?int $selectedDeliveryId = null;

    public ?int $selectedInstallationId = null;

    public string $scheduled_at = '';

    public string $address = '';

    public string $contact_name = '';

    public string $contact_phone = '';

    public ?int $assignee_id = null;

    public string $notes = '';

    /** @var array<string, bool> */
    public array $checklist = [];

    /** @var list<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $photos = [];

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasPermission('fulfillment.view') || $user?->hasPermission('fulfillment.manage'),
            403,
        );

        $this->canManage = (bool) $user?->hasPermission('fulfillment.manage');
        $this->syncCustomDatesFromPreset();
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['deliveries', 'installations', 'ready'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function updatedDatePreset(): void
    {
        $this->syncCustomDatesFromPreset();
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->datePreset = 'custom';
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->datePreset = 'custom';
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAssigneeFilter(): void
    {
        $this->resetPage();
    }

    public function openScheduleDelivery(int $orderId): void
    {
        abort_unless($this->canManage, 403);

        $order = $this->findAccessibleOrder($orderId);
        $contact = $order->contact;

        $this->resetForm();
        $this->selectedOrderId = $order->id;
        $this->scheduled_at = now()->addDay()->format('Y-m-d\TH:i');
        $this->address = (string) ($contact?->address ?? '');
        $this->contact_name = (string) ($contact?->fullName() ?? '');
        $this->contact_phone = (string) ($contact?->phone ?? '');
        $this->assignee_id = auth()->id();
        $this->showScheduleDeliveryModal = true;
    }

    public function openEditDelivery(int $deliveryId): void
    {
        abort_unless($this->canManage, 403);

        $delivery = $this->findAccessibleDelivery($deliveryId);

        $this->resetForm();
        $this->selectedDeliveryId = $delivery->id;
        $this->scheduled_at = $delivery->scheduled_at?->format('Y-m-d\TH:i') ?? '';
        $this->address = (string) ($delivery->address ?? '');
        $this->contact_name = (string) ($delivery->contact_name ?? '');
        $this->contact_phone = (string) ($delivery->contact_phone ?? '');
        $this->assignee_id = $delivery->user_id;
        $this->notes = (string) ($delivery->notes ?? '');
        $this->checklist = $delivery->checklist ?? app(DeliveryService::class)->defaultChecklist();
        $this->showEditDeliveryModal = true;
    }

    public function openCompleteDelivery(int $deliveryId): void
    {
        abort_unless($this->canManage, 403);

        $delivery = $this->findAccessibleDelivery($deliveryId);

        $this->resetForm();
        $this->selectedDeliveryId = $delivery->id;
        $this->notes = (string) ($delivery->notes ?? '');
        $this->checklist = $delivery->checklist ?? app(DeliveryService::class)->defaultChecklist();
        $this->showCompleteDeliveryModal = true;
    }

    public function openScheduleInstallation(?int $orderId = null, ?int $deliveryId = null): void
    {
        abort_unless($this->canManage, 403);

        $order = null;
        $delivery = null;

        if ($deliveryId) {
            $delivery = $this->findAccessibleDelivery($deliveryId);
            $order = $delivery->order;
        } elseif ($orderId) {
            $order = $this->findAccessibleOrder($orderId);
            $delivery = $order->latestDelivery;
        }

        abort_unless($order, 404);

        $this->resetForm();
        $this->selectedOrderId = $order->id;
        $this->selectedDeliveryId = $delivery?->id;
        $this->scheduled_at = now()->addDays(2)->format('Y-m-d\TH:i');
        $this->assignee_id = auth()->id();
        $this->showScheduleInstallationModal = true;
    }

    public function openEditInstallation(int $installationId): void
    {
        abort_unless($this->canManage, 403);

        $installation = $this->findAccessibleInstallation($installationId);

        $this->resetForm();
        $this->selectedInstallationId = $installation->id;
        $this->scheduled_at = $installation->scheduled_at?->format('Y-m-d\TH:i') ?? '';
        $this->assignee_id = $installation->user_id;
        $this->notes = (string) ($installation->notes ?? '');
        $this->checklist = $installation->checklist ?? app(InstallationService::class)->defaultChecklist();
        $this->showEditInstallationModal = true;
    }

    public function openCompleteInstallation(int $installationId): void
    {
        abort_unless($this->canManage, 403);

        $installation = $this->findAccessibleInstallation($installationId);

        $this->resetForm();
        $this->selectedInstallationId = $installation->id;
        $this->notes = (string) ($installation->notes ?? '');
        $this->checklist = $installation->checklist ?? app(InstallationService::class)->defaultChecklist();
        $this->showCompleteInstallationModal = true;
    }

    public function closeModals(): void
    {
        $this->showScheduleDeliveryModal = false;
        $this->showEditDeliveryModal = false;
        $this->showCompleteDeliveryModal = false;
        $this->showScheduleInstallationModal = false;
        $this->showEditInstallationModal = false;
        $this->showCompleteInstallationModal = false;
        $this->resetForm();
    }

    public function contactUrl(?Model $contact): ?string
    {
        if (! $contact) {
            return null;
        }

        return match ($contact->getMorphClass()) {
            'lead' => CrmRoutes::url('leads.show', ['lead' => $contact]),
            'prospect' => CrmRoutes::url('prospects.show', ['lead' => $contact]),
            'customer' => CrmRoutes::url('customers.show', ['lead' => $contact]),
            'recruit' => CrmRoutes::url('recruits.show', ['lead' => $contact]),
            default => null,
        };
    }

    public function contactLabel(?Model $contact, ?string $fallback = null): string
    {
        if ($contact && method_exists($contact, 'fullName')) {
            return $contact->fullName() ?: ($fallback ?: 'Unnamed contact');
        }

        return $fallback ?: 'Unknown contact';
    }

    public function scheduleDelivery(DeliveryService $deliveries): void
    {
        abort_unless($this->canManage, 403);

        $data = $this->validate([
            'selectedOrderId' => ['required', 'integer'],
            'scheduled_at' => ['required', 'date'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = $this->findAccessibleOrder((int) $data['selectedOrderId']);

        $deliveries->schedule($order, [
            'scheduled_at' => $data['scheduled_at'],
            'address' => $data['address'],
            'contact_name' => $data['contact_name'],
            'contact_phone' => $data['contact_phone'],
            'user_id' => $data['assignee_id'] ?? auth()->id(),
            'notes' => $data['notes'],
        ], auth()->user());

        session()->flash('status', 'Delivery scheduled.');
        $this->closeModals();
        $this->activeTab = 'deliveries';
    }

    public function saveDelivery(DeliveryService $deliveries): void
    {
        abort_unless($this->canManage, 403);

        $data = $this->validate([
            'selectedDeliveryId' => ['required', 'integer'],
            'scheduled_at' => ['required', 'date'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'checklist' => ['array'],
        ]);

        $delivery = $this->findAccessibleDelivery((int) $data['selectedDeliveryId']);

        $deliveries->update($delivery, [
            'scheduled_at' => $data['scheduled_at'],
            'address' => $data['address'],
            'contact_name' => $data['contact_name'],
            'contact_phone' => $data['contact_phone'],
            'user_id' => $data['assignee_id'] ?? $delivery->user_id,
            'notes' => $data['notes'],
            'checklist' => $this->checklist,
        ], auth()->user());

        session()->flash('status', 'Delivery updated.');
        $this->closeModals();
    }

    public function completeDelivery(DeliveryService $deliveries): void
    {
        abort_unless($this->canManage, 403);

        $this->validate([
            'selectedDeliveryId' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'checklist' => ['array'],
            'photos.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $delivery = $this->findAccessibleDelivery((int) $this->selectedDeliveryId);
        $photoPaths = $this->storePhotos($delivery->photo_paths ?? [], $this->photos, 'crm/deliveries');

        $deliveries->complete($delivery, [
            'checklist' => $this->checklist,
            'photo_paths' => $photoPaths,
            'notes' => $this->notes,
        ], auth()->user());

        session()->flash('status', 'Delivery marked complete.');
        $this->closeModals();
    }

    public function markDeliveryInTransit(int $deliveryId, DeliveryService $deliveries): void
    {
        abort_unless($this->canManage, 403);
        $deliveries->markInTransit($this->findAccessibleDelivery($deliveryId), auth()->user());
        session()->flash('status', 'Delivery marked in transit.');
    }

    public function failDelivery(int $deliveryId, DeliveryService $deliveries): void
    {
        abort_unless($this->canManage, 403);
        $deliveries->fail($this->findAccessibleDelivery($deliveryId), auth()->user());
        session()->flash('status', 'Delivery marked failed.');
    }

    public function cancelDelivery(int $deliveryId, DeliveryService $deliveries): void
    {
        abort_unless($this->canManage, 403);
        $deliveries->cancel($this->findAccessibleDelivery($deliveryId), auth()->user());
        session()->flash('status', 'Delivery cancelled.');
    }

    public function scheduleInstallation(InstallationService $installations): void
    {
        abort_unless($this->canManage, 403);

        $data = $this->validate([
            'selectedOrderId' => ['required', 'integer'],
            'selectedDeliveryId' => ['nullable', 'integer'],
            'scheduled_at' => ['required', 'date'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = $this->findAccessibleOrder((int) $data['selectedOrderId']);
        $delivery = $data['selectedDeliveryId']
            ? $this->findAccessibleDelivery((int) $data['selectedDeliveryId'])
            : $order->latestDelivery;

        $installations->schedule($order, [
            'scheduled_at' => $data['scheduled_at'],
            'user_id' => $data['assignee_id'] ?? auth()->id(),
            'notes' => $data['notes'],
        ], auth()->user(), $delivery);

        session()->flash('status', 'Installation scheduled.');
        $this->closeModals();
        $this->activeTab = 'installations';
    }

    public function saveInstallation(InstallationService $installations): void
    {
        abort_unless($this->canManage, 403);

        $data = $this->validate([
            'selectedInstallationId' => ['required', 'integer'],
            'scheduled_at' => ['required', 'date'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'checklist' => ['array'],
        ]);

        $installation = $this->findAccessibleInstallation((int) $data['selectedInstallationId']);

        $installations->update($installation, [
            'scheduled_at' => $data['scheduled_at'],
            'user_id' => $data['assignee_id'] ?? $installation->user_id,
            'notes' => $data['notes'],
            'checklist' => $this->checklist,
        ], auth()->user());

        session()->flash('status', 'Installation updated.');
        $this->closeModals();
    }

    public function completeInstallation(InstallationService $installations): void
    {
        abort_unless($this->canManage, 403);

        $this->validate([
            'selectedInstallationId' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'checklist' => ['array'],
            'photos.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $installation = $this->findAccessibleInstallation((int) $this->selectedInstallationId);
        $photoPaths = $this->storePhotos($installation->photo_paths ?? [], $this->photos, 'crm/installations');

        $installations->complete($installation, [
            'checklist' => $this->checklist,
            'photo_paths' => $photoPaths,
            'notes' => $this->notes,
        ], auth()->user());

        session()->flash('status', 'Installation completed. Order marked fulfilled.');
        $this->closeModals();
    }

    public function markInstallationInProgress(int $installationId, InstallationService $installations): void
    {
        abort_unless($this->canManage, 403);
        $installations->markInProgress($this->findAccessibleInstallation($installationId), auth()->user());
        session()->flash('status', 'Installation marked in progress.');
    }

    public function failInstallation(int $installationId, InstallationService $installations): void
    {
        abort_unless($this->canManage, 403);
        $installations->fail($this->findAccessibleInstallation($installationId), auth()->user());
        session()->flash('status', 'Installation marked failed.');
    }

    public function cancelInstallation(int $installationId, InstallationService $installations): void
    {
        abort_unless($this->canManage, 403);
        $installations->cancel($this->findAccessibleInstallation($installationId), auth()->user());
        session()->flash('status', 'Installation cancelled.');
    }

    public function render(): View
    {
        $user = auth()->user();
        [$rangeStart, $rangeEnd] = $this->dateRange();
        $summary = $this->buildSummary($user);

        $deliveries = null;
        $installations = null;
        $readyOrders = null;

        if ($this->activeTab === 'deliveries') {
            $deliveries = $this->deliveriesQuery($user, $rangeStart, $rangeEnd)
                ->paginate(max(5, min(100, $this->perPage)));
        } elseif ($this->activeTab === 'installations') {
            $installations = $this->installationsQuery($user, $rangeStart, $rangeEnd)
                ->paginate(max(5, min(100, $this->perPage)));
        } else {
            $readyOrders = $this->readyOrdersQuery($user)
                ->paginate(max(5, min(100, $this->perPage)));
        }

        return view('livewire.crm.fulfillment-manager', [
            'summary' => $summary,
            'deliveries' => $deliveries,
            'installations' => $installations,
            'readyOrders' => $readyOrders,
            'deliveryStatuses' => DeliveryStatus::cases(),
            'installationStatuses' => InstallationStatus::cases(),
            'assignees' => User::query()->orderBy('name')->get(['id', 'name']),
            'datePresets' => $this->datePresetOptions(),
            'rangeLabel' => $this->rangeLabel($rangeStart, $rangeEnd),
            'deliveryChecklistLabels' => config('crm.delivery_checklist', []),
            'installationChecklistLabels' => config('crm.installation_checklist', []),
        ])->layout($this->crmLayout());
    }

    private function deliveriesQuery(?User $user, ?Carbon $start, ?Carbon $end)
    {
        $term = '%'.trim($this->search).'%';

        return Delivery::query()
            ->with(['order', 'contact', 'assignee', 'installation'])
            ->forAccessibleContacts($user)
            ->when($start, fn ($q) => $q->where('scheduled_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('scheduled_at', '<=', $end))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->assigneeFilter !== '', fn ($q) => $q->where('user_id', $this->assigneeFilter))
            ->when(trim($this->search) !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('address', 'like', $term)
                        ->orWhere('contact_name', 'like', $term)
                        ->orWhere('contact_phone', 'like', $term)
                        ->orWhere('notes', 'like', $term)
                        ->orWhereHas('order', fn ($order) => $order->where('order_number', 'like', $term))
                        ->orWhereHas('assignee', fn ($user) => $user->where('name', 'like', $term))
                        ->orWhereHasMorph('contact', ['lead', 'prospect', 'customer', 'recruit'], function ($contact) use ($term) {
                            $contact->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        });
                });
            })
            ->orderByRaw("CASE status
                WHEN 'in_transit' THEN 0
                WHEN 'scheduled' THEN 1
                WHEN 'failed' THEN 2
                WHEN 'delivered' THEN 3
                ELSE 4 END")
            ->orderBy('scheduled_at');
    }

    private function installationsQuery(?User $user, ?Carbon $start, ?Carbon $end)
    {
        $term = '%'.trim($this->search).'%';

        return Installation::query()
            ->with(['order', 'contact', 'technician', 'delivery'])
            ->forAccessibleContacts($user)
            ->when($start, fn ($q) => $q->where('scheduled_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('scheduled_at', '<=', $end))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->assigneeFilter !== '', fn ($q) => $q->where('user_id', $this->assigneeFilter))
            ->when(trim($this->search) !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('notes', 'like', $term)
                        ->orWhereHas('order', fn ($order) => $order->where('order_number', 'like', $term))
                        ->orWhereHas('technician', fn ($user) => $user->where('name', 'like', $term))
                        ->orWhereHas('delivery', fn ($delivery) => $delivery->where('address', 'like', $term)
                            ->orWhere('contact_name', 'like', $term))
                        ->orWhereHasMorph('contact', ['lead', 'prospect', 'customer', 'recruit'], function ($contact) use ($term) {
                            $contact->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        });
                });
            })
            ->orderByRaw("CASE status
                WHEN 'in_progress' THEN 0
                WHEN 'scheduled' THEN 1
                WHEN 'failed' THEN 2
                WHEN 'completed' THEN 3
                ELSE 4 END")
            ->orderBy('scheduled_at');
    }

    private function readyOrdersQuery(?User $user)
    {
        $term = '%'.trim($this->search).'%';

        return Order::query()
            ->with(['contact', 'consultant', 'latestDelivery', 'latestInstallation'])
            ->forAccessibleContacts($user)
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Draft->value])
            ->where(function ($q) {
                $q->where(function ($needsDelivery) {
                    $needsDelivery
                        ->whereIn('status', [OrderStatus::Submitted->value, OrderStatus::Confirmed->value])
                        ->whereDoesntHave('deliveries', fn ($d) => $d->whereIn('status', [
                            DeliveryStatus::Scheduled->value,
                            DeliveryStatus::InTransit->value,
                            DeliveryStatus::Delivered->value,
                        ]));
                })->orWhere(function ($needsInstall) {
                    $needsInstall
                        ->whereHas('deliveries', fn ($d) => $d->where('status', DeliveryStatus::Delivered->value))
                        ->whereDoesntHave('installations', fn ($i) => $i->whereIn('status', [
                            InstallationStatus::Scheduled->value,
                            InstallationStatus::InProgress->value,
                            InstallationStatus::Completed->value,
                        ]));
                });
            })
            ->when(trim($this->search) !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('order_number', 'like', $term)
                        ->orWhereHas('consultant', fn ($user) => $user->where('name', 'like', $term))
                        ->orWhereHasMorph('contact', ['lead', 'prospect', 'customer', 'recruit'], function ($contact) use ($term) {
                            $contact->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        });
                });
            })
            ->orderByDesc('updated_at');
    }

    /**
     * @return array<string, int>
     */
    private function buildSummary(?User $user): array
    {
        $deliveries = Delivery::query()->forAccessibleContacts($user);
        $installations = Installation::query()->forAccessibleContacts($user);

        return [
            'deliveries_today' => (clone $deliveries)
                ->whereDate('scheduled_at', today())
                ->whereIn('status', [DeliveryStatus::Scheduled->value, DeliveryStatus::InTransit->value])
                ->count(),
            'deliveries_overdue' => (clone $deliveries)
                ->where('scheduled_at', '<', now())
                ->whereIn('status', [DeliveryStatus::Scheduled->value, DeliveryStatus::InTransit->value])
                ->count(),
            'in_transit' => (clone $deliveries)->where('status', DeliveryStatus::InTransit->value)->count(),
            'installs_today' => (clone $installations)
                ->whereDate('scheduled_at', today())
                ->whereIn('status', [InstallationStatus::Scheduled->value, InstallationStatus::InProgress->value])
                ->count(),
            'installs_overdue' => (clone $installations)
                ->where('scheduled_at', '<', now())
                ->whereIn('status', [InstallationStatus::Scheduled->value, InstallationStatus::InProgress->value])
                ->count(),
            'ready_count' => $this->readyOrdersQuery($user)->count(),
            'completed_week' => (clone $deliveries)
                ->where('status', DeliveryStatus::Delivered->value)
                ->where('delivered_at', '>=', now()->startOfWeek())
                ->count()
                + (clone $installations)
                    ->where('status', InstallationStatus::Completed->value)
                    ->where('completed_at', '>=', now()->startOfWeek())
                    ->count(),
        ];
    }

    private function findAccessibleOrder(int $id): Order
    {
        return Order::query()
            ->forAccessibleContacts(auth()->user())
            ->findOrFail($id);
    }

    private function findAccessibleDelivery(int $id): Delivery
    {
        return Delivery::query()
            ->with(['order', 'contact'])
            ->forAccessibleContacts(auth()->user())
            ->findOrFail($id);
    }

    private function findAccessibleInstallation(int $id): Installation
    {
        return Installation::query()
            ->with(['order', 'contact', 'delivery'])
            ->forAccessibleContacts(auth()->user())
            ->findOrFail($id);
    }

    /**
     * @param  list<string>  $existing
     * @param  list<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile>  $uploads
     * @return list<string>
     */
    private function storePhotos(array $existing, array $uploads, string $directory): array
    {
        $paths = $existing;

        foreach ($uploads as $photo) {
            $paths[] = $photo->store($directory, 'public');
        }

        return array_values(array_filter($paths));
    }

    private function resetForm(): void
    {
        $this->selectedOrderId = null;
        $this->selectedDeliveryId = null;
        $this->selectedInstallationId = null;
        $this->scheduled_at = '';
        $this->address = '';
        $this->contact_name = '';
        $this->contact_phone = '';
        $this->assignee_id = null;
        $this->notes = '';
        $this->checklist = [];
        $this->photos = [];
        $this->resetValidation();
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function dateRange(): array
    {
        return match ($this->datePreset) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'month_to_date' => [now()->startOfMonth(), now()->endOfDay()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'last_7_days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'last_30_days' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'last_90_days' => [now()->subDays(89)->startOfDay(), now()->endOfDay()],
            'year_to_date' => [now()->startOfYear(), now()->endOfDay()],
            'custom' => [
                filled($this->dateFrom) ? Carbon::parse($this->dateFrom)->startOfDay() : null,
                filled($this->dateTo) ? Carbon::parse($this->dateTo)->endOfDay() : null,
            ],
            default => [null, null],
        };
    }

    private function syncCustomDatesFromPreset(): void
    {
        if ($this->datePreset === 'custom') {
            return;
        }

        [$start, $end] = $this->dateRange();
        $this->dateFrom = $start?->toDateString() ?? '';
        $this->dateTo = $end?->toDateString() ?? '';
    }

    /**
     * @return array<string, string>
     */
    private function datePresetOptions(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This week',
            'last_week' => 'Last week',
            'month_to_date' => 'Month to date',
            'last_month' => 'Last month',
            'last_7_days' => 'Last 7 days',
            'last_30_days' => 'Last 30 days',
            'last_90_days' => 'Last 90 days',
            'year_to_date' => 'Year to date',
            'all' => 'All time',
            'custom' => 'Custom range',
        ];
    }

    private function rangeLabel(?Carbon $start, ?Carbon $end): string
    {
        if (! $start && ! $end) {
            return 'All time';
        }

        if ($start && $end) {
            return $start->format('M j, Y').' – '.$end->format('M j, Y');
        }

        if ($start) {
            return 'From '.$start->format('M j, Y');
        }

        return 'Through '.$end->format('M j, Y');
    }
}
