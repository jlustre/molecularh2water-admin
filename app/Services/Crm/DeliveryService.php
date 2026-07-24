<?php

namespace App\Services\Crm;

use App\Enums\Crm\DeliveryStatus;
use App\Models\Crm\Delivery;
use App\Models\Crm\Order;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class DeliveryService
{
    public function __construct(
        private readonly TimelineService $timeline,
        private readonly FunnelService $funnels,
        private readonly DashboardStatsService $dashboardStats,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function schedule(Order $order, array $data, User $user): Delivery
    {
        $contact = $order->contact;

        $delivery = Delivery::query()->create([
            'order_id' => $order->id,
            'contact_type' => $order->contact_type,
            'contact_id' => $order->contact_id,
            'user_id' => Arr::get($data, 'user_id', $user->id),
            'status' => DeliveryStatus::Scheduled,
            'scheduled_at' => Arr::get($data, 'scheduled_at', now()->addDay()),
            'address' => Arr::get($data, 'address', $contact?->address),
            'contact_name' => Arr::get($data, 'contact_name', $contact?->fullName()),
            'contact_phone' => Arr::get($data, 'contact_phone', $contact?->phone),
            'checklist' => $this->defaultChecklist(),
            'notes' => Arr::get($data, 'notes'),
        ]);

        if ($contact) {
            $this->timeline->log(
                $contact,
                'delivery_scheduled',
                'Delivery scheduled for '.$order->order_number,
                $delivery->scheduled_at?->format('M j, Y g:i A'),
                ['delivery_id' => $delivery->id, 'order_id' => $order->id],
                $user,
            );

            $this->funnels->moveLeadToStageSlug($contact, 'delivery-scheduled', $user);
        }

        $this->dashboardStats->forget($user);

        return $delivery->fresh(['assignee', 'order', 'contact']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Delivery $delivery, array $data, User $user): Delivery
    {
        $this->assertMutable($delivery);

        $delivery->update([
            'user_id' => Arr::get($data, 'user_id', $delivery->user_id),
            'scheduled_at' => Arr::get($data, 'scheduled_at', $delivery->scheduled_at),
            'address' => Arr::get($data, 'address', $delivery->address),
            'contact_name' => Arr::get($data, 'contact_name', $delivery->contact_name),
            'contact_phone' => Arr::get($data, 'contact_phone', $delivery->contact_phone),
            'notes' => Arr::get($data, 'notes', $delivery->notes),
            'checklist' => Arr::get($data, 'checklist', $delivery->checklist),
        ]);

        if ($delivery->contact) {
            $this->timeline->log(
                $delivery->contact,
                'delivery_updated',
                'Delivery updated for '.$delivery->order?->order_number,
                $delivery->scheduled_at?->format('M j, Y g:i A'),
                ['delivery_id' => $delivery->id, 'order_id' => $delivery->order_id],
                $user,
            );
        }

        $this->dashboardStats->forget($user);

        return $delivery->fresh(['assignee', 'order', 'contact']);
    }

    public function markInTransit(Delivery $delivery, User $user): Delivery
    {
        $this->assertStatus($delivery, [DeliveryStatus::Scheduled, DeliveryStatus::Failed]);

        $delivery->update(['status' => DeliveryStatus::InTransit]);

        if ($delivery->contact) {
            $this->timeline->log(
                $delivery->contact,
                'delivery_in_transit',
                'Delivery in transit for '.$delivery->order?->order_number,
                null,
                ['delivery_id' => $delivery->id, 'order_id' => $delivery->order_id],
                $user,
            );
        }

        $this->dashboardStats->forget($user);

        return $delivery->fresh(['assignee', 'order', 'contact']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function complete(Delivery $delivery, array $data, User $user): Delivery
    {
        $this->assertStatus($delivery, [
            DeliveryStatus::Scheduled,
            DeliveryStatus::InTransit,
            DeliveryStatus::Failed,
        ]);

        $checklist = Arr::get($data, 'checklist', $delivery->checklist ?? []);
        $photoPaths = array_values(array_filter(array_merge(
            $delivery->photo_paths ?? [],
            Arr::get($data, 'photo_paths', []),
        )));

        $delivery->update([
            'status' => DeliveryStatus::Delivered,
            'delivered_at' => now(),
            'checklist' => $checklist,
            'photo_paths' => $photoPaths ?: null,
            'notes' => Arr::get($data, 'notes', $delivery->notes),
        ]);

        if ($delivery->contact) {
            $this->timeline->log(
                $delivery->contact,
                'delivery_completed',
                'Delivery completed for '.$delivery->order?->order_number,
                null,
                ['delivery_id' => $delivery->id, 'order_id' => $delivery->order_id],
                $user,
            );
        }

        app(CrmAutomationService::class)->dispatch('delivery.completed', [
            'lead_id' => $delivery->contact_id,
            'delivery_id' => $delivery->id,
            'order_id' => $delivery->order_id,
        ], $user);

        $this->dashboardStats->forget($user);

        return $delivery->fresh(['assignee', 'order', 'contact']);
    }

    public function fail(Delivery $delivery, User $user, ?string $notes = null): Delivery
    {
        $this->assertStatus($delivery, [DeliveryStatus::Scheduled, DeliveryStatus::InTransit]);

        $delivery->update([
            'status' => DeliveryStatus::Failed,
            'notes' => $notes ?: $delivery->notes,
        ]);

        if ($delivery->contact) {
            $this->timeline->log(
                $delivery->contact,
                'delivery_failed',
                'Delivery failed for '.$delivery->order?->order_number,
                $notes,
                ['delivery_id' => $delivery->id, 'order_id' => $delivery->order_id],
                $user,
            );
        }

        $this->dashboardStats->forget($user);

        return $delivery->fresh(['assignee', 'order', 'contact']);
    }

    public function cancel(Delivery $delivery, User $user, ?string $notes = null): Delivery
    {
        $this->assertStatus($delivery, [
            DeliveryStatus::Scheduled,
            DeliveryStatus::InTransit,
            DeliveryStatus::Failed,
        ]);

        $delivery->update([
            'status' => DeliveryStatus::Cancelled,
            'notes' => $notes ?: $delivery->notes,
        ]);

        if ($delivery->contact) {
            $this->timeline->log(
                $delivery->contact,
                'delivery_cancelled',
                'Delivery cancelled for '.$delivery->order?->order_number,
                $notes,
                ['delivery_id' => $delivery->id, 'order_id' => $delivery->order_id],
                $user,
            );
        }

        $this->dashboardStats->forget($user);

        return $delivery->fresh(['assignee', 'order', 'contact']);
    }

    /**
     * @return array<string, bool>
     */
    public function defaultChecklist(): array
    {
        return collect(config('crm.delivery_checklist', []))
            ->mapWithKeys(fn (string $label, string $slug) => [$slug => false])
            ->all();
    }

    /**
     * @param  list<DeliveryStatus>  $allowed
     */
    private function assertStatus(Delivery $delivery, array $allowed): void
    {
        if (! in_array($delivery->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'This delivery cannot be updated from its current status.',
            ]);
        }
    }

    private function assertMutable(Delivery $delivery): void
    {
        $this->assertStatus($delivery, [
            DeliveryStatus::Scheduled,
            DeliveryStatus::InTransit,
            DeliveryStatus::Failed,
        ]);
    }
}
