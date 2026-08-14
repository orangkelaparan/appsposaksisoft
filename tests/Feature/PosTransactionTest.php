<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PosTransactionTest extends TestCase
{
    use RefreshDatabase;

    private array $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $now = now();
        $company = DB::table('companies')->insertGetId(['name' => 'Test Retail', 'code' => 'TEST', 'currency' => 'IDR', 'timezone' => 'Asia/Jakarta', 'created_at' => $now, 'updated_at' => $now]);
        $store = DB::table('stores')->insertGetId(['company_id' => $company, 'code' => 'TEST-01', 'name' => 'Test Store', 'invoice_prefix' => 'TST', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $warehouse = DB::table('warehouses')->insertGetId(['store_id' => $store, 'code' => 'TEST-WH', 'name' => 'Test Warehouse', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $user = DB::table('users')->insertGetId(['name' => 'Test Admin', 'email' => 'admin@test.local', 'username' => 'admin', 'password' => Hash::make('secret'), 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $product = DB::table('products')->insertGetId(['sku' => 'TEST-001', 'barcode' => '8991000000001', 'name' => 'Test Beverage', 'slug' => 'test-beverage', 'purchase_cost' => 10000, 'selling_price' => 15000, 'low_stock_threshold' => 2, 'track_inventory' => true, 'allow_negative_inventory' => false, 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('inventory_stocks')->insert(['warehouse_id' => $warehouse, 'product_id' => $product, 'quantity' => 10, 'average_cost' => 10000, 'created_at' => $now, 'updated_at' => $now]);
        $this->context = compact('store', 'warehouse', 'user', 'product');
    }

    public function test_completed_sale_creates_financial_and_inventory_records(): void
    {
        $response = $this->withSession(['user_id' => $this->context['user'], 'user_name' => 'Test Admin', 'user_role' => 'Super Administrator'])
            ->postJson(route('api.sales.checkout'), ['store_id' => $this->context['store'], 'warehouse_id' => $this->context['warehouse'], 'payment_method' => 'cash', 'tendered_amount' => 40000, 'items' => [['product_id' => $this->context['product'], 'quantity' => 2, 'unit_price' => 15000]]]);

        $response->assertOk()->assertJsonPath('message', 'Sale completed successfully.');
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('inventory_stocks', ['warehouse_id' => $this->context['warehouse'], 'product_id' => $this->context['product'], 'quantity' => 8]);
        $this->assertDatabaseHas('inventory_ledgers', ['movement_type' => 'sale', 'quantity' => -2]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'completed', 'module' => 'sales']);
        $this->assertDatabaseHas('sales', ['subtotal' => 30000, 'grand_total' => 30000, 'paid_total' => 30000]);
    }

    public function test_sale_rolls_back_when_stock_is_insufficient(): void
    {
        $response = $this->withSession(['user_id' => $this->context['user'], 'user_name' => 'Test Admin', 'user_role' => 'Super Administrator'])
            ->postJson(route('api.sales.checkout'), ['store_id' => $this->context['store'], 'warehouse_id' => $this->context['warehouse'], 'payment_method' => 'cash', 'tendered_amount' => 300000, 'items' => [['product_id' => $this->context['product'], 'quantity' => 99, 'unit_price' => 15000]]]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseHas('inventory_stocks', ['warehouse_id' => $this->context['warehouse'], 'product_id' => $this->context['product'], 'quantity' => 10]);
        $this->assertDatabaseCount('inventory_ledgers', 0);
    }
}
