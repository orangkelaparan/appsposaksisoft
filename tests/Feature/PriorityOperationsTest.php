<?php

namespace Tests\Feature;

use App\Services\StockCountService;
use App\Services\StockTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PriorityOperationsTest extends TestCase
{
    use RefreshDatabase;

    private int $userId;

    private int $sourceWarehouseId;

    private int $destinationWarehouseId;

    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $companyId = DB::table('companies')->insertGetId(['name' => 'Test Company', 'code' => 'TST', 'currency' => 'IDR', 'timezone' => 'Asia/Jakarta', 'created_at' => now(), 'updated_at' => now()]);
        $sourceStore = DB::table('stores')->insertGetId(['company_id' => $companyId, 'code' => 'TST-1', 'name' => 'Source Outlet', 'invoice_prefix' => 'SRC', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $destinationStore = DB::table('stores')->insertGetId(['company_id' => $companyId, 'code' => 'TST-2', 'name' => 'Destination Outlet', 'invoice_prefix' => 'DST', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->sourceWarehouseId = DB::table('warehouses')->insertGetId(['store_id' => $sourceStore, 'code' => 'WH-SRC', 'name' => 'Source warehouse', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->destinationWarehouseId = DB::table('warehouses')->insertGetId(['store_id' => $destinationStore, 'code' => 'WH-DST', 'name' => 'Destination warehouse', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->userId = DB::table('users')->insertGetId(['name' => 'Test Operator', 'email' => 'operator@example.test', 'username' => 'operator', 'password' => bcrypt('password'), 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $unitId = DB::table('units')->insertGetId(['name' => 'Piece', 'symbol' => 'pcs', 'conversion_factor' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->productId = DB::table('products')->insertGetId(['unit_id' => $unitId, 'sku' => 'TST-001', 'name' => 'Test Product', 'slug' => 'test-product', 'purchase_cost' => 10000, 'selling_price' => 15000, 'low_stock_threshold' => 1, 'track_inventory' => true, 'allow_negative_inventory' => false, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('inventory_stocks')->insert(['warehouse_id' => $this->sourceWarehouseId, 'product_id' => $this->productId, 'quantity' => 10, 'average_cost' => 10000, 'created_at' => now(), 'updated_at' => now()]);
        session(['user_id' => $this->userId]);
    }

    public function test_stock_transfer_shipment_and_receipt_create_balanced_ledger_movements(): void
    {
        $service = app(StockTransferService::class);
        $transferId = $service->create(['source_warehouse_id' => $this->sourceWarehouseId, 'destination_warehouse_id' => $this->destinationWarehouseId, 'items' => [['product_id' => $this->productId, 'quantity' => 4]]]);
        $service->approve($transferId);
        $service->ship($transferId);
        $service->receive($transferId);

        $this->assertDatabaseHas('stock_transfers', ['id' => $transferId, 'status' => 'received']);
        $this->assertDatabaseHas('inventory_stocks', ['warehouse_id' => $this->sourceWarehouseId, 'product_id' => $this->productId, 'quantity' => 6]);
        $this->assertDatabaseHas('inventory_stocks', ['warehouse_id' => $this->destinationWarehouseId, 'product_id' => $this->productId, 'quantity' => 4]);
        $this->assertDatabaseCount('inventory_ledgers', 2);
    }

    public function test_stocktake_approval_posts_the_physical_variance_to_inventory(): void
    {
        $service = app(StockCountService::class);
        $countId = $service->create($this->sourceWarehouseId, 'Cycle count');
        $service->record($countId, [$this->productId => 7]);
        $service->approve($countId);

        $this->assertDatabaseHas('stock_counts', ['id' => $countId, 'status' => 'approved']);
        $this->assertDatabaseHas('stock_count_items', ['stock_count_id' => $countId, 'product_id' => $this->productId, 'counted_quantity' => 7, 'variance_quantity' => -3]);
        $this->assertDatabaseHas('inventory_stocks', ['warehouse_id' => $this->sourceWarehouseId, 'product_id' => $this->productId, 'quantity' => 7]);
        $this->assertDatabaseHas('inventory_ledgers', ['movement_type' => 'stock_count', 'quantity' => -3]);
    }
}
