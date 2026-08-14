<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');
        if (! $password && app()->environment('testing')) {
            $password = 'testing-password';
        }
        if (! $password) {
            throw new RuntimeException('Set ADMIN_PASSWORD in the environment before running the database seeder.');
        }

        $now = now();
        $companyId = DB::table('companies')->insertGetId(['name' => 'AksiSoft Retail Indonesia', 'code' => 'AKSI-01', 'currency' => 'IDR', 'timezone' => 'Asia/Jakarta', 'address' => 'Jl. Jenderal Sudirman No. 88, Jakarta', 'phone' => '+62 21 555 0100', 'email' => 'hello@aksisoft.test', 'created_at' => $now, 'updated_at' => $now]);
        $centralStore = DB::table('stores')->insertGetId(['company_id' => $companyId, 'code' => 'JKT-01', 'name' => 'AksiSoft Flagship Jakarta', 'address' => 'Jl. Jenderal Sudirman No. 88, Jakarta', 'phone' => '+62 21 555 0100', 'invoice_prefix' => 'JKT', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $bandungStore = DB::table('stores')->insertGetId(['company_id' => $companyId, 'code' => 'BDG-01', 'name' => 'AksiSoft Bandung Outlet', 'address' => 'Jl. Asia Afrika No. 25, Bandung', 'phone' => '+62 22 555 0200', 'invoice_prefix' => 'BDG', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $centralWarehouse = DB::table('warehouses')->insertGetId(['store_id' => $centralStore, 'code' => 'WH-JKT-01', 'name' => 'Jakarta Main Warehouse', 'address' => 'Jl. Jenderal Sudirman No. 88, Jakarta', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('warehouses')->insert(['store_id' => $bandungStore, 'code' => 'WH-BDG-01', 'name' => 'Bandung Outlet Warehouse', 'address' => 'Jl. Asia Afrika No. 25, Bandung', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        foreach ([['POS-JKT-01', 'Front Counter 01'], ['POS-JKT-02', 'Front Counter 02'], ['POS-BDG-01', 'Bandung Counter 01']] as $i => $register) {
            DB::table('registers')->insert(['store_id' => $i === 2 ? $bandungStore : $centralStore, 'warehouse_id' => $i === 2 ? 2 : $centralWarehouse, 'code' => $register[0], 'name' => $register[1], 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        $permissions = ['dashboard.view', 'products.view', 'products.create', 'products.update', 'products.delete', 'products.import', 'products.export', 'inventory.view', 'inventory.adjust', 'inventory.transfer', 'inventory.count', 'sales.view', 'sales.create', 'sales.update', 'sales.cancel', 'sales.return', 'sales.refund', 'sales.discount', 'sales.void', 'payments.create', 'payments.refund', 'purchases.view', 'purchases.create', 'purchases.approve', 'purchases.receive', 'customers.view', 'customers.create', 'customers.update', 'customers.delete', 'suppliers.view', 'suppliers.create', 'reports.view', 'reports.export', 'users.manage', 'roles.manage', 'settings.manage', 'audit.view'];
        foreach ($permissions as $slug) {
            DB::table('permissions')->insert(['name' => Str::headline(str_replace('.', ' ', $slug)), 'slug' => $slug, 'module' => explode('.', $slug)[0], 'created_at' => $now, 'updated_at' => $now]);
        }
        $roleMap = ['Super Administrator' => 'Full system access and system-critical controls.', 'Business Owner' => 'Commercial performance, reports, sales, and purchasing oversight.', 'Manager' => 'Store operations, stock, purchasing, and returns.', 'Cashier' => 'POS, customer selection, payment, and own register operations.', 'Inventory Staff' => 'Product and warehouse inventory operations.', 'Purchasing Staff' => 'Supplier, purchase order, and receiving operations.', 'Accountant / Finance' => 'Financial reports, payments, expenses, and reconciliation.', 'Auditor' => 'Read-only review of operational and audit records.'];
        foreach ($roleMap as $name => $description) {
            $roleId = DB::table('roles')->insertGetId(['name' => $name, 'slug' => Str::slug($name), 'description' => $description, 'created_at' => $now, 'updated_at' => $now]);
            if ($name === 'Super Administrator') {
                foreach (DB::table('permissions')->pluck('id') as $permissionId) {
                    DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
                }
            }
        }
        $adminId = DB::table('users')->insertGetId(['name' => env('ADMIN_NAME', 'AksiSoft Administrator'), 'email' => env('ADMIN_EMAIL', 'admin@aksisoft.test'), 'username' => env('ADMIN_USERNAME', 'admin'), 'password' => Hash::make($password), 'status' => 'active', 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('user_roles')->insert(['user_id' => $adminId, 'role_id' => DB::table('roles')->where('slug', 'super-administrator')->value('id')]);
        DB::table('user_stores')->insert(['user_id' => $adminId, 'store_id' => $centralStore, 'is_primary' => true]);
        foreach ([['Pieces', 'pcs', 1], ['Bottle', 'btl', 1], ['Pack', 'pack', 1], ['Kilogram', 'kg', 1], ['Carton', 'ctn', 24]] as $unit) {
            DB::table('units')->insert(['name' => $unit[0], 'symbol' => $unit[1], 'conversion_factor' => $unit[2], 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach (['Beverages', 'Snacks', 'Household', 'Personal Care', 'Stationery', 'Fresh Food'] as $category) {
            DB::table('categories')->insert(['name' => $category, 'slug' => Str::slug($category), 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach (['Aksi Choice', 'IndoFresh', 'Nusantara', 'Prima', 'Sari Rasa'] as $brand) {
            DB::table('brands')->insert(['name' => $brand, 'slug' => Str::slug($brand), 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach ([['Walk-in', 0], ['Retail', 0], ['Wholesale', 5], ['VIP', 10]] as $group) {
            DB::table('customer_groups')->insert(['name' => $group[0], 'default_discount' => $group[1], 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach ([['PT Sumber Makmur', 'Budi Santoso', '021-7000111'], ['CV Prima Niaga', 'Rina Kartika', '021-7000222'], ['UD Pangan Jaya', 'Ahmad Fauzi', '022-7000333']] as $i => $supplier) {
            DB::table('suppliers')->insert(['code' => 'SUP-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT), 'company_name' => $supplier[0], 'contact_person' => $supplier[1], 'phone' => $supplier[2], 'email' => Str::slug($supplier[0]).'@example.test', 'address' => 'Jakarta, Indonesia', 'payment_terms' => 'Net 30', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach ([['Citra Lestari', '08123450001'], ['Dimas Pratama', '08123450002'], ['Maya Sari', '08123450003'], ['Andi Wijaya', '08123450004']] as $i => $customer) {
            DB::table('customers')->insert(['customer_group_id' => 2, 'code' => 'CUS-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT), 'name' => $customer[0], 'phone' => $customer[1], 'email' => Str::slug($customer[0]).'@example.test', 'address' => 'Jakarta, Indonesia', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach ([['company_name', 'AksiSoft Retail Indonesia'], ['tax_rate', '0'], ['allow_negative_stock', '0'], ['rounding', 'none'], ['receipt_width', '80mm']] as $setting) {
            DB::table('system_settings')->insert(['key' => $setting[0], 'value' => $setting[1], 'group' => 'general', 'created_at' => $now, 'updated_at' => $now]);
        }

        if (! filter_var(env('DEMO_SEED', false), FILTER_VALIDATE_BOOL)) {
            return;
        }
        $names = ['Aksi Cola 330ml', 'Aksi Mineral Water 600ml', 'IndoFresh Orange Juice', 'Prima Green Tea', 'Sari Rasa Coffee Sachet', 'Aksi Potato Chips Original', 'Aksi Potato Chips Barbecue', 'Nusantara Cassava Chips', 'Prima Chocolate Wafer', 'Sari Rasa Biscuit Butter', 'Aksi Instant Noodle Curry', 'Aksi Instant Noodle Chicken', 'IndoFresh White Rice 5kg', 'IndoFresh Cooking Oil 1L', 'Prima Granulated Sugar 1kg', 'Aksi Dish Soap 800ml', 'Aksi Laundry Detergent 900g', 'Nusantara Tissue 200 Sheets', 'Prima Floor Cleaner 800ml', 'Aksi Multipurpose Cleaner', 'Sari Rasa Hand Soap 450ml', 'Aksi Shampoo 170ml', 'Prima Toothpaste 120g', 'Aksi Toothbrush Soft', 'Nusantara Body Wash 250ml', 'Aksi Ballpoint Blue', 'Aksi Ballpoint Black', 'Prima Notebook A5', 'Nusantara Marker Black', 'Aksi Paper A4 80gsm', 'IndoFresh Banana Chips', 'Sari Rasa Peanut Snack', 'Prima Milk UHT 1L', 'Aksi Soy Sauce 135ml', 'Nusantara Chili Sauce 135ml', 'Prima Salt 500g', 'Aksi Bread Whole Wheat', 'IndoFresh Eggs 10pcs', 'Sari Rasa Cereal 250g', 'Aksi Oat Cookies', 'Prima Plastic Bag Large', 'Nusantara Aluminum Foil', 'Aksi Battery AA 2pcs', 'Prima LED Bulb 9W', 'IndoFresh Facial Tissue', 'Sari Rasa Mineral Water 1.5L', 'Aksi Lemon Tea 500ml', 'Prima Sparkling Water', 'Nusantara Corn Chips', 'Aksi Energy Drink 250ml'];
        foreach ($names as $i => $name) {
            $cost = 5000 + (($i % 9) * 1750);
            $price = $cost * 1.35;
            $productId = DB::table('products')->insertGetId(['category_id' => ($i % 6) + 1, 'brand_id' => ($i % 5) + 1, 'unit_id' => 1, 'default_supplier_id' => ($i % 3) + 1, 'sku' => 'AKS-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT), 'barcode' => '899100'.str_pad((string) ($i + 1), 7, '0', STR_PAD_LEFT), 'name' => $name, 'slug' => Str::slug($name).'-'.($i + 1), 'product_type' => 'simple', 'purchase_cost' => $cost, 'selling_price' => $price, 'low_stock_threshold' => 8, 'track_inventory' => true, 'allow_negative_inventory' => false, 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
            $qty = 20 + ($i % 21);
            DB::table('inventory_stocks')->insert(['warehouse_id' => $centralWarehouse, 'product_id' => $productId, 'quantity' => $qty, 'average_cost' => $cost, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('inventory_ledgers')->insert(['warehouse_id' => $centralWarehouse, 'product_id' => $productId, 'user_id' => $adminId, 'movement_type' => 'stock_opening', 'quantity' => $qty, 'before_quantity' => 0, 'after_quantity' => $qty, 'unit_cost' => $cost, 'reference_type' => 'seed', 'reference_id' => 0, 'note' => 'Development opening stock', 'created_at' => $now, 'updated_at' => $now]);
        }
    }
}
