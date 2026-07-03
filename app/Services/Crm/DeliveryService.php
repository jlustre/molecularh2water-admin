<?php

namespace App\Services\Crm;

use App\Enums\Crm\DeliveryStatus;
use App\Models\Crm\Delivery;
use App\Models\Crm\Order;
use App\Models\User;
use App\Services\Crm\CrmAutomationService;
use Illuminate\Support\Arr;

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
        $lead = $order->lead;

        $delivery = Delivery::query()->create([
            'order_id' => $order->id,
            'contact_type' => $order->contact_type,
            'contact_id' => $order->contact_id,
            'user_id' => Arr::get($data, 'user_id', $user->id),
            'status' => DeliveryStatus::Scheduled,
            'scheduled_at' => Arr::get($data, 'scheduled_at', now()->addDay()),
            'address' => Arr::get($data, 'address', $lead->address),
            'contact_name' => Arr::get($data, 'contact_name', $lead->fullName()),
            'contact_phone' => Arr::get($data, 'contact_phone', $lead->phone),
            'checklist' => $this->defaultChecklist(),
            'notes' => Arr::get($data, 'notes'),
        ]);

        $this->timeline->log(
            $lead,
            'delivery_scheduled',
            'Delivery scheduled for '.$order->order_number,
            $delivery->scheduled_at?->format('M j, Y g:i A'),
            ['delivery_id' => $delivery->id, 'order_id' => $order->id],
            $user,
        );

        $this->funnels->moveLeadToStageSlug($lead, 'delivery-scheduled', $user);
        $this->dashboardStats->forget($user);

        return $delivery->fresh(['assignee']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function complete(Delivery $delivery, array $data, User $user): Delivery
    {
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

        $this->timeline->log(
            $delivery->lead,
            'delivery_completed',
            'Delivery completed for '.$delivery->order->order_number,
            null,
            ['delivery_id' => $delivery->id, 'order_id' => $delivery->order_id],
            $user,
        );

        app(CrmAutomationService::class)->dispatch('delivery.completed', [
            'lead_id' => $delivery->contact_id,
            'delivery_id' => $delivery->id,
            'order_id' => $delivery->order_id,
        ], $user);

        $this->dashboardStats->forget($user);

        return $delivery->fresh(['assignee', 'order']);
    }

    /**
     * @return array<string, bool>
     */
    private function defaultChecklist(): array
    {
        return collect(config('crm.delivery_checklist', []))
            ->mapWithKeys(fn (string $label, string $slug) => [$slug => false])
            ->all();
    }
}
