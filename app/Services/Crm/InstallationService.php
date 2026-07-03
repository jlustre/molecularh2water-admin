<?php

namespace App\Services\Crm;

use App\Enums\Crm\InstallationStatus;
use App\Models\Crm\Delivery;
use App\Models\Crm\Installation;
use App\Models\Crm\Order;
use App\Models\User;
use Illuminate\Support\Arr;

class InstallationService
{
    public function __construct(
        private readonly TimelineService $timeline,
        private readonly FunnelService $funnels,
        private readonly OrderService $orders,
        private readonly DashboardStatsService $dashboardStats,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function schedule(Order $order, array $data, User $user, ?Delivery $delivery = null): Installation
    {
        $installation = Installation::query()->create([
            'order_id' => $order->id,
            'delivery_id' => $delivery?->id,
            'contact_type' => $order->contact_type,
            'contact_id' => $order->contact_id,
            'user_id' => Arr::get($data, 'user_id', $user->id),
            'status' => InstallationStatus::Scheduled,
            'scheduled_at' => Arr::get($data, 'scheduled_at', now()->addDays(2)),
            'checklist' => $this->defaultChecklist(),
            'notes' => Arr::get($data, 'notes'),
        ]);

        $this->timeline->log(
            $order->lead,
            'installation_scheduled',
            'Installation scheduled for '.$order->order_number,
            $installation->scheduled_at?->format('M j, Y g:i A'),
            ['installation_id' => $installation->id, 'order_id' => $order->id],
            $user,
        );

        $this->dashboardStats->forget($user);

        return $installation->fresh(['technician']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function complete(Installation $installation, array $data, User $user): Installation
    {
        $checklist = Arr::get($data, 'checklist', $installation->checklist ?? []);
        $photoPaths = array_values(array_filter(array_merge(
            $installation->photo_paths ?? [],
            Arr::get($data, 'photo_paths', []),
        )));

        $installation->update([
            'status' => InstallationStatus::Completed,
            'completed_at' => now(),
            'checklist' => $checklist,
            'photo_paths' => $photoPaths ?: null,
            'notes' => Arr::get($data, 'notes', $installation->notes),
        ]);

        $this->timeline->log(
            $installation->lead,
            'installation_completed',
            'Installation completed for '.$installation->order->order_number,
            null,
            ['installation_id' => $installation->id, 'order_id' => $installation->order_id],
            $user,
        );

        $this->funnels->moveLeadToStageSlug($installation->lead, 'delivered-installed', $user);
        $this->orders->markFulfilled($installation->order, $user);
        $this->dashboardStats->forget($user);

        return $installation->fresh(['technician', 'order']);
    }

    /**
     * @return array<string, bool>
     */
    private function defaultChecklist(): array
    {
        return collect(config('crm.installation_checklist', []))
            ->mapWithKeys(fn (string $label, string $slug) => [$slug => false])
            ->all();
    }
}
