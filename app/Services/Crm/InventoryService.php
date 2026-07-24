<?php

namespace App\Services\Crm;

use App\Enums\Crm\MemberSaleStatus;
use App\Enums\Crm\StockMovementType;
use App\Models\Crm\CrmProduct;
use App\Models\Crm\MemberSale;
use App\Models\Crm\MemberSaleItem;
use App\Models\Crm\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function receive(CrmProduct $product, int $quantity, User $actor, ?string $reason = null, ?string $notes = null): StockMovement
    {
        abort_unless($actor->hasPermission('products.manage'), 403);

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Receive quantity must be at least 1.',
            ]);
        }

        return $this->applyDelta(
            $product,
            StockMovementType::Receive,
            $quantity,
            0,
            $actor,
            $reason ?? 'Stock received',
            $notes,
        );
    }

    public function adjust(CrmProduct $product, int $quantityDelta, User $actor, ?string $reason = null, ?string $notes = null): StockMovement
    {
        abort_unless($actor->hasPermission('products.manage'), 403);

        if ($quantityDelta === 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Adjustment quantity cannot be zero.',
            ]);
        }

        $type = $quantityDelta > 0 ? StockMovementType::AdjustIn : StockMovementType::AdjustOut;

        return $this->applyDelta(
            $product,
            $type,
            $quantityDelta,
            0,
            $actor,
            $reason ?? 'Manual adjustment',
            $notes,
        );
    }

    public function writeOff(CrmProduct $product, int $quantity, User $actor, ?string $reason = null, ?string $notes = null): StockMovement
    {
        abort_unless($actor->hasPermission('products.manage'), 403);

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Write-off quantity must be at least 1.',
            ]);
        }

        return $this->applyDelta(
            $product,
            StockMovementType::WriteOff,
            -$quantity,
            0,
            $actor,
            $reason ?? 'Write-off',
            $notes,
        );
    }

    public function setOnHand(CrmProduct $product, int $newQuantity, User $actor, ?string $notes = null): ?StockMovement
    {
        abort_unless($actor->hasPermission('products.manage'), 403);

        $newQuantity = max(0, $newQuantity);
        $delta = $newQuantity - (int) $product->inventory_quantity;

        if ($delta === 0) {
            return null;
        }

        return $this->adjust($product, $delta, $actor, 'Set on-hand quantity', $notes);
    }

    public function syncSaleInventory(MemberSale $sale, User $actor): void
    {
        $sale->loadMissing('items');

        $shouldReserve = in_array($sale->status, [
            MemberSaleStatus::Approved,
            MemberSaleStatus::Financing,
        ], true);

        $shouldDeduct = in_array($sale->status, [
            MemberSaleStatus::Delivered,
            MemberSaleStatus::Completed,
        ], true);

        if ($shouldDeduct) {
            if ($sale->inventory_reserved && ! $sale->inventory_deducted) {
                $this->consumeReservation($sale, $actor);
            } elseif (! $sale->inventory_deducted) {
                $this->deductForSale($sale, $actor);
            }

            return;
        }

        if ($sale->inventory_deducted) {
            $this->reverseSaleDeduction($sale, $actor);
        }

        if ($shouldReserve && ! $sale->inventory_reserved) {
            $this->reserveForSale($sale, $actor);
        } elseif (! $shouldReserve && $sale->inventory_reserved) {
            $this->releaseReservation($sale, $actor);
        }
    }

    public function releaseSaleInventory(MemberSale $sale, User $actor): void
    {
        $sale->loadMissing('items');

        if ($sale->inventory_deducted) {
            $this->reverseSaleDeduction($sale, $actor);
        }

        if ($sale->inventory_reserved) {
            $this->releaseReservation($sale, $actor);
        }
    }

    private function reserveForSale(MemberSale $sale, User $actor): void
    {
        foreach ($this->catalogItems($sale) as $item) {
            $this->applyDelta(
                $item['product'],
                StockMovementType::Reserve,
                0,
                $item['quantity'],
                $actor,
                'Reserved for member sale #'.$sale->id,
                null,
                $sale,
            );
        }

        $sale->update(['inventory_reserved' => true]);
    }

    private function releaseReservation(MemberSale $sale, User $actor): void
    {
        foreach ($this->catalogItems($sale) as $item) {
            $this->applyDelta(
                $item['product'],
                StockMovementType::ReleaseReserve,
                0,
                -$item['quantity'],
                $actor,
                'Released reservation for member sale #'.$sale->id,
                null,
                $sale,
            );
        }

        $sale->update(['inventory_reserved' => false]);
    }

    private function consumeReservation(MemberSale $sale, User $actor): void
    {
        foreach ($this->catalogItems($sale) as $item) {
            $this->applyDelta(
                $item['product'],
                StockMovementType::Sale,
                -$item['quantity'],
                -$item['quantity'],
                $actor,
                'Fulfilled member sale #'.$sale->id,
                null,
                $sale,
            );
        }

        $sale->update([
            'inventory_reserved' => false,
            'inventory_deducted' => true,
        ]);
    }

    private function deductForSale(MemberSale $sale, User $actor): void
    {
        foreach ($this->catalogItems($sale) as $item) {
            $this->applyDelta(
                $item['product'],
                StockMovementType::Sale,
                -$item['quantity'],
                0,
                $actor,
                'Fulfilled member sale #'.$sale->id,
                null,
                $sale,
            );
        }

        $sale->update(['inventory_deducted' => true]);
    }

    private function reverseSaleDeduction(MemberSale $sale, User $actor): void
    {
        foreach ($this->catalogItems($sale) as $item) {
            $this->applyDelta(
                $item['product'],
                StockMovementType::SaleReversal,
                $item['quantity'],
                0,
                $actor,
                'Reversed member sale #'.$sale->id,
                null,
                $sale,
            );
        }

        $sale->update(['inventory_deducted' => false]);
    }

    /**
     * @return list<array{product: CrmProduct, quantity: int}>
     */
    private function catalogItems(MemberSale $sale): array
    {
        return $sale->items
            ->filter(fn (MemberSaleItem $item) => $item->crm_product_id)
            ->map(function (MemberSaleItem $item) {
                $product = CrmProduct::query()->lockForUpdate()->find($item->crm_product_id);

                if (! $product) {
                    return null;
                }

                return [
                    'product' => $product,
                    'quantity' => max(1, (int) $item->quantity),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function applyDelta(
        CrmProduct $product,
        StockMovementType $type,
        int $onHandDelta,
        int $reservedDelta,
        User $actor,
        ?string $reason = null,
        ?string $notes = null,
        ?MemberSale $reference = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $type, $onHandDelta, $reservedDelta, $actor, $reason, $notes, $reference) {
            $locked = CrmProduct::query()->lockForUpdate()->findOrFail($product->id);

            $before = (int) $locked->inventory_quantity;
            $reservedBefore = (int) $locked->reserved_quantity;
            $after = $before + $onHandDelta;
            $reservedAfter = $reservedBefore + $reservedDelta;

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient on-hand stock for {$locked->name}. Available: {$before}.",
                ]);
            }

            if ($reservedAfter < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Cannot release more than reserved for {$locked->name}.",
                ]);
            }

            if ($reservedAfter > $after && $onHandDelta === 0 && $reservedDelta > 0) {
                $available = $before - $reservedBefore;
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient available stock for {$locked->name}. Available: {$available}.",
                ]);
            }

            $locked->update([
                'inventory_quantity' => $after,
                'reserved_quantity' => $reservedAfter,
            ]);

            return StockMovement::query()->create([
                'crm_product_id' => $locked->id,
                'type' => $type,
                'quantity_delta' => $onHandDelta !== 0 ? $onHandDelta : $reservedDelta,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reserved_before' => $reservedBefore,
                'reserved_after' => $reservedAfter,
                'reason' => $reason,
                'notes' => $notes,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->id,
                'user_id' => $actor->id,
            ]);
        });
    }
}
