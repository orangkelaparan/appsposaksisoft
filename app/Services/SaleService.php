<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly InventoryService $inventory,
        private readonly AuditService $audit,
    ) {}

    public function complete(array $data): int
    {
        return DB::transaction(function () use ($data): int {
            $items = collect($data['items']);
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'A sale requires at least one item.']);
            }

            $subtotal = $items->sum(fn (array $item) => $item['quantity'] * $item['unit_price']);
            $discount = min((float) ($data['discount_total'] ?? 0), $subtotal);
            $tax = 0.0;
            $total = $subtotal - $discount;
            $tendered = (float) ($data['tendered_amount'] ?? $total);
            if ($tendered < $total) {
                throw ValidationException::withMessages(['tendered_amount' => 'Payment amount is less than the amount due.']);
            }

            $saleId = DB::table('sales')->insertGetId([
                'store_id' => $data['store_id'],
                'warehouse_id' => $data['warehouse_id'],
                'register_session_id' => $data['register_session_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'cashier_id' => session('user_id'),
                'invoice_number' => $this->numbers->next('sale', $data['store_id']),
                'sold_at' => now(),
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'tax_total' => $tax,
                'rounding_total' => 0,
                'grand_total' => $total,
                'paid_total' => $total,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($items as $item) {
                $product = DB::table('products')->where('id', $item['product_id'])->where('active', true)->lockForUpdate()->first();
                if (! $product) {
                    throw ValidationException::withMessages(['items' => 'An item is no longer available for sale.']);
                }
                $lineSubtotal = (float) $item['quantity'] * (float) $item['unit_price'];
                $lineDiscount = $subtotal > 0 ? $discount * ($lineSubtotal / $subtotal) : 0;
                $lineTotal = $lineSubtotal - $lineDiscount;

                DB::table('sale_items')->insert([
                    'sale_id' => $saleId,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $product->purchase_cost,
                    'discount_amount' => $lineDiscount,
                    'tax_amount' => 0,
                    'line_total' => $lineTotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($product->track_inventory) {
                    $this->inventory->move((int) $data['warehouse_id'], (int) $product->id, -1 * (float) $item['quantity'], 'sale', (float) $product->purchase_cost, 'sale', $saleId, 'POS sale', (bool) $product->allow_negative_inventory);
                }
            }

            $change = max(0, $tendered - $total);
            DB::table('payments')->insert([
                'sale_id' => $saleId,
                'user_id' => session('user_id'),
                'method' => $data['payment_method'] ?? 'cash',
                'amount' => $total,
                'tendered_amount' => $tendered,
                'change_amount' => $change,
                'paid_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->audit->record('completed', 'sales', 'sale', $saleId, null, ['total' => $total, 'items' => $items->count()]);

            return $saleId;
        });
    }

    public function returnItem(int $saleId, int $saleItemId, float $quantity, string $reason): int
    {
        return DB::transaction(function () use ($saleId, $saleItemId, $quantity, $reason): int {
            $sale = DB::table('sales')->where('id', $saleId)->lockForUpdate()->first();
            $item = DB::table('sale_items')->where('id', $saleItemId)->where('sale_id', $saleId)->lockForUpdate()->first();
            if (! $sale || ! $item || $quantity <= 0 || $quantity > (float) $item->quantity) {
                throw ValidationException::withMessages(['return' => 'The requested return is invalid.']);
            }

            $refund = $quantity * (float) $item->unit_price;
            $returnId = DB::table('sale_returns')->insertGetId([
                'sale_id' => $saleId,
                'warehouse_id' => $sale->warehouse_id,
                'created_by' => session('user_id'),
                'return_number' => $this->numbers->next('sale_return', $sale->store_id),
                'refund_total' => $refund,
                'refund_method' => 'cash',
                'status' => 'completed',
                'reason' => $reason,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('sale_return_items')->insert([
                'sale_return_id' => $returnId,
                'sale_item_id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $quantity,
                'refund_amount' => $refund,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->inventory->move((int) $sale->warehouse_id, (int) $item->product_id, $quantity, 'sales_return', (float) $item->unit_cost, 'sale_return', $returnId, $reason, true);
            DB::table('sales')->where('id', $saleId)->update(['status' => 'partially_refunded', 'updated_at' => now()]);
            $this->audit->record('returned', 'sales', 'sale_return', $returnId, null, ['refund_total' => $refund]);

            return $returnId;
        });
    }
}
