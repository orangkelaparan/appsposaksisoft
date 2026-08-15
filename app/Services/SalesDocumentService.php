<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesDocumentService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly AuditService $audit,
    ) {}

    public function createQuotation(array $data): int
    {
        return DB::transaction(function () use ($data): int {
            [$items, $subtotal, $discount, $total] = $this->prepareItems($data['items'], (float) ($data['discount_total'] ?? 0));
            $quotationId = DB::table('quotations')->insertGetId([
                'store_id' => $data['store_id'],
                'warehouse_id' => $data['warehouse_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'created_by' => session('user_id'),
                'quote_number' => $this->numbers->next('quotation', (int) $data['store_id']),
                'quote_date' => $data['quote_date'] ?? now()->toDateString(),
                'valid_until' => $data['valid_until'] ?? now()->addDays(14)->toDateString(),
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'tax_total' => 0,
                'grand_total' => $total,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($items as $item) {
                DB::table('quotation_items')->insert(array_merge($item, ['quotation_id' => $quotationId, 'created_at' => now(), 'updated_at' => now()]));
            }
            $this->audit->record('created', 'sales', 'quotation', $quotationId, null, ['total' => $total]);

            return $quotationId;
        });
    }

    public function convertToOrder(int $quotationId, ?string $dueDate = null): int
    {
        return DB::transaction(function () use ($quotationId, $dueDate): int {
            $quote = DB::table('quotations')->where('id', $quotationId)->lockForUpdate()->first();
            if (! $quote || ! in_array($quote->status, ['draft', 'accepted'], true)) {
                throw ValidationException::withMessages(['quotation' => 'Only a draft or accepted quotation may be converted to a sales order.']);
            }
            $orderId = DB::table('sales_orders')->insertGetId([
                'store_id' => $quote->store_id,
                'warehouse_id' => $quote->warehouse_id,
                'customer_id' => $quote->customer_id,
                'quotation_id' => $quote->id,
                'created_by' => session('user_id'),
                'order_number' => $this->numbers->next('sales_order', (int) $quote->store_id),
                'order_date' => now()->toDateString(),
                'due_date' => $dueDate,
                'subtotal' => $quote->subtotal,
                'discount_total' => $quote->discount_total,
                'tax_total' => $quote->tax_total,
                'grand_total' => $quote->grand_total,
                'status' => 'confirmed',
                'notes' => $quote->notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach (DB::table('quotation_items')->where('quotation_id', $quotationId)->get() as $item) {
                DB::table('sales_order_items')->insert([
                    'sales_order_id' => $orderId,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'line_total' => $item->line_total,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('quotations')->where('id', $quotationId)->update(['status' => 'accepted', 'updated_at' => now()]);
            $this->audit->record('converted', 'sales', 'sales_order', $orderId, null, ['quotation_id' => $quotationId]);

            return $orderId;
        });
    }

    /** @return array{array<int, array<string, mixed>>, float, float, float} */
    private function prepareItems(array $items, float $requestedDiscount): array
    {
        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'At least one document item is required.']);
        }
        $prepared = [];
        foreach ($items as $input) {
            $product = DB::table('products')->where('id', $input['product_id'])->where('active', true)->first();
            if (! $product || (float) $input['quantity'] <= 0) {
                throw ValidationException::withMessages(['items' => 'Every item must reference an active product with a positive quantity.']);
            }
            $prepared[] = ['product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku, 'quantity' => $input['quantity'], 'unit_price' => $input['unit_price'] ?? $product->selling_price];
        }
        $subtotal = (float) collect($prepared)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
        $discount = min(max($requestedDiscount, 0), $subtotal);
        foreach ($prepared as &$item) {
            $lineSubtotal = (float) $item['quantity'] * (float) $item['unit_price'];
            $item['discount_amount'] = $subtotal > 0 ? $discount * ($lineSubtotal / $subtotal) : 0;
            $item['line_total'] = $lineSubtotal - $item['discount_amount'];
        }

        return [$prepared, $subtotal, $discount, $subtotal - $discount];
    }
}
