<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function move(int $warehouseId, int $productId, float $quantity, string $movementType, float $unitCost, string $referenceType, int $referenceId, ?string $note = null, bool $allowNegative = false): void
    {
        $stock = DB::table('inventory_stocks')
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            DB::table('inventory_stocks')->insert([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'quantity' => 0,
                'average_cost' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $stock = DB::table('inventory_stocks')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();
        }

        $before = (float) $stock->quantity;
        $after = $before + $quantity;

        if ($after < 0 && ! $allowNegative) {
            throw ValidationException::withMessages([
                'stock' => 'Insufficient stock for this transaction.',
            ]);
        }

        $averageCost = (float) $stock->average_cost;
        if ($quantity > 0 && $movementType === 'purchase') {
            $averageCost = (($before * $averageCost) + ($quantity * $unitCost)) / max($after, 1);
        }

        DB::table('inventory_stocks')->where('id', $stock->id)->update([
            'quantity' => $after,
            'average_cost' => $averageCost,
            'updated_at' => now(),
        ]);

        DB::table('inventory_ledgers')->insert([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'user_id' => session('user_id'),
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $after,
            'unit_cost' => $unitCost,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'note' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
