<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PriorityOperationsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('stock_transfers')->exists()) {
            return;
        }

        $now = now();
        $adminId = DB::table('users')->orderBy('id')->value('id');
        $sourceWarehouse = DB::table('warehouses')->where('code', 'WH-JKT-01')->first();
        $destinationWarehouse = DB::table('warehouses')->where('code', 'WH-BDG-01')->first();
        $products = DB::table('products')->orderBy('id')->limit(8)->get();
        $customerIds = DB::table('customers')->orderBy('id')->limit(3)->pluck('id')->all();
        if (! $sourceWarehouse || ! $destinationWarehouse || $products->count() < 3 || empty($customerIds)) {
            return;
        }

        foreach ([['TRF-DEMO-001', 'draft', 0, 8], ['TRF-DEMO-002', 'approved', 1, 12], ['TRF-DEMO-003', 'shipped', 2, 6]] as $i => [$number, $status, $productIndex, $quantity]) {
            $at = $now->copy()->subDays(5 - $i);
            $transferId = DB::table('stock_transfers')->insertGetId(['source_warehouse_id' => $sourceWarehouse->id, 'destination_warehouse_id' => $destinationWarehouse->id, 'created_by' => $adminId, 'approved_by' => $status !== 'draft' ? $adminId : null, 'shipped_by' => $status === 'shipped' ? $adminId : null, 'transfer_number' => $number, 'status' => $status, 'approved_at' => $status !== 'draft' ? $at->copy()->addHour() : null, 'shipped_at' => $status === 'shipped' ? $at->copy()->addHours(2) : null, 'notes' => 'Dummy antar-outlet untuk demonstrasi workflow.', 'created_at' => $at, 'updated_at' => $at]);
            $product = $products[$productIndex];
            DB::table('stock_transfer_items')->insert(['stock_transfer_id' => $transferId, 'product_id' => $product->id, 'requested_quantity' => $quantity, 'shipped_quantity' => $status === 'shipped' ? $quantity : 0, 'received_quantity' => 0, 'unit_cost' => $product->purchase_cost, 'created_at' => $at, 'updated_at' => $at]);
        }

        foreach ([['CNT-DEMO-001', 'draft', 3, null], ['CNT-DEMO-002', 'counted', 4, 19]] as $i => [$number, $status, $productIndex, $counted]) {
            $at = $now->copy()->subDays(3 - $i);
            $product = $products[$productIndex];
            $system = (float) (DB::table('inventory_stocks')->where('warehouse_id', $sourceWarehouse->id)->where('product_id', $product->id)->value('quantity') ?? 0);
            $countId = DB::table('stock_counts')->insertGetId(['warehouse_id' => $sourceWarehouse->id, 'created_by' => $adminId, 'count_number' => $number, 'status' => $status, 'snapshot_at' => $at, 'counted_at' => $counted === null ? null : $at->copy()->addHour(), 'notes' => 'Dummy stocktake dengan variasi fisik.', 'created_at' => $at, 'updated_at' => $at]);
            DB::table('stock_count_items')->insert(['stock_count_id' => $countId, 'product_id' => $product->id, 'system_quantity' => $system, 'counted_quantity' => $counted, 'variance_quantity' => $counted === null ? null : $counted - $system, 'created_at' => $at, 'updated_at' => $at]);
        }

        foreach ([['QTN-DEMO-001', 'accepted', 5, 4], ['QTN-DEMO-002', 'draft', 6, 7], ['QTN-DEMO-003', 'draft', 7, 3]] as $i => [$number, $status, $productIndex, $quantity]) {
            $at = $now->copy()->subDays(2 - $i);
            $product = $products[$productIndex];
            $total = $quantity * $product->selling_price;
            $quoteId = DB::table('quotations')->insertGetId(['store_id' => $sourceWarehouse->store_id, 'warehouse_id' => $sourceWarehouse->id, 'customer_id' => $customerIds[$i], 'created_by' => $adminId, 'quote_number' => $number, 'quote_date' => $at->toDateString(), 'valid_until' => $at->copy()->addDays(14)->toDateString(), 'subtotal' => $total, 'discount_total' => 0, 'tax_total' => 0, 'grand_total' => $total, 'status' => $status, 'notes' => 'Dummy quotation untuk pelanggan demo.', 'created_at' => $at, 'updated_at' => $at]);
            DB::table('quotation_items')->insert(['quotation_id' => $quoteId, 'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku, 'quantity' => $quantity, 'unit_price' => $product->selling_price, 'discount_amount' => 0, 'line_total' => $total, 'created_at' => $at, 'updated_at' => $at]);
            if ($i === 0) {
                $orderId = DB::table('sales_orders')->insertGetId(['store_id' => $sourceWarehouse->store_id, 'warehouse_id' => $sourceWarehouse->id, 'customer_id' => $customerIds[$i], 'quotation_id' => $quoteId, 'created_by' => $adminId, 'order_number' => 'SO-DEMO-001', 'order_date' => $at->copy()->addDay()->toDateString(), 'due_date' => $at->copy()->addDays(7)->toDateString(), 'subtotal' => $total, 'discount_total' => 0, 'tax_total' => 0, 'grand_total' => $total, 'status' => 'confirmed', 'notes' => 'Dummy sales order hasil quotation.', 'created_at' => $at, 'updated_at' => $at]);
                DB::table('sales_order_items')->insert(['sales_order_id' => $orderId, 'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku, 'quantity' => $quantity, 'fulfilled_quantity' => 0, 'unit_price' => $product->selling_price, 'discount_amount' => 0, 'line_total' => $total, 'created_at' => $at, 'updated_at' => $at]);
            }
        }
    }
}
