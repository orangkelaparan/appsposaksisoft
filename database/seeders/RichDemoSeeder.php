<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RichDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = (int) DB::table('users')->orderBy('id')->value('id');
        $centralStoreId = (int) DB::table('stores')->where('code', 'JKT-01')->value('id');
        $bandungStoreId = (int) DB::table('stores')->where('code', 'BDG-01')->value('id');
        $centralWarehouseId = (int) DB::table('warehouses')->where('code', 'WH-JKT-01')->value('id');
        $bandungWarehouseId = (int) DB::table('warehouses')->where('code', 'WH-BDG-01')->value('id');
        $registers = DB::table('registers')->orderBy('id')->pluck('id')->all();

        $this->seedReferenceData($now);
        $staffIds = $this->seedStaff($centralStoreId, $bandungStoreId, $now);
        $this->seedContacts($now);
        $this->seedAdditionalProducts($adminId, $centralWarehouseId, $now);

        $productRows = DB::table('products')->select('id', 'name', 'sku', 'purchase_cost', 'selling_price')->orderBy('id')->get();
        $productIds = $productRows->pluck('id')->all();
        $customerIds = DB::table('customers')->orderBy('id')->pluck('id')->all();
        $supplierIds = DB::table('suppliers')->orderBy('id')->pluck('id')->all();
        $cashierIds = array_values(array_unique(array_merge([$adminId], $staffIds)));

        $this->seedVariants($productRows, $now);
        $this->seedPurchases($productRows, $supplierIds, $adminId, $centralStoreId, $centralWarehouseId, $now);
        $sessions = $this->seedRegisterSessions($registers, $cashierIds, $now);
        $sales = $this->seedSales($productRows, $customerIds, $cashierIds, $sessions, $centralStoreId, $centralWarehouseId, $now);
        $this->seedReturns($sales, $centralWarehouseId, $adminId, $now);
        $this->seedExpensesAndCash($sessions, $centralStoreId, $cashierIds, $now);
        $this->seedPriorityOperations($productRows, $customerIds, $adminId, $centralStoreId, $bandungStoreId, $centralWarehouseId, $bandungWarehouseId, $now);
        $this->seedAuditHistory($cashierIds, $productIds, $now);
    }

    private function seedReferenceData(Carbon $now): void
    {
        foreach ([
            ['Dairy & Chilled', 'dairy-chilled', 'Susu, yoghurt, keju, dan produk dingin.'],
            ['Baby Care', 'baby-care', 'Keperluan bayi dan anak.'],
            ['Pet Supplies', 'pet-supplies', 'Makanan dan kebutuhan hewan peliharaan.'],
            ['Automotive', 'automotive', 'Aksesori dan perawatan kendaraan.'],
        ] as $i => [$name, $slug, $description]) {
            DB::table('categories')->updateOrInsert(['slug' => $slug], ['name' => $name, 'description' => $description, 'sort_order' => 20 + $i, 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        foreach (['Rasa Kita', 'Bumi Sehat', 'Lestari', 'Kirana', 'Natura'] as $brand) {
            DB::table('brands')->updateOrInsert(['slug' => Str::slug($brand)], ['name' => $brand, 'website' => 'https://example.test/'.Str::slug($brand), 'description' => 'Merek contoh untuk data demonstrasi AksiSoft.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        foreach ([['PPN 11%', 11, false], ['Bebas PPN', 0, false], ['Harga termasuk PPN 11%', 11, true]] as [$name, $rate, $inclusive]) {
            DB::table('taxes')->updateOrInsert(['name' => $name], ['rate' => $rate, 'inclusive' => $inclusive, 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        foreach ([['Corporate', 7.5], ['Member', 2.5], ['Employee', 12.5]] as [$name, $discount]) {
            DB::table('customer_groups')->updateOrInsert(['name' => $name], ['default_discount' => $discount, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    private function seedStaff(int $centralStoreId, int $bandungStoreId, Carbon $now): array
    {
        $roles = DB::table('roles')->pluck('id', 'slug');
        $definitions = [
            ['Siti Rahmawati', 'siti.rahmawati', 'cashier', $centralStoreId],
            ['Rizky Pratama', 'rizky.pratama', 'cashier', $bandungStoreId],
            ['Dewi Anggraini', 'dewi.anggraini', 'manager', $centralStoreId],
            ['Arif Nugroho', 'arif.nugroho', 'inventory-staff', $centralStoreId],
            ['Nadia Putri', 'nadia.putri', 'purchasing-staff', $centralStoreId],
            ['Fajar Maulana', 'fajar.maulana', 'accountant-finance', $centralStoreId],
            ['Lina Kurnia', 'lina.kurnia', 'auditor', $centralStoreId],
        ];
        $ids = [];
        foreach ($definitions as [$name, $username, $role, $storeId]) {
            $id = DB::table('users')->insertGetId(['name' => $name, 'email' => $username.'@aksisoft.web.id', 'username' => $username, 'password' => Hash::make(Str::password(32)), 'status' => 'active', 'email_verified_at' => $now, 'last_login_at' => $now->copy()->subDays(random_int(1, 18)), 'created_at' => $now, 'updated_at' => $now]);
            DB::table('user_roles')->insert(['user_id' => $id, 'role_id' => $roles[$role]]);
            DB::table('user_stores')->insert(['user_id' => $id, 'store_id' => $storeId, 'is_primary' => true]);
            $ids[] = $id;
        }

        return $ids;
    }

    private function seedContacts(Carbon $now): void
    {
        $supplierBases = ['PT Bumi Jaya Abadi', 'CV Sukses Bersama', 'PT Sentosa Pangan', 'UD Cahaya Niaga', 'PT Mitra Sejahtera', 'CV Karya Nusantara', 'PT Arta Mandiri', 'UD Berkah Makmur', 'PT Global Retailindo', 'CV Cipta Selaras', 'PT Bintang Kencana', 'UD Maju Lancar'];
        foreach ($supplierBases as $i => $company) {
            $sequence = $i + 4;
            DB::table('suppliers')->insert(['code' => 'SUP-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT), 'company_name' => $company, 'contact_person' => ['Yusuf', 'Melati', 'Rangga', 'Tasya'][$i % 4].' '.['Saputra', 'Permata', 'Kusuma'][$i % 3], 'phone' => '021-88'.str_pad((string) ($i + 100), 5, '0', STR_PAD_LEFT), 'email' => 'sales'.($i + 1).'@supplier.example.test', 'address' => 'Kawasan Niaga Blok '.chr(65 + ($i % 6)).', Jakarta, Indonesia', 'tax_number' => '01.'.str_pad((string) ($i + 100), 3, '0', STR_PAD_LEFT).'.'.str_pad((string) ($i + 200), 3, '0', STR_PAD_LEFT).'.0-001.000', 'payment_terms' => $i % 3 === 0 ? 'Net 45' : 'Net 30', 'credit_limit' => 20000000 + ($i * 2500000), 'notes' => 'Pemasok contoh dengan pengiriman terjadwal mingguan.', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        }

        $firstNames = ['Bagus', 'Intan', 'Rafi', 'Nabila', 'Yoga', 'Aulia', 'Dimas', 'Salma', 'Rani', 'Fikri', 'Anisa', 'Galih', 'Vina', 'Raka', 'Salsa', 'Bima', 'Nisa', 'Hendra', 'Ayu', 'Danu', 'Wulan', 'Tio', 'Mira', 'Bayu', 'Sinta', 'Adit', 'Rina', 'Eko', 'Laras', 'Rio', 'Nanda', 'Farah'];
        $lastNames = ['Saputra', 'Pranoto', 'Wijaya', 'Nugraha', 'Kusuma', 'Permata', 'Lestari', 'Anggraini'];
        $groups = DB::table('customer_groups')->pluck('id')->all();
        foreach ($firstNames as $i => $firstName) {
            $number = $i + 5;
            $name = $firstName.' '.$lastNames[$i % count($lastNames)];
            DB::table('customers')->insert(['customer_group_id' => $groups[$i % count($groups)], 'code' => 'CUS-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT), 'name' => $name, 'phone' => '08'.str_pad((string) (120000000 + $i), 10, '0', STR_PAD_LEFT), 'email' => Str::slug($name).$number.'@customer.example.test', 'address' => 'Jl. '.['Melati', 'Kenanga', 'Mawar', 'Anggrek'][$i % 4].' No. '.($i + 3).', '.['Jakarta', 'Bandung', 'Bekasi', 'Tangerang'][$i % 4], 'credit_limit' => ($i % 4) * 500000, 'outstanding_balance' => $i % 5 === 0 ? 125000 : 0, 'points' => random_int(20, 2200), 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    private function seedAdditionalProducts(int $adminId, int $warehouseId, Carbon $now): void
    {
        $categories = DB::table('categories')->pluck('id')->all();
        $brands = DB::table('brands')->pluck('id')->all();
        $units = DB::table('units')->pluck('id')->all();
        $suppliers = DB::table('suppliers')->pluck('id')->all();
        $taxes = DB::table('taxes')->pluck('id')->all();
        $families = ['Kopi Arabika 200g', 'Susu UHT Cokelat 1L', 'Sereal Gandum 350g', 'Minyak Goreng 2L', 'Teh Melati 25 Sachet', 'Biskuit Keju 150g', 'Madu Murni 250ml', 'Sabun Cuci Tangan 250ml', 'Pembersih Lantai 1L', 'Pasta Gigi Herbal 120g', 'Sampo Anti Ketombe 340ml', 'Popok Bayi M 24pcs', 'Tisu Basah 50pcs', 'Pakan Kucing Adult 1kg', 'Pasir Kucing 5L', 'Cairan Wiper 500ml', 'Pengharum Mobil 8ml', 'Baterai AAA 4pcs', 'Lampu LED 12W', 'Kertas Thermal 80mm', 'Map Plastik A4', 'Spidol Permanen Biru', 'Yoghurt Stroberi 125ml', 'Keju Cheddar 170g', 'Sosis Sapi 500g', 'Roti Cokelat Isi 60g', 'Buah Apel Fuji 1kg', 'Pisang Cavendish 1kg', 'Sayur Bayam 250g', 'Telur Omega 10pcs', 'Air Soda Lemon 330ml', 'Jus Mangga 1L', 'Keripik Singkong Balado 120g', 'Kacang Panggang 150g', 'Bubur Bayi Beras Merah 120g', 'Minyak Telon 60ml', 'Vitamin C 1000mg 10 Tablet', 'Masker Wajah Aloe 25ml', 'Deterjen Cair 1.2L', 'Pelembut Pakaian 900ml', 'Sabun Cuci Piring 500ml', 'Spons Cuci 3pcs', 'Beras Premium 5kg', 'Gula Aren 500g', 'Garam Himalaya 250g', 'Saus Tomat 340g', 'Kecap Manis 600ml', 'Mie Instan Rendang', 'Bubur Instan Ayam', 'Kopi Susu Botol 250ml', 'Minuman Isotonik 500ml', 'Kacang Kedelai 500g', 'Susu Kental Manis 370g', 'Cokelat Batang 80g', 'Pembersih Kaca 500ml', 'Pewangi Ruangan 250ml', 'Obat Nyamuk Elektrik', 'Bola Lampu 7W', 'Buku Catatan A6', 'Pulpen Gel Hitam'];
        foreach ($families as $i => $name) {
            $sequence = $i + 51;
            $cost = 6500 + (($i % 12) * 2250);
            $qty = 80 + (($i % 16) * 11);
            $productId = DB::table('products')->insertGetId(['category_id' => $categories[$i % count($categories)], 'brand_id' => $brands[$i % count($brands)], 'unit_id' => $units[$i % count($units)], 'tax_id' => $taxes[$i % count($taxes)], 'default_supplier_id' => $suppliers[$i % count($suppliers)], 'sku' => 'AKS-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT), 'barcode' => '899200'.str_pad((string) $sequence, 7, '0', STR_PAD_LEFT), 'name' => $name, 'slug' => Str::slug($name).'-'.$sequence, 'description' => 'Data produk contoh lengkap untuk simulasi operasional retail, stok, pembelian, dan penjualan.', 'image_path' => 'images/products/placeholder.svg', 'product_type' => 'simple', 'purchase_cost' => $cost, 'selling_price' => round($cost * 1.42, -2), 'wholesale_price' => round($cost * 1.28, -2), 'minimum_price' => round($cost * 1.12, -2), 'low_stock_threshold' => 12, 'reorder_level' => 30, 'track_inventory' => true, 'allow_negative_inventory' => false, 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('inventory_stocks')->insert(['warehouse_id' => $warehouseId, 'product_id' => $productId, 'quantity' => $qty, 'average_cost' => $cost, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('inventory_ledgers')->insert(['warehouse_id' => $warehouseId, 'product_id' => $productId, 'user_id' => $adminId, 'movement_type' => 'stock_opening', 'quantity' => $qty, 'before_quantity' => 0, 'after_quantity' => $qty, 'unit_cost' => $cost, 'reference_type' => 'seed', 'reference_id' => 0, 'note' => 'Opening stock demo katalog lengkap', 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    private function seedVariants($products, Carbon $now): void
    {
        foreach ($products->take(24) as $i => $product) {
            $name = $i % 2 === 0 ? 'Value Pack' : 'Family Pack';
            DB::table('product_variants')->insert(['product_id' => $product->id, 'name' => $name, 'sku' => $product->sku.'-'.($i % 2 === 0 ? 'VP' : 'FP'), 'barcode' => '880'.str_pad((string) ($product->id + 100000), 10, '0', STR_PAD_LEFT), 'purchase_cost' => $product->purchase_cost * 1.75, 'selling_price' => $product->selling_price * 1.75, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    private function seedPurchases($products, array $supplierIds, int $adminId, int $storeId, int $warehouseId, Carbon $now): void
    {
        for ($i = 1; $i <= 24; $i++) {
            $orderedAt = $now->copy()->subDays(105 - ($i * 4));
            $poId = DB::table('purchase_orders')->insertGetId(['supplier_id' => $supplierIds[$i % count($supplierIds)], 'store_id' => $storeId, 'warehouse_id' => $warehouseId, 'created_by' => $adminId, 'po_number' => 'PO-2026-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT), 'order_date' => $orderedAt->toDateString(), 'expected_date' => $orderedAt->copy()->addDays(4)->toDateString(), 'subtotal' => 0, 'discount_total' => $i % 4 === 0 ? 25000 : 0, 'tax_total' => 0, 'grand_total' => 0, 'status' => 'completed', 'notes' => 'Pengadaan rutin demonstrasi untuk katalog produk.', 'created_at' => $orderedAt, 'updated_at' => $orderedAt]);
            $subtotal = 0;
            $itemIds = [];
            for ($line = 0; $line < 4; $line++) {
                $product = $products[($i * 5 + $line) % $products->count()];
                $quantity = 20 + (($i + $line) % 7) * 5;
                $lineTotal = $quantity * $product->purchase_cost;
                $subtotal += $lineTotal;
                $itemIds[] = DB::table('purchase_order_items')->insertGetId(['purchase_order_id' => $poId, 'product_id' => $product->id, 'ordered_quantity' => $quantity, 'received_quantity' => $quantity, 'unit_cost' => $product->purchase_cost, 'line_total' => $lineTotal, 'created_at' => $orderedAt, 'updated_at' => $orderedAt]);
            }
            DB::table('purchase_orders')->where('id', $poId)->update(['subtotal' => $subtotal, 'grand_total' => $subtotal - ($i % 4 === 0 ? 25000 : 0), 'updated_at' => $orderedAt]);
            $receiptId = DB::table('purchase_receipts')->insertGetId(['purchase_order_id' => $poId, 'warehouse_id' => $warehouseId, 'received_by' => $adminId, 'receipt_number' => 'GRN-2026-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT), 'received_at' => $orderedAt->copy()->addDays(3), 'notes' => 'Penerimaan lengkap contoh.', 'created_at' => $orderedAt->copy()->addDays(3), 'updated_at' => $orderedAt->copy()->addDays(3)]);
            foreach ($itemIds as $line => $itemId) {
                $item = DB::table('purchase_order_items')->find($itemId);
                DB::table('purchase_receipt_items')->insert(['purchase_receipt_id' => $receiptId, 'purchase_order_item_id' => $itemId, 'quantity' => $item->ordered_quantity, 'unit_cost' => $item->unit_cost, 'created_at' => $orderedAt->copy()->addDays(3), 'updated_at' => $orderedAt->copy()->addDays(3)]);
            }
        }
    }

    private function seedRegisterSessions(array $registers, array $cashierIds, Carbon $now): array
    {
        $sessions = [];
        for ($day = 90; $day >= 1; $day--) {
            $openedAt = $now->copy()->subDays($day)->setTime(8, 0);
            $opening = 350000 + (($day % 5) * 50000);
            $sessionId = DB::table('register_sessions')->insertGetId(['register_id' => $registers[$day % count($registers)], 'user_id' => $cashierIds[$day % count($cashierIds)], 'opening_balance' => $opening, 'expected_cash' => $opening + 650000 + (($day % 6) * 85000), 'actual_cash' => $opening + 650000 + (($day % 6) * 85000) + (($day % 3) - 1) * 1000, 'variance' => (($day % 3) - 1) * 1000, 'opened_at' => $openedAt, 'closed_at' => $openedAt->copy()->addHours(13), 'status' => 'closed', 'created_at' => $openedAt, 'updated_at' => $openedAt->copy()->addHours(13)]);
            $sessions[] = $sessionId;
        }

        return $sessions;
    }

    private function seedSales($products, array $customerIds, array $cashierIds, array $sessions, int $storeId, int $warehouseId, Carbon $now): array
    {
        $sales = [];
        $stock = DB::table('inventory_stocks')->where('warehouse_id', $warehouseId)->pluck('quantity', 'product_id')->all();
        for ($number = 1; $number <= 180; $number++) {
            $soldAt = $now->copy()->subDays(90 - intdiv($number, 2))->setTime(9 + ($number % 9), ($number * 7) % 60);
            $saleId = DB::table('sales')->insertGetId(['store_id' => $storeId, 'warehouse_id' => $warehouseId, 'register_session_id' => $sessions[$number % count($sessions)], 'customer_id' => $number % 4 === 0 ? null : $customerIds[$number % count($customerIds)], 'cashier_id' => $cashierIds[$number % count($cashierIds)], 'invoice_number' => 'INV-2026-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT), 'sold_at' => $soldAt, 'subtotal' => 0, 'discount_total' => 0, 'tax_total' => 0, 'rounding_total' => 0, 'grand_total' => 0, 'paid_total' => 0, 'status' => 'completed', 'notes' => $number % 10 === 0 ? 'Catatan transaksi contoh untuk laporan kasir.' : null, 'created_at' => $soldAt, 'updated_at' => $soldAt]);
            $subtotal = 0;
            $lines = 2 + ($number % 4);
            for ($line = 0; $line < $lines; $line++) {
                $product = $products[($number * 3 + $line * 11) % $products->count()];
                $quantity = 1 + (($number + $line) % 3);
                $unitPrice = $product->selling_price;
                $discount = $line === 0 && $number % 6 === 0 ? round($unitPrice * 0.05, 2) : 0;
                $lineTotal = ($quantity * $unitPrice) - $discount;
                $subtotal += $quantity * $unitPrice;
                DB::table('sale_items')->insert(['sale_id' => $saleId, 'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku, 'quantity' => $quantity, 'unit_price' => $unitPrice, 'unit_cost' => $product->purchase_cost, 'discount_amount' => $discount, 'tax_amount' => 0, 'line_total' => $lineTotal, 'created_at' => $soldAt, 'updated_at' => $soldAt]);
                $before = (float) $stock[$product->id];
                $after = $before - $quantity;
                $stock[$product->id] = $after;
                DB::table('inventory_ledgers')->insert(['warehouse_id' => $warehouseId, 'product_id' => $product->id, 'user_id' => $cashierIds[$number % count($cashierIds)], 'movement_type' => 'sale', 'quantity' => -$quantity, 'before_quantity' => $before, 'after_quantity' => $after, 'unit_cost' => $product->purchase_cost, 'reference_type' => 'sale', 'reference_id' => $saleId, 'note' => 'Penjualan data demo', 'created_at' => $soldAt, 'updated_at' => $soldAt]);
            }
            $discountTotal = DB::table('sale_items')->where('sale_id', $saleId)->sum('discount_amount');
            $grandTotal = $subtotal - $discountTotal;
            $methods = ['cash', 'qris', 'card', 'bank_transfer', 'e_wallet'];
            $method = $methods[$number % count($methods)];
            $tendered = $method === 'cash' ? ceil($grandTotal / 5000) * 5000 : $grandTotal;
            DB::table('payments')->insert(['sale_id' => $saleId, 'user_id' => $cashierIds[$number % count($cashierIds)], 'method' => $method, 'amount' => $grandTotal, 'tendered_amount' => $tendered, 'change_amount' => max(0, $tendered - $grandTotal), 'reference' => $method === 'cash' ? null : strtoupper($method).'-'.str_pad((string) $number, 8, '0', STR_PAD_LEFT), 'paid_at' => $soldAt, 'created_at' => $soldAt, 'updated_at' => $soldAt]);
            DB::table('sales')->where('id', $saleId)->update(['subtotal' => $subtotal, 'discount_total' => $discountTotal, 'grand_total' => $grandTotal, 'paid_total' => $grandTotal, 'updated_at' => $soldAt]);
            $sales[] = $saleId;
        }
        foreach ($stock as $productId => $quantity) {
            DB::table('inventory_stocks')->where(['warehouse_id' => $warehouseId, 'product_id' => $productId])->update(['quantity' => $quantity, 'updated_at' => $now]);
        }

        return $sales;
    }

    private function seedReturns(array $sales, int $warehouseId, int $adminId, Carbon $now): void
    {
        foreach ([18, 61, 123, 160] as $index) {
            $saleId = $sales[$index];
            $item = DB::table('sale_items')->where('sale_id', $saleId)->orderBy('id')->first();
            $sale = DB::table('sales')->find($saleId);
            $refund = min((float) $item->line_total, (float) $item->unit_price);
            $createdAt = Carbon::parse($sale->sold_at)->addDays(1);
            $returnId = DB::table('sale_returns')->insertGetId(['sale_id' => $saleId, 'warehouse_id' => $warehouseId, 'created_by' => $adminId, 'return_number' => 'RET-2026-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT), 'refund_total' => $refund, 'refund_method' => 'cash', 'status' => 'completed', 'reason' => 'Produk kemasan rusak pada data demonstrasi.', 'created_at' => $createdAt, 'updated_at' => $createdAt]);
            DB::table('sale_return_items')->insert(['sale_return_id' => $returnId, 'sale_item_id' => $item->id, 'product_id' => $item->product_id, 'quantity' => 1, 'refund_amount' => $refund, 'created_at' => $createdAt, 'updated_at' => $createdAt]);
        }
    }

    private function seedExpensesAndCash(array $sessions, int $storeId, array $cashierIds, Carbon $now): void
    {
        $categories = ['Utilities', 'Store Supplies', 'Transport', 'Maintenance', 'Employee Meals', 'Marketing'];
        for ($i = 1; $i <= 36; $i++) {
            $date = $now->copy()->subDays($i * 2);
            $sessionId = $sessions[$i % count($sessions)];
            DB::table('expenses')->insert(['store_id' => $storeId, 'register_session_id' => $sessionId, 'created_by' => $cashierIds[$i % count($cashierIds)], 'expense_number' => 'EXP-2026-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT), 'category' => $categories[$i % count($categories)], 'amount' => 25000 + (($i % 9) * 15000), 'payment_method' => $i % 2 === 0 ? 'cash' : 'bank_transfer', 'expense_date' => $date->toDateString(), 'description' => 'Biaya operasional contoh '.strtolower($categories[$i % count($categories)]).'.', 'status' => 'approved', 'created_at' => $date, 'updated_at' => $date]);
        }
        for ($i = 1; $i <= 60; $i++) {
            $at = $now->copy()->subDays($i);
            DB::table('cash_movements')->insert(['register_session_id' => $sessions[$i % count($sessions)], 'user_id' => $cashierIds[$i % count($cashierIds)], 'type' => $i % 2 === 0 ? 'cash_in' : 'cash_out', 'amount' => 20000 + (($i % 8) * 10000), 'reason' => $i % 2 === 0 ? 'Tambahan uang kecil kasir demo' : 'Pembayaran operasional demo', 'created_at' => $at, 'updated_at' => $at]);
        }
    }

    private function seedPriorityOperations($products, array $customerIds, int $adminId, int $centralStoreId, int $bandungStoreId, int $centralWarehouseId, int $bandungWarehouseId, Carbon $now): void
    {
        foreach ([
            ['TRF-2026-000001', 'received', 0, 0, 18, 2],
            ['TRF-2026-000002', 'shipped', 1, 1, 12, 5],
            ['TRF-2026-000003', 'draft', 2, 2, 8, 8],
        ] as [$number, $status, $productIndex, $daysAgo, $quantity, $received]) {
            $at = $now->copy()->subDays(12 - $daysAgo);
            $transferId = DB::table('stock_transfers')->insertGetId(['source_warehouse_id' => $centralWarehouseId, 'destination_warehouse_id' => $bandungWarehouseId, 'created_by' => $adminId, 'approved_by' => $status !== 'draft' ? $adminId : null, 'shipped_by' => in_array($status, ['shipped', 'received'], true) ? $adminId : null, 'received_by' => $status === 'received' ? $adminId : null, 'transfer_number' => $number, 'status' => $status, 'approved_at' => $status !== 'draft' ? $at : null, 'shipped_at' => in_array($status, ['shipped', 'received'], true) ? $at->copy()->addHour() : null, 'received_at' => $status === 'received' ? $at->copy()->addHours(5) : null, 'notes' => 'Transfer stok antar-outlet untuk simulasi operasional.', 'created_at' => $at, 'updated_at' => $at]);
            $product = $products[$productIndex];
            DB::table('stock_transfer_items')->insert(['stock_transfer_id' => $transferId, 'product_id' => $product->id, 'requested_quantity' => $quantity, 'shipped_quantity' => $status === 'draft' ? 0 : $quantity, 'received_quantity' => $status === 'received' ? $received : 0, 'unit_cost' => $product->purchase_cost, 'created_at' => $at, 'updated_at' => $at]);
            if (in_array($status, ['shipped', 'received'], true)) {
                $source = DB::table('inventory_stocks')->where('warehouse_id', $centralWarehouseId)->where('product_id', $product->id)->first();
                if ($source) {
                    DB::table('inventory_stocks')->where('id', $source->id)->update(['quantity' => max(0, (float) $source->quantity - $quantity), 'updated_at' => $at]);
                    DB::table('inventory_ledgers')->insert(['warehouse_id' => $centralWarehouseId, 'product_id' => $product->id, 'user_id' => $adminId, 'movement_type' => 'transfer_out', 'quantity' => -$quantity, 'before_quantity' => $source->quantity, 'after_quantity' => max(0, (float) $source->quantity - $quantity), 'unit_cost' => $product->purchase_cost, 'reference_type' => 'stock_transfer', 'reference_id' => $transferId, 'note' => 'Transfer antar-outlet demo', 'created_at' => $at, 'updated_at' => $at]);
                }
            }
            if ($status === 'received') {
                DB::table('inventory_stocks')->updateOrInsert(['warehouse_id' => $bandungWarehouseId, 'product_id' => $product->id], ['quantity' => $received, 'average_cost' => $product->purchase_cost, 'created_at' => $at, 'updated_at' => $at]);
                DB::table('inventory_ledgers')->insert(['warehouse_id' => $bandungWarehouseId, 'product_id' => $product->id, 'user_id' => $adminId, 'movement_type' => 'transfer_in', 'quantity' => $received, 'before_quantity' => 0, 'after_quantity' => $received, 'unit_cost' => $product->purchase_cost, 'reference_type' => 'stock_transfer', 'reference_id' => $transferId, 'note' => 'Penerimaan transfer antar-outlet demo', 'created_at' => $at, 'updated_at' => $at]);
            }
        }

        foreach ([['CNT-2026-000001', 'approved', 25, 24], ['CNT-2026-000002', 'counted', 27, 31], ['CNT-2026-000003', 'draft', 30, null]] as [$number, $status, $productIndex, $counted]) {
            $at = $now->copy()->subDays($productIndex % 9 + 2);
            $product = $products[$productIndex];
            $system = (float) DB::table('inventory_stocks')->where('warehouse_id', $centralWarehouseId)->where('product_id', $product->id)->value('quantity');
            $countId = DB::table('stock_counts')->insertGetId(['warehouse_id' => $centralWarehouseId, 'created_by' => $adminId, 'approved_by' => $status === 'approved' ? $adminId : null, 'count_number' => $number, 'status' => $status, 'snapshot_at' => $at, 'counted_at' => $counted !== null ? $at->copy()->addHours(2) : null, 'approved_at' => $status === 'approved' ? $at->copy()->addHours(4) : null, 'notes' => 'Stocktake simulasi dengan varians terukur.', 'created_at' => $at, 'updated_at' => $at]);
            DB::table('stock_count_items')->insert(['stock_count_id' => $countId, 'product_id' => $product->id, 'system_quantity' => $system, 'counted_quantity' => $counted, 'variance_quantity' => $counted === null ? null : $counted - $system, 'created_at' => $at, 'updated_at' => $at]);
        }

        foreach ([['QTN-2026-000001', 'accepted', 3, 6], ['QTN-2026-000002', 'draft', 4, 8], ['QTN-2026-000003', 'draft', 7, 3]] as $i => [$number, $status, $productIndex, $quantity]) {
            $at = $now->copy()->subDays(9 - $i);
            $product = $products[$productIndex];
            $total = $quantity * $product->selling_price;
            $quoteId = DB::table('quotations')->insertGetId(['store_id' => $centralStoreId, 'warehouse_id' => $centralWarehouseId, 'customer_id' => $customerIds[$i % count($customerIds)], 'created_by' => $adminId, 'quote_number' => $number, 'quote_date' => $at->toDateString(), 'valid_until' => $at->copy()->addDays(14)->toDateString(), 'subtotal' => $total, 'discount_total' => 0, 'tax_total' => 0, 'grand_total' => $total, 'status' => $status, 'notes' => 'Penawaran produk untuk pelanggan demo.', 'created_at' => $at, 'updated_at' => $at]);
            DB::table('quotation_items')->insert(['quotation_id' => $quoteId, 'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku, 'quantity' => $quantity, 'unit_price' => $product->selling_price, 'discount_amount' => 0, 'line_total' => $total, 'created_at' => $at, 'updated_at' => $at]);
            if ($i === 0) {
                $orderId = DB::table('sales_orders')->insertGetId(['store_id' => $centralStoreId, 'warehouse_id' => $centralWarehouseId, 'customer_id' => $customerIds[0], 'quotation_id' => $quoteId, 'created_by' => $adminId, 'order_number' => 'SO-2026-000001', 'order_date' => $at->copy()->addDay()->toDateString(), 'due_date' => $at->copy()->addDays(7)->toDateString(), 'subtotal' => $total, 'discount_total' => 0, 'tax_total' => 0, 'grand_total' => $total, 'status' => 'confirmed', 'notes' => 'Sales order hasil konversi quotation demo.', 'created_at' => $at, 'updated_at' => $at]);
                DB::table('sales_order_items')->insert(['sales_order_id' => $orderId, 'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku, 'quantity' => $quantity, 'fulfilled_quantity' => 0, 'unit_price' => $product->selling_price, 'discount_amount' => 0, 'line_total' => $total, 'created_at' => $at, 'updated_at' => $at]);
            }
        }
    }

    private function seedAuditHistory(array $userIds, array $productIds, Carbon $now): void
    {
        $actions = ['created', 'updated', 'viewed', 'approved', 'received', 'completed', 'exported'];
        $modules = ['products', 'inventory', 'purchases', 'sales', 'customers', 'reports'];
        for ($i = 1; $i <= 240; $i++) {
            $at = $now->copy()->subMinutes($i * 90);
            $module = $modules[$i % count($modules)];
            DB::table('audit_logs')->insert(['user_id' => $userIds[$i % count($userIds)], 'action' => $actions[$i % count($actions)], 'module' => $module, 'record_type' => $module === 'products' ? 'product' : $module, 'record_id' => $productIds[$i % count($productIds)], 'old_values' => json_encode(['status' => 'draft']), 'new_values' => json_encode(['status' => 'completed', 'source' => 'demo_seed']), 'ip_address' => '127.0.0.1', 'user_agent' => 'AksiSoft demo data seeder', 'created_at' => $at, 'updated_at' => $at]);
        }
    }
}
