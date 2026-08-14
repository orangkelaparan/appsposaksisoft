<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly InventoryService $inventory,
        private readonly AuditService $audit,
    ) {}

    public function create(array $data): int
    {
        return DB::transaction(function () use ($data): int {
            $subtotal = collect($data['items'])->sum(fn (array $item) => $item['quantity'] * $item['unit_cost']);
            $orderId = DB::table('purchase_orders')->insertGetId([
                'supplier_id' => $data['supplier_id'],
                'store_id' => $data['store_id'],
                'warehouse_id' => $data['warehouse_id'],
                'created_by' => session('user_id'),
                'po_number' => $this->numbers->next('purchase_order', $data['store_id']),
                'order_date' => now()->toDateString(),
                'expected_date' => $data['expected_date'] ?? null,
                'subtotal' => $subtotal,
                'grand_total' => $subtotal,
                'status' => 'approved',
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                DB::table('purchase_order_items')->insert([
                    'purchase_order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'ordered_quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $item['quantity'] * $item['unit_cost'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->audit->record('created', 'purchasing', 'purchase_order', $orderId, null, ['subtotal' => $subtotal]);

            return $orderId;
        });
    }

    public function receive(int $orderId, array $items): int
    {
        return DB::transaction(function () use ($orderId, $items): int {
            $order = DB::table('purchase_orders')->where('id', $orderId)->lockForUpdate()->first();
            if (! $order || in_array($order->status, ['cancelled', 'completed'], true)) {
                throw ValidationException::withMessages(['purchase_order' => 'This purchase order cannot receive stock.']);
            }

            $receiptId = DB::table('purchase_receipts')->insertGetId([
                'purchase_order_id' => $orderId,
                'warehouse_id' => $order->warehouse_id,
                'received_by' => session('user_id'),
                'receipt_number' => $this->numbers->next('purchase_receipt', $order->store_id),
                'received_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($items as $item) {
                $orderItem = DB::table('purchase_order_items')->where('id', $item['purchase_order_item_id'])->lockForUpdate()->first();
                if (! $orderItem || (float) $item['quantity'] <= 0 || ((float) $orderItem->received_quantity + (float) $item['quantity']) > (float) $orderItem->ordered_quantity) {
                    throw ValidationException::withMessages(['items' => 'Receipt quantity exceeds the outstanding purchase quantity.']);
                }

                DB::table('purchase_receipt_items')->insert([
                    'purchase_receipt_id' => $receiptId,
                    'purchase_order_item_id' => $orderItem->id,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $orderItem->unit_cost,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('purchase_order_items')->where('id', $orderItem->id)->increment('received_quantity', $item['quantity']);
                $this->inventory->move((int) $order->warehouse_id, (int) $orderItem->product_id, (float) $item['quantity'], 'purchase', (float) $orderItem->unit_cost, 'purchase_receipt', $receiptId, 'Purchase receiving');
            }

            $remaining = DB::table('purchase_order_items')->where('purchase_order_id', $orderId)->whereColumn('received_quantity', '<', 'ordered_quantity')->exists();
            DB::table('purchase_orders')->where('id', $orderId)->update(['status' => $remaining ? 'partially_received' : 'completed', 'updated_at' => now()]);
            $this->audit->record('received', 'purchasing', 'purchase_receipt', $receiptId);

            return $receiptId;
        });
    }
}
