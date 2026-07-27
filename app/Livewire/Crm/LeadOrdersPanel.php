<?php

namespace App\Livewire\Crm;

use App\Models\Crm\Delivery;
use App\Models\Crm\Installation;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\Order;
use App\Services\Crm\DeliveryService;
use App\Services\Crm\InstallationService;
use App\Services\Crm\OrderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class LeadOrdersPanel extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public Lead|Prospect|Customer|Recruit $lead;

    public ?int $activeOrderId = null;

    public string $payment_amount = '';

    public string $payment_method = '';

    public string $payment_reference = '';

    public string $delivery_scheduled_at = '';

    public string $delivery_address = '';

    public string $delivery_contact_name = '';

    public string $delivery_contact_phone = '';

    public string $installation_scheduled_at = '';

    /** @var array<string, bool> */
    public array $deliveryChecklist = [];

    /** @var array<string, bool> */
    public array $installationChecklist = [];

    /** @var list<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $deliveryPhotos = [];

    /** @var list<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $installationPhotos = [];

    public ?int $completingDeliveryId = null;

    public ?int $completingInstallationId = null;

    public function mount(Lead|Prospect|Customer|Recruit $lead): void
    {
        $this->authorize('view', $lead);
        $this->lead = $lead;
        $this->delivery_scheduled_at = now()->addDay()->format('Y-m-d\TH:i');
        $this->installation_scheduled_at = now()->addDays(2)->format('Y-m-d\TH:i');
        $this->delivery_address = (string) $lead->address;
        $this->delivery_contact_name = $lead->fullName();
        $this->delivery_contact_phone = (string) ($lead->phone ?? '');
    }

    public function submitOrder(int $orderId, OrderService $orders): void
    {
        $this->authorize('update', $this->lead);

        $order = $this->findOrder($orderId);
        $orders->submit($order, auth()->user());
        $this->lead->refresh();
    }

    public function recordPayment(int $orderId, OrderService $orders): void
    {
        $this->authorize('update', $this->lead);

        $order = $this->findOrder($orderId);

        $validated = $this->validate([
            'payment_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $order = $orders->recordPayment($order, $validated, auth()->user());

        $this->reset(['payment_amount', 'payment_method', 'payment_reference']);

        $contact = $order->contact;

        if ($contact) {
            $this->lead = $contact;
        } else {
            $this->lead->refresh();
        }
    }

    public function scheduleDelivery(int $orderId, DeliveryService $deliveries): void
    {
        $this->authorize('update', $this->lead);

        $order = $this->findOrder($orderId);

        $validated = $this->validate([
            'delivery_scheduled_at' => ['required', 'date'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'delivery_contact_name' => ['nullable', 'string', 'max:255'],
            'delivery_contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $deliveries->schedule($order, [
            'scheduled_at' => $validated['delivery_scheduled_at'],
            'address' => $validated['delivery_address'],
            'contact_name' => $validated['delivery_contact_name'],
            'contact_phone' => $validated['delivery_contact_phone'],
        ], auth()->user());

        $this->lead->refresh();
    }

    public function startCompleteDelivery(int $deliveryId): void
    {
        $this->authorize('update', $this->lead);

        $delivery = Delivery::query()
            ->whereContact($this->lead)
            ->findOrFail($deliveryId);

        $this->completingDeliveryId = $delivery->id;
        $this->deliveryChecklist = $delivery->checklist ?? $this->defaultDeliveryChecklist();
        $this->deliveryPhotos = [];
    }

    public function completeDelivery(DeliveryService $deliveries): void
    {
        $this->authorize('update', $this->lead);

        $delivery = Delivery::query()
            ->whereContact($this->lead)
            ->findOrFail($this->completingDeliveryId);

        $this->validate([
            'deliveryPhotos.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $photoPaths = $this->storePhotos($delivery->photo_paths ?? [], $this->deliveryPhotos, 'crm/deliveries');

        $deliveries->complete($delivery, [
            'checklist' => $this->deliveryChecklist,
            'photo_paths' => $photoPaths,
        ], auth()->user());

        $this->completingDeliveryId = null;
        $this->deliveryPhotos = [];
        $this->lead->refresh();
    }

    public function scheduleInstallation(int $orderId, InstallationService $installations): void
    {
        $this->authorize('update', $this->lead);

        $order = $this->findOrder($orderId);

        $validated = $this->validate([
            'installation_scheduled_at' => ['required', 'date'],
        ]);

        $delivery = $order->latestDelivery;

        $installations->schedule($order, [
            'scheduled_at' => $validated['installation_scheduled_at'],
        ], auth()->user(), $delivery);

        $this->lead->refresh();
    }

    public function startCompleteInstallation(int $installationId): void
    {
        $this->authorize('update', $this->lead);

        $installation = Installation::query()
            ->whereContact($this->lead)
            ->findOrFail($installationId);

        $this->completingInstallationId = $installation->id;
        $this->installationChecklist = $installation->checklist ?? $this->defaultInstallationChecklist();
        $this->installationPhotos = [];
    }

    public function completeInstallation(InstallationService $installations): void
    {
        $this->authorize('update', $this->lead);

        $installation = Installation::query()
            ->whereContact($this->lead)
            ->findOrFail($this->completingInstallationId);

        $this->validate([
            'installationPhotos.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $photoPaths = $this->storePhotos($installation->photo_paths ?? [], $this->installationPhotos, 'crm/installations');

        $installations->complete($installation, [
            'checklist' => $this->installationChecklist,
            'photo_paths' => $photoPaths,
        ], auth()->user());

        $this->completingInstallationId = null;
        $this->installationPhotos = [];
        $this->lead->refresh();
    }

    public function setActiveOrder(int $orderId): void
    {
        $this->activeOrderId = $this->activeOrderId === $orderId ? null : $orderId;
        $order = $this->findOrder($orderId);
        $this->payment_amount = (string) max(0, (float) $order->total - (float) $order->amount_paid);
    }

    /**
     * @param  list<mixed>  $existing
     * @param  list<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile>  $uploads
     * @return list<string>
     */
    private function storePhotos(array $existing, array $uploads, string $directory): array
    {
        $paths = $existing;

        foreach ($uploads as $photo) {
            if ($photo) {
                $paths[] = $photo->store($directory, 'public');
            }
        }

        return array_values(array_filter($paths));
    }

  /**
     * @return array<string, bool>
     */
    private function defaultDeliveryChecklist(): array
    {
        return collect(config('crm.delivery_checklist', []))
            ->mapWithKeys(fn (string $label, string $slug) => [$slug => false])
            ->all();
    }

    /**
     * @return array<string, bool>
     */
    private function defaultInstallationChecklist(): array
    {
        return collect(config('crm.installation_checklist', []))
            ->mapWithKeys(fn (string $label, string $slug) => [$slug => false])
            ->all();
    }

    private function findOrder(int $orderId): Order
    {
        return Order::query()
            ->whereContact($this->lead)
            ->with(['items', 'deliveries', 'installations'])
            ->findOrFail($orderId);
    }

    public function render()
    {
        return view('livewire.crm.lead-orders-panel', [
            'orders' => $this->lead->orders()
                ->with(['items', 'author', 'quotation', 'deliveries', 'installations'])
                ->limit(10)
                ->get(),
        ]);
    }
}
