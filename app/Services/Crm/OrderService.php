<?php

namespace App\Services\Crm;

use App\Enums\Crm\OrderStatus;
use App\Enums\Crm\PaymentStatus;
use App\Enums\Crm\QuotationStatus;
use App\Models\Crm\Lead;
use App\Models\Crm\Order;
use App\Models\Crm\OrderItem;
use App\Models\Crm\Quotation;
use App\Models\User;
use App\Services\Crm\CrmAutomationService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly TimelineService $timeline,
        private readonly FunnelService $funnels,
        private readonly DashboardStatsService $dashboardStats,
    ) {}

    public function createFromQuotation(Quotation $quotation, User $user): Order
    {
        if ($quotation->order()->exists()) {
            throw ValidationException::withMessages([
                'quotation' => 'An order already exists for this quotation.',
            ]);
        }

        $quotation->load('items', 'lead');

        return DB::transaction(function () use ($quotation, $user) {
            $order = Order::query()->create([
                'contact_type' => $quotation->contact_type,
                'contact_id' => $quotation->contact_id,
                'quotation_id' => $quotation->id,
                'user_id' => $user->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => OrderStatus::Draft,
                'payment_status' => PaymentStatus::Pending,
                'subtotal' => $quotation->subtotal ?? 0,
                'discount_amount' => $quotation->discount_amount ?? 0,
                'tax_amount' => $quotation->tax_amount ?? 0,
                'shipping_amount' => $quotation->shipping_amount ?? 0,
                'total' => $quotation->total ?? 0,
                'notes' => $quotation->notes,
            ]);

            foreach ($quotation->items as $index => $item) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'quotation_item_id' => $item->id,
                    'crm_product_id' => $item->crm_product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                    'sort_order' => $index + 1,
                ]);
            }

            if ($quotation->status !== QuotationStatus::Accepted) {
                $quotation->update([
                    'status' => QuotationStatus::Accepted,
                    'accepted_at' => now(),
                ]);
            }

            $this->timeline->log(
                $quotation->lead,
                'order_created',
                'Order '.$order->order_number.' created from quote',
                null,
                ['order_id' => $order->id, 'quotation_id' => $quotation->id],
                $user,
            );

            $this->dashboardStats->forget($user);

            return $order->fresh(['items', 'author', 'quotation']);
        });
    }

    public function submit(Order $order, User $user): Order
    {
        $order->update([
            'status' => OrderStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $this->timeline->log(
            $order->lead,
            'order_submitted',
            'Order '.$order->order_number.' submitted',
            null,
            ['order_id' => $order->id],
            $user,
        );

        $this->funnels->moveLeadToStageSlug($order->lead, 'order-submitted', $user);
        $this->dashboardStats->forget($user);

        return $order->fresh(['items', 'deliveries', 'installations']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordPayment(Order $order, array $data, User $user): Order
    {
        $amount = (float) Arr::get($data, 'amount', $order->total);
        $newPaid = round((float) $order->amount_paid + $amount, 2);
        $total = (float) $order->total;

        $paymentStatus = match (true) {
            $newPaid <= 0 => PaymentStatus::Pending,
            $newPaid < $total => PaymentStatus::Partial,
            default => PaymentStatus::Paid,
        };

        $order->update([
            'amount_paid' => $newPaid,
            'payment_status' => $paymentStatus,
            'payment_method' => Arr::get($data, 'payment_method', $order->payment_method),
            'payment_reference' => Arr::get($data, 'payment_reference', $order->payment_reference),
            'paid_at' => $paymentStatus === PaymentStatus::Paid ? now() : $order->paid_at,
            'status' => $order->status === OrderStatus::Draft
                ? OrderStatus::Submitted
                : $order->status,
        ]);

        $this->timeline->log(
            $order->lead,
            'order_payment_recorded',
            'Payment recorded for '.$order->order_number,
            '$'.number_format($amount, 2).' — '.$paymentStatus->label(),
            ['order_id' => $order->id, 'amount' => $amount, 'payment_status' => $paymentStatus->value],
            $user,
        );

        if ($paymentStatus === PaymentStatus::Paid) {
            $order->update(['status' => OrderStatus::Confirmed]);
            $this->funnels->moveLeadToStageSlug($order->lead, 'payment-received', $user);

            app(CrmAutomationService::class)->dispatch('order.paid', [
                'lead_id' => $order->contact_id,
                'order_id' => $order->id,
            ], $user);
        }

        $this->dashboardStats->forget($user);

        return $order->fresh(['items', 'deliveries', 'installations']);
    }

    public function markFulfilled(Order $order, User $user): Order
    {
        $order->update(['status' => OrderStatus::Fulfilled]);

        $this->timeline->log(
            $order->lead,
            'order_fulfilled',
            'Order '.$order->order_number.' fulfilled',
            null,
            ['order_id' => $order->id],
            $user,
        );

        $this->dashboardStats->forget($user);

        return $order->fresh(['items', 'deliveries', 'installations']);
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'O-'.now()->format('Ymd');
        $count = Order::query()
            ->where('order_number', 'like', $prefix.'%')
            ->count();

        return sprintf('%s-%04d', $prefix, $count + 1);
    }
}
