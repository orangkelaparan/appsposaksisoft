<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly InventoryService $inventory,
        private readonly AuditService $audit,
    ) {}

    public function create(array $data): int
    {
        return DB::transaction(function () use ($data): int {
            $source = DB::table('warehouses')->where('id', $data['source_warehouse_id'])->lockForUpdate()->first();
            $destination = DB::table('warehouses')->where('id', $data['destination_warehouse_id'])->lockForUpdate()->first();
            if (! $source || ! $destination || $source->id === $destination->id) {
                throw ValidationException::withMessages(['warehouse' => 'Source and destination warehouses must be different active warehouses.']);
            }
            if (empty($data['items'])) {
                throw ValidationException::withMessages(['items' => 'A stock transfer requires at least one item.']);
            }

            $transferId = DB::table('stock_transfers')->insertGetId([
                'source_warehouse_id' => $source->id,
                'destination_warehouse_id' => $destination->id,
                'created_by' => session('user_id'),
                'transfer_number' => $this->numbers->next('stock_transfer', (int) $source->store_id),
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $product = DB::table('products')->where('id', $item['product_id'])->where('active', true)->first();
                if (! $product || (float) $item['quantity'] <= 0) {
                    throw ValidationException::withMessages(['items' => 'Each transfer item must reference an active product with a positive quantity.']);
                }
                $cost = (float) DB::table('inventory_stocks')->where('warehouse_id', $source->id)->where('product_id', $product->id)->value('average_cost');
                DB::table('stock_transfer_items')->insert([
                    'stock_transfer_id' => $transferId,
                    'product_id' => $product->id,
                    'requested_quantity' => $item['quantity'],
                    'unit_cost' => $cost ?: (float) $product->purchase_cost,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->audit->record('created', 'inventory', 'stock_transfer', $transferId, null, ['source_warehouse_id' => $source->id, 'destination_warehouse_id' => $destination->id]);

            return $transferId;
        });
    }

    public function approve(int $transferId): void
    {
        DB::transaction(function () use ($transferId): void {
            $transfer = DB::table('stock_transfers')->where('id', $transferId)->lockForUpdate()->first();
            if (! $transfer || $transfer->status !== 'draft') {
                throw ValidationException::withMessages(['transfer' => 'Only draft transfers may be approved.']);
            }
            DB::table('stock_transfers')->where('id', $transferId)->update(['status' => 'approved', 'approved_by' => session('user_id'), 'approved_at' => now(), 'updated_at' => now()]);
            $this->audit->record('approved', 'inventory', 'stock_transfer', $transferId);
        });
    }

    public function ship(int $transferId): void
    {
        DB::transaction(function () use ($transferId): void {
            $transfer = DB::table('stock_transfers')->where('id', $transferId)->lockForUpdate()->first();
            if (! $transfer || $transfer->status !== 'approved') {
                throw ValidationException::withMessages(['transfer' => 'Only approved transfers may be shipped.']);
            }
            foreach (DB::table('stock_transfer_items')->where('stock_transfer_id', $transferId)->lockForUpdate()->get() as $item) {
                $quantity = (float) $item->requested_quantity;
                $this->inventory->move((int) $transfer->source_warehouse_id, (int) $item->product_id, -$quantity, 'transfer_out', (float) $item->unit_cost, 'stock_transfer', $transferId, 'Stock transfer shipment');
                DB::table('stock_transfer_items')->where('id', $item->id)->update(['shipped_quantity' => $quantity, 'updated_at' => now()]);
            }
            DB::table('stock_transfers')->where('id', $transferId)->update(['status' => 'shipped', 'shipped_by' => session('user_id'), 'shipped_at' => now(), 'updated_at' => now()]);
            $this->audit->record('shipped', 'inventory', 'stock_transfer', $transferId);
        });
    }

    public function receive(int $transferId): void
    {
        DB::transaction(function () use ($transferId): void {
            $transfer = DB::table('stock_transfers')->where('id', $transferId)->lockForUpdate()->first();
            if (! $transfer || ! in_array($transfer->status, ['shipped', 'partially_received'], true)) {
                throw ValidationException::withMessages(['transfer' => 'Only shipped transfers may be received.']);
            }
            foreach (DB::table('stock_transfer_items')->where('stock_transfer_id', $transferId)->lockForUpdate()->get() as $item) {
                $remaining = (float) $item->shipped_quantity - (float) $item->received_quantity;
                if ($remaining <= 0) {
                    continue;
                }
                $this->inventory->move((int) $transfer->destination_warehouse_id, (int) $item->product_id, $remaining, 'transfer_in', (float) $item->unit_cost, 'stock_transfer', $transferId, 'Stock transfer receipt');
                DB::table('stock_transfer_items')->where('id', $item->id)->update(['received_quantity' => (float) $item->received_quantity + $remaining, 'updated_at' => now()]);
            }
            DB::table('stock_transfers')->where('id', $transferId)->update(['status' => 'received', 'received_by' => session('user_id'), 'received_at' => now(), 'updated_at' => now()]);
            $this->audit->record('received', 'inventory', 'stock_transfer', $transferId);
        });
    }
}
