<?php

namespace App\Http\Controllers;

use App\Services\ExpenseService;
use App\Services\SalesDocumentService;
use App\Services\StockCountService;
use App\Services\StockTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PriorityOperationsController extends Controller
{
    public function switchStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['store_id' => ['required', 'integer', 'exists:stores,id']]);
        abort_unless($this->stores()->contains('id', (int) $data['store_id']), 403);
        session(['active_store_id' => (int) $data['store_id']]);

        return back()->with('success', 'Active outlet updated.');
    }

    public function index(string $module = 'transfers'): View
    {
        $permission = match ($module) {
            'transfers' => 'inventory.transfer',
            'stocktake' => 'inventory.count',
            'expenses' => 'expenses.view',
            'documents' => 'sales.quote',
            default => abort(404),
        };
        $this->authorizePermission($permission);
        $store = $this->activeStore();
        $availableStores = $this->stores();
        $warehouses = DB::table('warehouses')->where('store_id', $store->id)->where('active', true)->orderBy('name')->get();
        $destinationWarehouses = DB::table('warehouses')->join('stores', 'stores.id', '=', 'warehouses.store_id')->where('stores.company_id', $store->company_id)->where('warehouses.active', true)->select('warehouses.*', 'stores.name as store_name')->orderBy('stores.name')->orderBy('warehouses.name')->get();
        $products = DB::table('products')->where('active', true)->orderBy('name')->limit(300)->get();
        $customers = DB::table('customers')->where('status', 'active')->orderBy('name')->get();
        $registerSessions = DB::table('register_sessions')->join('registers', 'registers.id', '=', 'register_sessions.register_id')->where('registers.store_id', $store->id)->where('register_sessions.status', 'open')->select('register_sessions.*', 'registers.name as register_name')->get();
        $records = match ($module) {
            'transfers' => DB::table('stock_transfers')->join('warehouses as source', 'source.id', '=', 'stock_transfers.source_warehouse_id')->join('warehouses as destination', 'destination.id', '=', 'stock_transfers.destination_warehouse_id')->select('stock_transfers.*', 'source.name as source_name', 'destination.name as destination_name')->where('source.store_id', $store->id)->latest('stock_transfers.id')->paginate(15),
            'stocktake' => DB::table('stock_counts')->join('warehouses', 'warehouses.id', '=', 'stock_counts.warehouse_id')->select('stock_counts.*', 'warehouses.name as warehouse_name')->where('warehouses.store_id', $store->id)->latest('stock_counts.id')->paginate(15),
            'expenses' => DB::table('expenses')->where('store_id', $store->id)->latest('expense_date')->paginate(15),
            'documents' => collect(['quotations' => DB::table('quotations')->where('store_id', $store->id)->latest('id')->limit(10)->get(), 'orders' => DB::table('sales_orders')->where('store_id', $store->id)->latest('id')->limit(10)->get()]),
        };

        return view('modules.operations', compact('module', 'store', 'availableStores', 'warehouses', 'destinationWarehouses', 'products', 'customers', 'registerSessions', 'records'));
    }

    public function createTransfer(Request $request, StockTransferService $service): RedirectResponse
    {
        $this->authorizePermission('inventory.transfer');
        $data = $request->validate(['source_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'], 'destination_warehouse_id' => ['required', 'different:source_warehouse_id', 'integer', 'exists:warehouses,id'], 'product_id' => ['required', 'integer', 'exists:products,id'], 'quantity' => ['required', 'numeric', 'gt:0'], 'notes' => ['nullable', 'string', 'max:500']]);
        $store = $this->activeStore();
        abort_unless(DB::table('warehouses')->where('id', $data['source_warehouse_id'])->where('store_id', $store->id)->exists() && DB::table('warehouses')->join('stores', 'stores.id', '=', 'warehouses.store_id')->where('warehouses.id', $data['destination_warehouse_id'])->where('stores.company_id', $store->company_id)->exists(), 403);
        $service->create(['source_warehouse_id' => $data['source_warehouse_id'], 'destination_warehouse_id' => $data['destination_warehouse_id'], 'notes' => $data['notes'] ?? null, 'items' => [['product_id' => $data['product_id'], 'quantity' => $data['quantity']]]]);

        return back()->with('success', 'Stock transfer created as draft.');
    }

    public function transferAction(int $transferId, string $action, StockTransferService $service): RedirectResponse
    {
        $this->authorizePermission('inventory.transfer');
        match ($action) {
            'approve' => $service->approve($transferId),
            'ship' => $service->ship($transferId),
            'receive' => $service->receive($transferId),
            default => abort(404),
        };

        return back()->with('success', 'Stock transfer '.$action.'d successfully.');
    }

    public function createStockCount(Request $request, StockCountService $service): RedirectResponse
    {
        $this->authorizePermission('inventory.count');
        $data = $request->validate(['warehouse_id' => ['required', 'integer', 'exists:warehouses,id'], 'notes' => ['nullable', 'string', 'max:500']]);
        abort_unless(DB::table('warehouses')->where('id', $data['warehouse_id'])->where('store_id', $this->activeStore()->id)->exists(), 403);
        $service->create((int) $data['warehouse_id'], $data['notes'] ?? null);

        return back()->with('success', 'Stocktake created using an inventory snapshot.');
    }

    public function recordStockCount(Request $request, int $countId, StockCountService $service): RedirectResponse
    {
        $this->authorizePermission('inventory.count');
        $data = $request->validate(['product_id' => ['required', 'integer', 'exists:products,id'], 'counted_quantity' => ['required', 'numeric', 'min:0']]);
        abort_unless(DB::table('stock_counts')->join('warehouses', 'warehouses.id', '=', 'stock_counts.warehouse_id')->where('stock_counts.id', $countId)->where('warehouses.store_id', $this->activeStore()->id)->exists(), 403);
        $service->record($countId, [(int) $data['product_id'] => $data['counted_quantity']]);

        return back()->with('success', 'Physical quantity recorded.');
    }

    public function approveStockCount(int $countId, StockCountService $service): RedirectResponse
    {
        $this->authorizePermission('inventory.count');
        abort_unless(DB::table('stock_counts')->join('warehouses', 'warehouses.id', '=', 'stock_counts.warehouse_id')->where('stock_counts.id', $countId)->where('warehouses.store_id', $this->activeStore()->id)->exists(), 403);
        $service->approve($countId);

        return back()->with('success', 'Stocktake approved and ledger adjustments posted.');
    }

    public function storeExpense(Request $request, ExpenseService $service): RedirectResponse
    {
        $this->authorizePermission('expenses.create');
        $data = $request->validate(['store_id' => ['required', 'integer', 'exists:stores,id'], 'register_session_id' => ['nullable', 'integer', 'exists:register_sessions,id'], 'category' => ['required', 'string', 'max:100'], 'amount' => ['required', 'numeric', 'gt:0'], 'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'card', 'other'])], 'expense_date' => ['required', 'date'], 'description' => ['nullable', 'string', 'max:500']]);
        abort_unless((int) $data['store_id'] === (int) $this->activeStore()->id, 403);
        $service->create($data);

        return back()->with('success', 'Expense recorded successfully.');
    }

    public function createQuotation(Request $request, SalesDocumentService $service): RedirectResponse
    {
        $this->authorizePermission('sales.quote');
        $data = $request->validate(['store_id' => ['required', 'integer', 'exists:stores,id'], 'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'], 'customer_id' => ['nullable', 'integer', 'exists:customers,id'], 'product_id' => ['required', 'integer', 'exists:products,id'], 'quantity' => ['required', 'numeric', 'gt:0'], 'unit_price' => ['nullable', 'numeric', 'min:0'], 'discount_total' => ['nullable', 'numeric', 'min:0'], 'valid_until' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:500']]);
        abort_unless((int) $data['store_id'] === (int) $this->activeStore()->id && DB::table('warehouses')->where('id', $data['warehouse_id'])->where('store_id', $data['store_id'])->exists(), 403);
        $service->createQuotation(array_merge($data, ['items' => [['product_id' => $data['product_id'], 'quantity' => $data['quantity'], 'unit_price' => $data['unit_price'] ?? null]]]));

        return back()->with('success', 'Quotation created successfully.');
    }

    public function convertQuotation(Request $request, int $quotationId, SalesDocumentService $service): RedirectResponse
    {
        $this->authorizePermission('sales.order');
        $data = $request->validate(['due_date' => ['nullable', 'date']]);
        abort_unless(DB::table('quotations')->where('id', $quotationId)->where('store_id', $this->activeStore()->id)->exists(), 403);
        $service->convertToOrder($quotationId, $data['due_date'] ?? null);

        return back()->with('success', 'Quotation converted to confirmed sales order.');
    }

    private function stores()
    {
        if (session('user_role') === 'Super Administrator') {
            return DB::table('stores')->where('active', true)->orderBy('name')->get();
        }

        return DB::table('stores')->join('user_stores', 'user_stores.store_id', '=', 'stores.id')->where('user_stores.user_id', session('user_id'))->where('stores.active', true)->select('stores.*')->orderBy('stores.name')->get();
    }

    private function activeStore(): object
    {
        $stores = $this->stores();
        $store = $stores->firstWhere('id', session('active_store_id')) ?: $stores->first();
        abort_unless($store, 403, 'No outlet is assigned to this user.');
        session(['active_store_id' => $store->id]);

        return $store;
    }

    private function authorizePermission(string $permission): void
    {
        if (session('user_role') === 'Super Administrator') {
            return;
        }
        $allowed = DB::table('user_roles')->join('role_permissions', 'role_permissions.role_id', '=', 'user_roles.role_id')->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')->where('user_roles.user_id', session('user_id'))->where('permissions.slug', $permission)->exists();
        abort_unless($allowed, 403, 'You do not have permission to perform this action.');
    }
}
