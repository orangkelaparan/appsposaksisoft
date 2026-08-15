<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\PurchaseService;
use App\Services\RegisterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackOfficeController extends Controller
{
    public function dashboard(): View
    {
        $today = now()->startOfDay();
        $sales = DB::table('sales')->where('status', 'completed')->where('sold_at', '>=', $today);
        $todaySales = (float) $sales->sum('grand_total');
        $transactions = (int) $sales->count();
        $profit = (float) DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')->where('sales.sold_at', '>=', $today)->selectRaw('COALESCE(SUM(sale_items.line_total - (sale_items.quantity * sale_items.unit_cost)), 0) as value')->value('value');
        $lowStock = DB::table('inventory_stocks')->join('products', 'products.id', '=', 'inventory_stocks.product_id')->whereColumn('inventory_stocks.quantity', '<=', 'products.low_stock_threshold')->count();
        $pendingPurchases = DB::table('purchase_orders')->whereIn('status', ['draft', 'approved', 'partially_received'])->count();
        $cashPosition = (float) DB::table('register_sessions')->where('status', 'open')->sum('expected_cash');
        $recentSales = DB::table('sales')->join('users', 'users.id', '=', 'sales.cashier_id')->select('sales.*', 'users.name as cashier_name')->latest('sold_at')->limit(8)->get();
        $topProducts = DB::table('sale_items')->select('product_name', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(line_total) as revenue'))->groupBy('product_name')->orderByDesc('revenue')->limit(5)->get();
        $dailySales = DB::table('sales')->where('sold_at', '>=', now()->subDays(6)->startOfDay())->where('status', 'completed')->selectRaw('DATE(sold_at) as date, SUM(grand_total) as total')->groupBy('date')->orderBy('date')->get();

        return view('dashboard', compact('todaySales', 'transactions', 'profit', 'lowStock', 'pendingPurchases', 'cashPosition', 'recentSales', 'topProducts', 'dailySales'));
    }

    public function products(): View
    {
        $products = DB::table('products')->leftJoin('categories', 'categories.id', '=', 'products.category_id')->leftJoin('brands', 'brands.id', '=', 'products.brand_id')->select('products.*', 'categories.name as category_name', 'brands.name as brand_name')->latest('products.id')->paginate(12);

        return view('modules.products', ['products' => $products, 'categories' => DB::table('categories')->where('active', true)->orderBy('name')->get(), 'brands' => DB::table('brands')->where('active', true)->orderBy('name')->get(), 'units' => DB::table('units')->orderBy('name')->get(), 'suppliers' => DB::table('suppliers')->where('status', 'active')->orderBy('company_name')->get()]);
    }

    public function storeProduct(Request $request, AuditService $audit): RedirectResponse
    {
        $this->authorizePermission('products.create');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'], 'sku' => ['required', 'string', 'max:80', 'unique:products,sku'], 'barcode' => ['nullable', 'string', 'max:80', 'unique:products,barcode'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'], 'brand_id' => ['nullable', 'integer', 'exists:brands,id'], 'unit_id' => ['nullable', 'integer', 'exists:units,id'], 'default_supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'purchase_cost' => ['required', 'numeric', 'min:0'], 'selling_price' => ['required', 'numeric', 'min:0'], 'low_stock_threshold' => ['required', 'numeric', 'min:0'], 'image_path' => ['nullable', 'string', 'max:255'],
        ]);
        $id = DB::table('products')->insertGetId(array_merge($data, ['image_path' => $data['image_path'] ?: 'images/products/placeholder.svg', 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)), 'product_type' => 'simple', 'track_inventory' => true, 'allow_negative_inventory' => false, 'active' => true, 'created_at' => now(), 'updated_at' => now()]));
        $audit->record('created', 'products', 'product', $id, null, $data);

        return back()->with('success', 'Product created successfully.');
    }

    public function exportProducts(AuditService $audit): StreamedResponse
    {
        $this->authorizePermission('products.export');
        $audit->record('exported', 'products', 'product_catalog', null, null, ['format' => 'csv']);

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['sku', 'barcode', 'name', 'category_id', 'brand_id', 'unit_id', 'purchase_cost', 'selling_price', 'low_stock_threshold', 'description']);
            DB::table('products')->orderBy('id')->select('sku', 'barcode', 'name', 'category_id', 'brand_id', 'unit_id', 'purchase_cost', 'selling_price', 'low_stock_threshold', 'description')->chunk(250, function ($products) use ($output): void {
                foreach ($products as $product) {
                    fputcsv($output, (array) $product);
                }
            });
            fclose($output);
        }, 'aksisoft-products-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importProducts(Request $request, AuditService $audit): RedirectResponse
    {
        $this->authorizePermission('products.import');
        $request->validate(['products_csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $handle = fopen($request->file('products_csv')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $required = ['sku', 'name', 'purchase_cost', 'selling_price'];
        if (! $header || array_diff($required, $header)) {
            fclose($handle);

            return back()->withErrors(['products_csv' => 'CSV must include sku, name, purchase_cost, and selling_price columns.']);
        }
        $columns = array_flip($header);
        $imported = 0;
        DB::transaction(function () use ($handle, $columns, &$imported): void {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) === 1 && trim((string) $row[0]) === '') {
                    continue;
                }
                $value = fn (string $key, mixed $default = null) => array_key_exists($key, $columns) ? ($row[$columns[$key]] ?? $default) : $default;
                $sku = trim((string) $value('sku'));
                $name = trim((string) $value('name'));
                $cost = $value('purchase_cost');
                $price = $value('selling_price');
                if ($sku === '' || $name === '' || ! is_numeric($cost) || ! is_numeric($price) || (float) $cost < 0 || (float) $price < 0) {
                    continue;
                }
                $existing = DB::table('products')->where('sku', $sku)->first();
                $data = ['barcode' => trim((string) $value('barcode')) ?: null, 'name' => $name, 'category_id' => is_numeric($value('category_id')) ? (int) $value('category_id') : null, 'brand_id' => is_numeric($value('brand_id')) ? (int) $value('brand_id') : null, 'unit_id' => is_numeric($value('unit_id')) ? (int) $value('unit_id') : 1, 'purchase_cost' => $cost, 'selling_price' => $price, 'low_stock_threshold' => is_numeric($value('low_stock_threshold')) ? $value('low_stock_threshold') : 0, 'description' => $value('description'), 'image_path' => 'images/products/placeholder.svg', 'product_type' => 'simple', 'track_inventory' => true, 'allow_negative_inventory' => false, 'active' => true, 'updated_at' => now()];
                if ($existing) {
                    DB::table('products')->where('id', $existing->id)->update($data);
                } else {
                    DB::table('products')->insert(array_merge($data, ['sku' => $sku, 'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)), 'created_at' => now()]));
                }
                $imported++;
            }
        });
        fclose($handle);
        $audit->record('imported', 'products', 'product_catalog', null, null, ['format' => 'csv', 'rows' => $imported]);

        return back()->with('success', "Imported or updated {$imported} products from CSV.");
    }

    public function customers(): View
    {
        return view('modules.contacts', ['type' => 'customer', 'records' => DB::table('customers')->orderByDesc('id')->paginate(12), 'groups' => DB::table('customer_groups')->orderBy('name')->get()]);
    }

    public function storeCustomer(Request $request, AuditService $audit): RedirectResponse
    {
        $this->authorizePermission('customers.create');
        $data = $request->validate(['name' => ['required', 'string', 'max:160'], 'phone' => ['nullable', 'string', 'max:40'], 'email' => ['nullable', 'email', 'max:160'], 'address' => ['nullable', 'string', 'max:500'], 'customer_group_id' => ['nullable', 'integer', 'exists:customer_groups,id']]);
        $id = DB::table('customers')->insertGetId(array_merge($data, ['code' => 'CUS-'.str_pad((string) ((DB::table('customers')->max('id') ?? 0) + 1), 5, '0', STR_PAD_LEFT), 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]));
        $audit->record('created', 'customers', 'customer', $id, null, $data);

        return back()->with('success', 'Customer created successfully.');
    }

    public function suppliers(): View
    {
        return view('modules.contacts', ['type' => 'supplier', 'records' => DB::table('suppliers')->orderByDesc('id')->paginate(12), 'groups' => collect()]);
    }

    public function storeSupplier(Request $request, AuditService $audit): RedirectResponse
    {
        $this->authorizePermission('suppliers.create');
        $data = $request->validate(['company_name' => ['required', 'string', 'max:180'], 'contact_person' => ['nullable', 'string', 'max:150'], 'phone' => ['nullable', 'string', 'max:40'], 'email' => ['nullable', 'email', 'max:160'], 'address' => ['nullable', 'string', 'max:500'], 'payment_terms' => ['nullable', 'string', 'max:100']]);
        $id = DB::table('suppliers')->insertGetId(array_merge($data, ['code' => 'SUP-'.str_pad((string) ((DB::table('suppliers')->max('id') ?? 0) + 1), 5, '0', STR_PAD_LEFT), 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]));
        $audit->record('created', 'suppliers', 'supplier', $id, null, $data);

        return back()->with('success', 'Supplier created successfully.');
    }

    public function purchases(): View
    {
        $orders = DB::table('purchase_orders')->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')->join('warehouses', 'warehouses.id', '=', 'purchase_orders.warehouse_id')->select('purchase_orders.*', 'suppliers.company_name', 'warehouses.name as warehouse_name')->latest('purchase_orders.id')->paginate(10);

        return view('modules.purchases', ['orders' => $orders, 'suppliers' => DB::table('suppliers')->where('status', 'active')->orderBy('company_name')->get(), 'products' => DB::table('products')->where('active', true)->orderBy('name')->get(), 'store' => DB::table('stores')->where('active', true)->first(), 'warehouse' => DB::table('warehouses')->where('active', true)->first()]);
    }

    public function storePurchase(Request $request, PurchaseService $purchases): RedirectResponse
    {
        $this->authorizePermission('purchases.create');
        $data = $request->validate(['supplier_id' => ['required', 'integer', 'exists:suppliers,id'], 'store_id' => ['required', 'integer', 'exists:stores,id'], 'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'], 'product_id' => ['required', 'integer', 'exists:products,id'], 'quantity' => ['required', 'numeric', 'min:0.001'], 'unit_cost' => ['required', 'numeric', 'min:0'], 'notes' => ['nullable', 'string', 'max:500']]);
        $purchases->create(['supplier_id' => $data['supplier_id'], 'store_id' => $data['store_id'], 'warehouse_id' => $data['warehouse_id'], 'notes' => $data['notes'] ?? null, 'items' => [['product_id' => $data['product_id'], 'quantity' => $data['quantity'], 'unit_cost' => $data['unit_cost']]]]);

        return back()->with('success', 'Approved purchase order created. Receive it to add stock.');
    }

    public function receivePurchase(int $orderId, PurchaseService $purchases): RedirectResponse
    {
        $this->authorizePermission('purchases.receive');
        $items = DB::table('purchase_order_items')->where('purchase_order_id', $orderId)->whereColumn('received_quantity', '<', 'ordered_quantity')->get()->map(fn ($item) => ['purchase_order_item_id' => $item->id, 'quantity' => (float) $item->ordered_quantity - (float) $item->received_quantity])->all();
        if (empty($items)) {
            return back()->with('error', 'No outstanding quantities are available to receive.');
        }
        $purchases->receive($orderId, $items);

        return back()->with('success', 'Purchase receipt completed and inventory ledger updated.');
    }

    public function inventory(): View
    {
        $stocks = DB::table('inventory_stocks')->join('products', 'products.id', '=', 'inventory_stocks.product_id')->join('warehouses', 'warehouses.id', '=', 'inventory_stocks.warehouse_id')->select('inventory_stocks.*', 'products.name as product_name', 'products.sku', 'products.low_stock_threshold', 'warehouses.name as warehouse_name')->orderBy('products.name')->paginate(15);
        $ledgers = DB::table('inventory_ledgers')->join('products', 'products.id', '=', 'inventory_ledgers.product_id')->join('warehouses', 'warehouses.id', '=', 'inventory_ledgers.warehouse_id')->select('inventory_ledgers.*', 'products.name as product_name', 'warehouses.name as warehouse_name')->latest('inventory_ledgers.id')->limit(12)->get();

        return view('modules.inventory', compact('stocks', 'ledgers'));
    }

    public function reports(): View
    {
        $from = request('from', now()->startOfMonth()->toDateString());
        $to = request('to', now()->toDateString());
        $salesQuery = DB::table('sales')->whereBetween('sold_at', [$from.' 00:00:00', $to.' 23:59:59'])->where('status', 'completed');
        $summary = ['revenue' => (float) $salesQuery->sum('grand_total'), 'transactions' => (int) $salesQuery->count(), 'discounts' => (float) $salesQuery->sum('discount_total'), 'tax' => (float) $salesQuery->sum('tax_total'), 'stock_value' => (float) DB::table('inventory_stocks')->selectRaw('COALESCE(SUM(quantity * average_cost),0) as value')->value('value')];
        $byProduct = DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')->whereBetween('sales.sold_at', [$from.' 00:00:00', $to.' 23:59:59'])->select('sale_items.product_name', DB::raw('SUM(sale_items.quantity) as quantity'), DB::raw('SUM(sale_items.line_total) as total'))->groupBy('sale_items.product_name')->orderByDesc('total')->limit(10)->get();
        $byPayment = DB::table('payments')->join('sales', 'sales.id', '=', 'payments.sale_id')->whereBetween('sales.sold_at', [$from.' 00:00:00', $to.' 23:59:59'])->select('payments.method', DB::raw('SUM(payments.amount) as total'))->groupBy('payments.method')->get();

        return view('modules.reports', compact('from', 'to', 'summary', 'byProduct', 'byPayment'));
    }

    public function registers(): View
    {
        $registers = DB::table('registers')->join('stores', 'stores.id', '=', 'registers.store_id')->select('registers.*', 'stores.name as store_name')->get();
        $sessions = DB::table('register_sessions')->join('registers', 'registers.id', '=', 'register_sessions.register_id')->join('users', 'users.id', '=', 'register_sessions.user_id')->select('register_sessions.*', 'registers.name as register_name', 'users.name as cashier_name')->latest('register_sessions.id')->limit(20)->get();

        return view('modules.registers', compact('registers', 'sessions'));
    }

    public function openRegister(Request $request, RegisterService $registers): RedirectResponse
    {
        $data = $request->validate(['register_id' => ['required', 'integer', 'exists:registers,id'], 'opening_balance' => ['required', 'numeric', 'min:0']]);
        $registers->open((int) $data['register_id'], (float) $data['opening_balance']);

        return back()->with('success', 'Cash register opened. You can now use the POS.');
    }

    public function closeRegister(Request $request, int $sessionId, RegisterService $registers): RedirectResponse
    {
        $data = $request->validate(['actual_cash' => ['required', 'numeric', 'min:0']]);
        $registers->close($sessionId, (float) $data['actual_cash']);

        return back()->with('success', 'Register closed and variance recorded.');
    }

    public function administration(string $section = 'settings'): View
    {
        $this->authorizePermission(match ($section) {
            'settings' => 'settings.manage',
            'users', 'roles' => 'roles.manage',
            'audit' => 'audit.view',
        });

        $data = match ($section) {
            'users' => ['records' => DB::table('users')->leftJoin('user_roles', 'user_roles.user_id', '=', 'users.id')->leftJoin('roles', 'roles.id', '=', 'user_roles.role_id')->select('users.*', 'roles.name as role_name')->paginate(15)],
            'roles' => ['records' => DB::table('roles')->orderBy('name')->get(), 'permissions' => DB::table('permissions')->orderBy('module')->orderBy('slug')->get()],
            'audit' => ['records' => DB::table('audit_logs')->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')->select('audit_logs.*', 'users.name as user_name')->latest('audit_logs.id')->paginate(25)],
            default => ['records' => DB::table('system_settings')->orderBy('group')->orderBy('key')->get()],
        };

        return view('modules.administration', array_merge(['section' => $section], $data));
    }

    public function updateSettings(Request $request, AuditService $audit): RedirectResponse
    {
        $this->authorizePermission('settings.manage');
        $data = $request->validate(['company_name' => ['required', 'string', 'max:160'], 'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'], 'allow_negative_stock' => ['nullable', 'boolean']]);
        foreach ($data as $key => $value) {
            DB::table('system_settings')->updateOrInsert(['key' => $key], ['value' => (string) $value, 'group' => 'general', 'updated_at' => now(), 'created_at' => now()]);
        }
        $audit->record('updated', 'settings', 'system_settings', null, null, $data);

        return back()->with('success', 'Settings saved successfully.');
    }

    private function authorizePermission(string $permission): void
    {
        $role = session('user_role');
        if ($role === 'Super Administrator') {
            return;
        }
        $allowed = DB::table('user_roles')->join('role_permissions', 'role_permissions.role_id', '=', 'user_roles.role_id')->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')->where('user_roles.user_id', session('user_id'))->where('permissions.slug', $permission)->exists();
        abort_unless($allowed, 403, 'You do not have permission to perform this action.');
    }
}
