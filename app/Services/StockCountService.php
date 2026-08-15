<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockCountService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly InventoryService $inventory,
        private readonly AuditService $audit,
    ) {}

    public function create(int $warehouseId, ?string $notes = null): int
    {
        return DB::transaction(function () use ($warehouseId, $notes): int {
            $warehouse = DB::table('warehouses')->where('id', $warehouseId)->lockForUpdate()->first();
            if (! $warehouse) {
                throw ValidationException::withMessages(['warehouse_id' => 'The selected warehouse is unavailable.']);
            }
            $countId = DB::table('stock_counts')->insertGetId([
                'warehouse_id' => $warehouseId,
                'created_by' => session('user_id'),
                'count_number' => $this->numbers->next('stock_count', (int) $warehouse->store_id),
                'status' => 'draft',
                'snapshot_at' => now(),
                'notes' => $notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach (DB::table('inventory_stocks')->where('warehouse_id', $warehouseId)->lockForUpdate()->get() as $stock) {
                DB::table('stock_count_items')->insert([
                    'stock_count_id' => $countId,
                    'product_id' => $stock->product_id,
                    'system_quantity' => $stock->quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->audit->record('created', 'inventory', 'stock_count', $countId, null, ['warehouse_id' => $warehouseId]);

            return $countId;
        });
    }

    public function record(int $countId, array $quantities): void
    {
        DB::transaction(function () use ($countId, $quantities): void {
            $count = DB::table('stock_counts')->where('id', $countId)->lockForUpdate()->first();
            if (! $count || ! in_array($count->status, ['draft', 'counted'], true)) {
                throw ValidationException::withMessages(['stock_count' => 'Only draft stock counts may be recorded.']);
            }
            foreach ($quantities as $productId => $quantity) {
                $item = DB::table('stock_count_items')->where('stock_count_id', $countId)->where('product_id', $productId)->lockForUpdate()->first();
                if (! $item || (float) $quantity < 0) {
                    throw ValidationException::withMessages(['items' => 'The stock count contains an invalid item or quantity.']);
                }
                DB::table('stock_count_items')->where('id', $item->id)->update([
                    'counted_quantity' => $quantity,
                    'variance_quantity' => (float) $quantity - (float) $item->system_quantity,
                    'updated_at' => now(),
                ]);
            }
            DB::table('stock_counts')->where('id', $countId)->update(['status' => 'counted', 'counted_at' => now(), 'updated_at' => now()]);
            $this->audit->record('counted', 'inventory', 'stock_count', $countId);
        });
    }

    public function approve(int $countId): void
    {
        DB::transaction(function () use ($countId): void {
            $count = DB::table('stock_counts')->where('id', $countId)->lockForUpdate()->first();
            if (! $count || $count->status !== 'counted') {
                throw ValidationException::withMessages(['stock_count' => 'Only counted stocktakes may be approved.']);
            }
            foreach (DB::table('stock_count_items')->where('stock_count_id', $countId)->lockForUpdate()->get() as $item) {
                if ($item->counted_quantity === null) {
                    throw ValidationException::withMessages(['items' => 'Every stock count item must have a physical quantity before approval.']);
                }
                $current = DB::table('inventory_stocks')->where('warehouse_id', $count->warehouse_id)->where('product_id', $item->product_id)->lockForUpdate()->first();
                $actual = (float) ($current->quantity ?? 0);
                $difference = (float) $item->counted_quantity - $actual;
                if ($difference !== 0.0) {
                    $this->inventory->move((int) $count->warehouse_id, (int) $item->product_id, $difference, 'stock_count', (float) ($current->average_cost ?? 0), 'stock_count', $countId, 'Approved stocktake adjustment', true);
                }
            }
            DB::table('stock_counts')->where('id', $countId)->update(['status' => 'approved', 'approved_by' => session('user_id'), 'approved_at' => now(), 'updated_at' => now()]);
            $this->audit->record('approved', 'inventory', 'stock_count', $countId);
        });
    }
}
