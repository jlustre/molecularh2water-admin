<?php

namespace App\Services\Crm;

use App\Enums\Crm\InstallationStatus;
use App\Models\Crm\Delivery;
use App\Models\Crm\Installation;
use App\Models\Crm\Order;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

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

        if ($order->contact) {
            $this->timeline->log(
                $order->contact,
                'installation_scheduled',
                'Installation scheduled for '.$order->order_number,
                $installation->scheduled_at?->format('M j, Y g:i A'),
                ['installation_id' => $installation->id, 'order_id' => $order->id],
                $user,
            );
        }

        $this->dashboardStats->forget($user);

        return $installation->fresh(['technician', 'order', 'contact', 'delivery']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Installation $installation, array $data, User $user): Installation
    {
        $this->assertMutable($installation);

        $installation->update([
            'user_id' => Arr::get($data, 'user_id', $installation->user_id),
            'scheduled_at' => Arr::get($data, 'scheduled_at', $installation->scheduled_at),
            'notes' => Arr::get($data, 'notes', $installation->notes),
            'checklist' => Arr::get($data, 'checklist', $installation->checklist),
            'delivery_id' => Arr::has($data, 'delivery_id')
                ? Arr::get($data, 'delivery_id')
                : $installation->delivery_id,
        ]);

        if ($installation->contact) {
            $this->timeline->log(
                $installation->contact,
                'installation_updated',
                'Installation updated for '.$installation->order?->order_number,
                $installation->scheduled_at?->format('M j, Y g:i A'),
                ['installation_id' => $installation->id, 'order_id' => $installation->order_id],
                $user,
            );
        }

        $this->dashboardStats->forget($user);

        return $installation->fresh(['technician', 'order', 'contact', 'delivery']);
    }

    public function markInProgress(Installation $installation, User $user): Installation
    {
        $this->assertStatus($installation, [InstallationStatus::Scheduled, InstallationStatus::Failed]);

        $installation->update(['status' => InstallationStatus::InProgress]);

        if ($installation->contact) {
            $this->timeline->log(
                $installation->contact,
                'installation_in_progress',
                'Installation in progress for '.$installation->order?->order_number,
                null,
                ['installation_id' => $installation->id, 'order_id' => $installation->order_id],
                $user,
            );
        }

        $this->dashboardStats->forget($user);

        return $installation->fresh(['technician', 'order', 'contact']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function complete(Installation $installation, array $data, User $user): Installation
    {
        $this->assertStatus($installation, [
            InstallationStatus::Scheduled,
            InstallationStatus::InProgress,
            InstallationStatus::Failed,
        ]);

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

        if ($installation->contact) {
            $this->timeline->log(
                $installation->contact,
                'installation_completed',
                'Installation completed for '.$installation->order?->order_number,
                null,
                ['installation_id' => $installation->id, 'order_id' => $installation->order_id],
                $user,
            );

            $this->funnels->moveLeadToStageSlug($installation->contact, 'delivered-installed', $user);
        }

        if ($installation->order) {
            $this->orders->markFulfilled($installation->order, $user);
        }

        $this->dashboardStats->forget($user);

        return $installation->fresh(['technician', 'order', 'contact']);
    }

    public function fail(Installation $installation, User $user, ?string $notes = null): Installation
    {
        $this->assertStatus($installation, [InstallationStatus::Scheduled, InstallationStatus::InProgress]);

        $installation->update([
            'status' => InstallationStatus::Failed,
            'notes' => $notes ?: $installation->notes,
        ]);

        if ($installation->contact) {
            $this->timeline->log(
                $installation->contact,
                'installation_failed',
                'Installation failed for '.$installation->order?->order_number,
                $notes,
                ['installation_id' => $installation->id, 'order_id' => $installation->order_id],
                $user,
            );
        }

        $this->dashboardStats->forget($user);

        return $installation->fresh(['technician', 'order', 'contact']);
    }

    public function cancel(Installation $installation, User $user, ?string $notes = null): Installation
    {
        $this->assertStatus($installation, [
            InstallationStatus::Scheduled,
            InstallationStatus::InProgress,
            InstallationStatus::Failed,
        ]);

        $installation->update([
            'status' => InstallationStatus::Cancelled,
            'notes' => $notes ?: $installation->notes,
        ]);

        if ($installation->contact) {
            $this->timeline->log(
                $installation->contact,
                'installation_cancelled',
                'Installation cancelled for '.$installation->order?->order_number,
                $notes,
                ['installation_id' => $installation->id, 'order_id' => $installation->order_id],
                $user,
            );
        }

        $this->dashboardStats->forget($user);

        return $installation->fresh(['technician', 'order', 'contact']);
    }

    /**
     * @return array<string, bool>
     */
    public function defaultChecklist(): array
    {
        return collect(config('crm.installation_checklist', []))
            ->mapWithKeys(fn (string $label, string $slug) => [$slug => false])
            ->all();
    }

    /**
     * @param  list<InstallationStatus>  $allowed
     */
    private function assertStatus(Installation $installation, array $allowed): void
    {
        if (! in_array($installation->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'This installation cannot be updated from its current status.',
            ]);
        }
    }

    private function assertMutable(Installation $installation): void
    {
        $this->assertStatus($installation, [
            InstallationStatus::Scheduled,
            InstallationStatus::InProgress,
            InstallationStatus::Failed,
        ]);
    }
}
