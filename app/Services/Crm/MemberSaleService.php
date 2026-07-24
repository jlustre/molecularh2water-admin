<?php

namespace App\Services\Crm;

use App\Enums\Crm\CrmProductKind;
use App\Enums\Crm\MemberSaleStatus;
use App\Models\Crm\CrmProduct;
use App\Models\Crm\MemberSale;
use App\Models\Crm\MemberSaleItem;
use App\Models\User;
use App\Support\BusinessLineContext;
use App\Support\BusinessLineResolver;
use App\Support\Crm\CrmContactResolver;
use App\Support\Crm\CrmScope;
use App\Support\Crm\MemberSaleScope;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberSaleService
{
    public function __construct(
        private readonly InventoryService $inventory,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $items
     */
    public function create(array $data, array $items, User $actor): MemberSale
    {
        abort_unless($actor->hasPermission('sales.manage'), 403);

        return DB::transaction(function () use ($data, $items, $actor) {
            $sellerId = $this->resolveSellerId($data, $actor);
            $contact = $this->resolveContact($data);
            $status = MemberSaleStatus::from((string) Arr::get($data, 'status', MemberSaleStatus::ApplicationStarted->value));

            $sale = MemberSale::query()->create([
                'user_id' => $sellerId,
                'demo_consultant_id' => $this->resolveDemoConsultantId($data, $sellerId),
                'contact_type' => $contact?->getMorphClass(),
                'contact_id' => $contact?->id,
                'customer_name' => Arr::get($data, 'customer_name') ?: ($contact && method_exists($contact, 'fullName') ? $contact->fullName() : null),
                'customer_phone' => Arr::get($data, 'customer_phone') ?: ($contact?->phone ?? null),
                'customer_email' => Arr::get($data, 'customer_email') ?: ($contact?->email ?? null),
                'status' => $status,
                'business_line' => BusinessLineResolver::forRelatedContact($data, $actor, $contact),
                'notes' => Arr::get($data, 'notes'),
                'created_by' => $actor->id,
                ...$this->statusTimestamps($status, []),
            ]);

            $this->syncItems($sale, $items);
            $this->recalculateTotals($sale);
            $this->inventory->syncSaleInventory($sale->fresh(['items']), $actor);

            return $sale->fresh(['consultant', 'demoConsultant', 'items', 'contact', 'creator']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $items
     */
    public function update(MemberSale $sale, array $data, array $items, User $actor): MemberSale
    {
        abort_unless($actor->hasPermission('sales.manage'), 403);
        abort_unless(MemberSaleScope::saleIsAccessible($sale, $actor), 403);

        return DB::transaction(function () use ($sale, $data, $items, $actor) {
            $this->inventory->releaseSaleInventory($sale->load('items'), $actor);

            $sellerId = $this->resolveSellerId($data, $actor, $sale);
            $contact = $this->resolveContact($data, $sale);
            $status = MemberSaleStatus::from((string) Arr::get($data, 'status', $sale->status->value));

            $sale->update([
                'user_id' => $sellerId,
                'demo_consultant_id' => $this->resolveDemoConsultantId($data, $sellerId, $sale),
                'contact_type' => $contact?->getMorphClass(),
                'contact_id' => $contact?->id,
                'customer_name' => Arr::get($data, 'customer_name', $sale->customer_name),
                'customer_phone' => Arr::get($data, 'customer_phone', $sale->customer_phone),
                'customer_email' => Arr::get($data, 'customer_email', $sale->customer_email),
                'status' => $status,
                'business_line' => Arr::get($data, 'business_line')
                    ? BusinessLineResolver::forRelatedContact($data, $actor, $contact)
                    : ($sale->business_line?->value ?? BusinessLineContext::current($actor)),
                'notes' => Arr::get($data, 'notes', $sale->notes),
                'inventory_reserved' => false,
                'inventory_deducted' => false,
                ...$this->statusTimestamps($status, $sale->only([
                    'application_started_at',
                    'financing_at',
                    'approved_at',
                    'delivered_at',
                    'completed_at',
                ])),
            ]);

            $this->syncItems($sale, $items);
            $this->recalculateTotals($sale);
            $this->inventory->syncSaleInventory($sale->fresh(['items']), $actor);

            return $sale->fresh(['consultant', 'demoConsultant', 'items', 'contact', 'creator']);
        });
    }

    public function delete(MemberSale $sale, User $actor): void
    {
        abort_unless($actor->hasPermission('sales.manage'), 403);
        abort_unless(MemberSaleScope::saleIsAccessible($sale, $actor), 403);

        DB::transaction(function () use ($sale, $actor) {
            $this->inventory->releaseSaleInventory($sale->load('items'), $actor);
            $sale->delete();
        });
    }

    public function updateStatus(MemberSale $sale, MemberSaleStatus $status, User $actor): MemberSale
    {
        abort_unless($actor->hasPermission('sales.manage'), 403);
        abort_unless(MemberSaleScope::saleIsAccessible($sale, $actor), 403);

        return DB::transaction(function () use ($sale, $status, $actor) {
            $sale->update([
                'status' => $status,
                ...$this->statusTimestamps($status, $sale->only([
                    'application_started_at',
                    'financing_at',
                    'approved_at',
                    'delivered_at',
                    'completed_at',
                ])),
            ]);

            $this->inventory->syncSaleInventory($sale->fresh(['items']), $actor);

            return $sale->fresh(['consultant', 'demoConsultant', 'items', 'contact']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function syncItems(MemberSale $sale, array $items): void
    {
        $sale->items()->delete();

        foreach (array_values($items) as $index => $item) {
            $productId = Arr::get($item, 'crm_product_id');
            $product = $productId ? CrmProduct::query()->find($productId) : null;
            $kind = CrmProductKind::from((string) Arr::get(
                $item,
                'item_kind',
                $product?->kind?->value ?? CrmProductKind::Product->value,
            ));

            $quantity = max(1, (int) Arr::get($item, 'quantity', 1));
            $unitPrice = (float) Arr::get($item, 'unit_price', $product?->unit_price ?? 0);
            $name = trim((string) Arr::get($item, 'name', $product?->name ?? ''));

            if ($name === '') {
                throw ValidationException::withMessages([
                    'items' => 'Each line item needs a name or catalog product.',
                ]);
            }

            MemberSaleItem::query()->create([
                'member_sale_id' => $sale->id,
                'crm_product_id' => $product?->id,
                'item_kind' => $kind,
                'name' => $name,
                'sku' => Arr::get($item, 'sku') ?: $product?->sku,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => round($quantity * $unitPrice, 2),
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function recalculateTotals(MemberSale $sale): void
    {
        $items = $sale->items()->get();

        $subtotal = $items
            ->filter(fn (MemberSaleItem $item) => $item->item_kind === CrmProductKind::Product)
            ->sum(fn (MemberSaleItem $item) => (float) $item->line_total);

        $giftsTotal = $items
            ->filter(fn (MemberSaleItem $item) => $item->item_kind === CrmProductKind::Gift)
            ->sum(fn (MemberSaleItem $item) => (float) $item->line_total);

        $sale->update([
            'subtotal' => round($subtotal, 2),
            'gifts_total' => round($giftsTotal, 2),
            'total' => round($subtotal + $giftsTotal, 2),
        ]);
    }

    /**
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    private function statusTimestamps(MemberSaleStatus $status, array $existing): array
    {
        $column = $status->timestampColumn();
        $stamps = [];

        foreach (MemberSaleStatus::cases() as $case) {
            $key = $case->timestampColumn();
            $stamps[$key] = $existing[$key] ?? null;
        }

        if (empty($stamps[$column])) {
            $stamps[$column] = now();
        }

        if ($status === MemberSaleStatus::ApplicationStarted && empty($stamps['application_started_at'])) {
            $stamps['application_started_at'] = now();
        }

        return $stamps;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveSellerId(array $data, User $actor, ?MemberSale $sale = null): int
    {
        $requested = (int) (Arr::get($data, 'user_id') ?: ($sale?->user_id ?: $actor->id));

        if (MemberSaleScope::userCanManage($actor)) {
            return $requested > 0 ? $requested : $actor->id;
        }

        return $actor->id;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveDemoConsultantId(array $data, int $consultantId, ?MemberSale $sale = null): ?int
    {
        if (! array_key_exists('demo_consultant_id', $data) && $sale) {
            return $sale->demo_consultant_id;
        }

        $demoId = Arr::get($data, 'demo_consultant_id');

        if ($demoId === null || $demoId === '' || (int) $demoId === 0) {
            return null;
        }

        $demoId = (int) $demoId;

        return $demoId === $consultantId ? null : $demoId;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveContact(array $data, ?MemberSale $sale = null)
    {
        $type = Arr::get($data, 'contact_type') ?: $sale?->contact_type;
        $id = Arr::get($data, 'contact_id') ?: $sale?->contact_id;

        if (! $type || ! $id) {
            return null;
        }

        $contact = CrmContactResolver::resolve((string) $type, (int) $id);

        abort_unless(
            CrmScope::contacts($contact->newQuery())->whereKey($contact->id)->exists(),
            403,
        );

        return $contact;
    }
}
