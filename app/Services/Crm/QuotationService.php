<?php

namespace App\Services\Crm;

use App\Enums\Crm\QuotationStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Quotation;
use App\Models\Crm\QuotationItem;
use App\Models\Crm\Recruit;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    public function __construct(
        private readonly TimelineService $timeline,
        private readonly FunnelService $funnels,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $items
     */
    public function create(Lead|Prospect|Customer|Recruit $lead, array $data, array $items, User $user): Quotation
    {
        return DB::transaction(function () use ($lead, $data, $items, $user) {
            $quotation = Quotation::query()->create([
                'contact_type' => $lead->getMorphClass(),
                'contact_id' => $lead->id,
                'user_id' => $user->id,
                'consultation_id' => Arr::get($data, 'consultation_id'),
                'quote_number' => $this->generateQuoteNumber(),
                'status' => QuotationStatus::Draft,
                'discount_amount' => (float) Arr::get($data, 'discount_amount', 0),
                'tax_amount' => (float) Arr::get($data, 'tax_amount', 0),
                'shipping_amount' => (float) Arr::get($data, 'shipping_amount', 0),
                'warranty_notes' => Arr::get($data, 'warranty_notes'),
                'financing_notes' => Arr::get($data, 'financing_notes'),
                'payment_plan_notes' => Arr::get($data, 'payment_plan_notes'),
                'notes' => Arr::get($data, 'notes'),
                'valid_until' => Arr::get($data, 'valid_until', now()->addDays(30)),
            ]);

            $this->syncItems($quotation, $items);
            $this->recalculateTotals($quotation);

            $this->timeline->log(
                $lead,
                'quotation_created',
                'Quote '.$quotation->quote_number.' created',
                null,
                ['quotation_id' => $quotation->id, 'quote_number' => $quotation->quote_number],
                $user,
            );

            return $quotation->fresh(['items', 'author']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function update(Quotation $quotation, array $data, array $items, User $user): Quotation
    {
        return DB::transaction(function () use ($quotation, $data, $items, $user) {
            $quotation->update(Arr::only($data, [
                'discount_amount', 'tax_amount', 'shipping_amount',
                'warranty_notes', 'financing_notes', 'payment_plan_notes', 'notes', 'valid_until',
            ]));

            $this->syncItems($quotation, $items);
            $this->recalculateTotals($quotation);

            return $quotation->fresh(['items', 'author']);
        });
    }

    public function present(Quotation $quotation, User $user): Quotation
    {
        $quotation->update([
            'status' => QuotationStatus::Presented,
            'presented_at' => now(),
        ]);

        $this->timeline->log(
            $quotation->lead,
            'quotation_presented',
            'Quote '.$quotation->quote_number.' presented',
            null,
            ['quotation_id' => $quotation->id],
            $user,
        );

        $this->funnels->moveLeadToStageSlug($quotation->lead, 'quote-presented', $user);

        return $quotation->fresh(['items', 'author', 'lead']);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function syncItems(Quotation $quotation, array $items): void
    {
        $quotation->items()->delete();

        foreach (array_values($items) as $index => $item) {
            $quantity = max(1, (int) Arr::get($item, 'quantity', 1));
            $unitPrice = (float) Arr::get($item, 'unit_price', 0);
            $lineTotal = round($quantity * $unitPrice, 2);

            QuotationItem::query()->create([
                'quotation_id' => $quotation->id,
                'crm_product_id' => Arr::get($item, 'crm_product_id'),
                'description' => trim((string) Arr::get($item, 'description', 'Line item')),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'sort_order' => $index + 1,
            ]);
        }
    }

    public function recalculateTotals(Quotation $quotation): Quotation
    {
        $quotation->load('items');

        $subtotal = round((float) $quotation->items->sum('line_total'), 2);
        $discount = (float) $quotation->discount_amount;
        $tax = (float) $quotation->tax_amount;
        $shipping = (float) $quotation->shipping_amount;
        $total = max(0, round($subtotal - $discount + $tax + $shipping, 2));

        $quotation->update([
            'subtotal' => $subtotal,
            'total' => $total,
        ]);

        return $quotation->fresh(['items']);
    }

    private function generateQuoteNumber(): string
    {
        $prefix = 'Q-'.now()->format('Ymd');
        $count = Quotation::query()
            ->where('quote_number', 'like', $prefix.'%')
            ->count();

        return sprintf('%s-%04d', $prefix, $count + 1);
    }
}
