<?php

namespace App\Http\Controllers;

use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        $store = $this->activeStore();
        $warehouse = DB::table('warehouses')->where('store_id', $store?->id)->where('active', true)->orderBy('id')->first();
        $register = DB::table('registers')->where('store_id', $store?->id)->where('active', true)->orderBy('id')->first();
        $session = $register ? DB::table('register_sessions')->where('register_id', $register->id)->where('user_id', session('user_id'))->where('status', 'open')->first() : null;
        $categories = DB::table('categories')->where('active', true)->orderBy('name')->get();
        $customers = DB::table('customers')->where('status', 'active')->orderBy('name')->get();
        $products = $this->productsForPos($warehouse?->id)->take(18)->get();

        return view('pos.index', compact('store', 'warehouse', 'register', 'session', 'categories', 'customers', 'products'));
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => ['nullable', 'string', 'max:120'], 'category_id' => ['nullable', 'integer', 'exists:categories,id'], 'warehouse_id' => ['required', 'integer']]);
        $activeStore = $this->activeStore();
        abort_unless(DB::table('warehouses')->where('id', (int) $request->input('warehouse_id'))->where('store_id', $activeStore->id)->exists(), 403);
        $query = trim((string) $request->input('q'));
        $products = $this->productsForPos((int) $request->input('warehouse_id'))
            ->when($request->filled('category_id'), fn ($builder) => $builder->where('products.category_id', (int) $request->input('category_id')))
            ->when($query !== '', fn ($builder) => $builder->where(function ($q) use ($query) {
                $q->where('products.barcode', $query)->orWhere('products.sku', $query)->orWhere('products.name', 'like', "%{$query}%");
            }))
            ->take(24)->get();

        return response()->json($products);
    }

    public function checkout(Request $request, SaleService $sales): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'register_session_id' => ['nullable', 'integer', 'exists:register_sessions,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,card,qris,bank_transfer,e_wallet,other'],
            'tendered_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $activeStore = $this->activeStore();
        abort_unless((int) $data['store_id'] === (int) $activeStore->id && DB::table('warehouses')->where('id', $data['warehouse_id'])->where('store_id', $activeStore->id)->exists(), 403);
        $saleId = $sales->complete($data);
        $sale = DB::table('sales')->find($saleId);

        return response()->json([
            'message' => 'Sale completed successfully.',
            'sale_id' => $saleId,
            'invoice_number' => $sale->invoice_number,
            'receipt_url' => route('sales.receipt', $saleId),
        ]);
    }

    public function receipt(int $saleId): View
    {
        $sale = DB::table('sales')
            ->join('stores', 'stores.id', '=', 'sales.store_id')
            ->join('companies', 'companies.id', '=', 'stores.company_id')
            ->join('users', 'users.id', '=', 'sales.cashier_id')
            ->where('sales.id', $saleId)
            ->select('sales.*', 'stores.name as store_name', 'stores.address as store_address', 'companies.name as company_name', 'companies.phone as company_phone', 'users.name as cashier_name')
            ->firstOrFail();
        $items = DB::table('sale_items')->where('sale_id', $saleId)->get();
        $payments = DB::table('payments')->where('sale_id', $saleId)->get();

        return view('sales.receipt', compact('sale', 'items', 'payments'));
    }

    public function returnItem(Request $request, int $saleId, SaleService $sales): RedirectResponse
    {
        $data = $request->validate([
            'sale_item_id' => ['required', 'integer', 'exists:sale_items,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $returnId = $sales->returnItem($saleId, (int) $data['sale_item_id'], (float) $data['quantity'], $data['reason']);

        return back()->with('success', "Return {$returnId} was completed and the stock ledger was updated.");
    }

    private function activeStore(): object
    {
        $stores = session('user_role') === 'Super Administrator'
            ? DB::table('stores')->where('active', true)->orderBy('name')->get()
            : DB::table('stores')->join('user_stores', 'user_stores.store_id', '=', 'stores.id')->where('user_stores.user_id', session('user_id'))->where('stores.active', true)->select('stores.*')->orderBy('stores.name')->get();
        $store = $stores->firstWhere('id', session('active_store_id')) ?: $stores->first();
        abort_unless($store, 403, 'No outlet is assigned to this user.');
        session(['active_store_id' => $store->id]);

        return $store;
    }

    private function productsForPos(?int $warehouseId)
    {
        return DB::table('products')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('inventory_stocks', function ($join) use ($warehouseId) {
                $join->on('inventory_stocks.product_id', '=', 'products.id')->where('inventory_stocks.warehouse_id', '=', $warehouseId ?? 0);
            })
            ->where('products.active', true)
            ->select('products.id', 'products.category_id', 'products.name', 'products.sku', 'products.barcode', 'products.image_path', 'products.selling_price', 'products.low_stock_threshold', 'categories.name as category_name', DB::raw('COALESCE(inventory_stocks.quantity, 0) as stock'))
            ->orderBy('products.name');
    }
}
