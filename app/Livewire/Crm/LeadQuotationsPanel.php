<?php

namespace App\Livewire\Crm;

use App\Models\Crm\CrmProduct;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\Quotation;
use App\Services\Crm\OrderService;
use App\Services\Crm\QuotationService;
use App\Support\Crm\CrmRoutes;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class LeadQuotationsPanel extends Component
{
    use AuthorizesRequests;

    public Lead|Prospect|Customer|Recruit $lead;

    public bool $showBuilder = false;

    public string $discount_amount = '0';

    public string $tax_amount = '0';

    public string $shipping_amount = '0';

    public string $warranty_notes = '';

    public string $financing_notes = '';

    public string $payment_plan_notes = '';

    public string $notes = '';

    public string $valid_until = '';

    /** @var list<array{crm_product_id: int|null, description: string, quantity: int, unit_price: string}> */
    public array $lineItems = [];

    public function mount(Lead|Prospect|Customer|Recruit $lead): void
    {
        $this->authorize('view', $lead);
        $this->lead = $lead;
        $this->valid_until = now()->addDays(30)->format('Y-m-d');
        $this->resetLineItems();
    }

    public function toggleBuilder(): void
    {
        $this->authorize('update', $this->lead);
        $this->showBuilder = ! $this->showBuilder;

        if ($this->showBuilder && $this->lineItems === []) {
            $this->resetLineItems();
        }
    }

    public function addLineItem(): void
    {
        $this->authorize('update', $this->lead);
        $this->lineItems[] = [
            'crm_product_id' => null,
            'description' => '',
            'quantity' => 1,
            'unit_price' => '0',
        ];
    }

    public function removeLineItem(int $index): void
    {
        $this->authorize('update', $this->lead);
        unset($this->lineItems[$index]);
        $this->lineItems = array_values($this->lineItems);
    }

    public function updatedLineItems(mixed $value, string $key): void
    {
        if (! str_contains($key, 'crm_product_id')) {
            return;
        }

        $index = (int) explode('.', $key)[0];
        $productId = $this->lineItems[$index]['crm_product_id'] ?? null;

        if (! $productId) {
            return;
        }

        $product = CrmProduct::query()->find($productId);

        if ($product) {
            $this->lineItems[$index]['description'] = $product->name;
            $this->lineItems[$index]['unit_price'] = (string) $product->unit_price;
        }
    }

    public function saveQuotation(QuotationService $quotations): void
    {
        $this->authorize('update', $this->lead);

        $this->validate([
            'lineItems' => ['required', 'array', 'min:1'],
            'lineItems.*.description' => ['required', 'string', 'max:255'],
            'lineItems.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'lineItems.*.unit_price' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'valid_until' => ['required', 'date'],
        ]);

        $quotations->create(
            $this->lead,
            [
                'discount_amount' => (float) $this->discount_amount,
                'tax_amount' => (float) $this->tax_amount,
                'shipping_amount' => (float) $this->shipping_amount,
                'warranty_notes' => $this->warranty_notes ?: null,
                'financing_notes' => $this->financing_notes ?: null,
                'payment_plan_notes' => $this->payment_plan_notes ?: null,
                'notes' => $this->notes ?: null,
                'valid_until' => $this->valid_until,
            ],
            $this->lineItems,
            auth()->user(),
        );

        $this->resetBuilder();
        $this->lead->refresh();
    }

    public function createOrder(int $quotationId, OrderService $orders): void
    {
        $this->authorize('update', $this->lead);

        $quotation = Quotation::query()
            ->whereContact($this->lead)
            ->with('items')
            ->findOrFail($quotationId);

        $order = $orders->createFromQuotation($quotation, auth()->user());
        $orders->submit($order, auth()->user());

        $this->lead->refresh();
    }

    public function presentQuote(int $quotationId, QuotationService $quotations): void
    {
        $this->authorize('update', $this->lead);

        $quotation = Quotation::query()
            ->whereContact($this->lead)
            ->findOrFail($quotationId);

        $quotations->present($quotation, auth()->user());
        $this->lead->refresh();
    }

    public function pdfUrl(Quotation $quotation): string
    {
        return CrmRoutes::url('quotations.pdf', ['quotation' => $quotation]);
    }

    public function getSubtotalProperty(): float
    {
        return round(collect($this->lineItems)->sum(function (array $item) {
            return ((int) ($item['quantity'] ?? 1)) * ((float) ($item['unit_price'] ?? 0));
        }), 2);
    }

    public function getEstimatedTotalProperty(): float
    {
        return max(0, round(
            $this->subtotal
            - (float) $this->discount_amount
            + (float) $this->tax_amount
            + (float) $this->shipping_amount,
            2
        ));
    }

    private function resetBuilder(): void
    {
        $this->reset([
            'showBuilder', 'discount_amount', 'tax_amount', 'shipping_amount',
            'warranty_notes', 'financing_notes', 'payment_plan_notes', 'notes',
        ]);
        $this->discount_amount = '0';
        $this->tax_amount = '0';
        $this->shipping_amount = '0';
        $this->valid_until = now()->addDays(30)->format('Y-m-d');
        $this->resetLineItems();
    }

    private function resetLineItems(): void
    {
        $this->lineItems = [[
            'crm_product_id' => null,
            'description' => '',
            'quantity' => 1,
            'unit_price' => '0',
        ]];
    }

    public function render()
    {
        return view('livewire.crm.lead-quotations-panel', [
            'quotations' => $this->lead->quotations()->with(['items', 'author', 'order'])->limit(10)->get(),
            'products' => CrmProduct::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }
}
