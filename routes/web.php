<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackOfficeController;
use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    try {
        DB::select('select 1');

        return response()->json(['status' => 'ok']);
    } catch (Throwable) {
        return response()->json(['status' => 'degraded'], 503);
    }
})->name('health');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');

Route::middleware('pos.auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [BackOfficeController::class, 'dashboard'])->middleware('pos.permission:dashboard.view')->name('dashboard');

    Route::get('/pos', [PosController::class, 'index'])->middleware('pos.permission:sales.create')->name('pos.index');
    Route::get('/api/v1/products/search', [PosController::class, 'search'])->middleware('pos.permission:sales.create')->name('api.products.search');
    Route::post('/api/v1/sales/checkout', [PosController::class, 'checkout'])->middleware('pos.permission:sales.create')->name('api.sales.checkout');
    Route::get('/sales/{sale}/receipt', [PosController::class, 'receipt'])->middleware('pos.permission:sales.view')->name('sales.receipt');
    Route::post('/sales/{sale}/return', [PosController::class, 'returnItem'])->middleware('pos.permission:sales.return')->name('sales.return');

    Route::get('/products', [BackOfficeController::class, 'products'])->middleware('pos.permission:products.view')->name('products.index');
    Route::post('/products', [BackOfficeController::class, 'storeProduct'])->middleware('pos.permission:products.create')->name('products.store');
    Route::get('/customers', [BackOfficeController::class, 'customers'])->middleware('pos.permission:customers.view')->name('customers.index');
    Route::post('/customers', [BackOfficeController::class, 'storeCustomer'])->middleware('pos.permission:customers.create')->name('customers.store');
    Route::get('/suppliers', [BackOfficeController::class, 'suppliers'])->middleware('pos.permission:suppliers.view')->name('suppliers.index');
    Route::post('/suppliers', [BackOfficeController::class, 'storeSupplier'])->middleware('pos.permission:suppliers.create')->name('suppliers.store');
    Route::get('/purchases', [BackOfficeController::class, 'purchases'])->middleware('pos.permission:purchases.view')->name('purchases.index');
    Route::post('/purchases', [BackOfficeController::class, 'storePurchase'])->middleware('pos.permission:purchases.create')->name('purchases.store');
    Route::post('/purchases/{purchase}/receive', [BackOfficeController::class, 'receivePurchase'])->middleware('pos.permission:purchases.receive')->name('purchases.receive');
    Route::get('/inventory', [BackOfficeController::class, 'inventory'])->middleware('pos.permission:inventory.view')->name('inventory.index');
    Route::get('/reports', [BackOfficeController::class, 'reports'])->middleware('pos.permission:reports.view')->name('reports.index');
    Route::get('/registers', [BackOfficeController::class, 'registers'])->middleware('pos.permission:sales.create')->name('registers.index');
    Route::post('/registers/open', [BackOfficeController::class, 'openRegister'])->middleware('pos.permission:sales.create')->name('registers.open');
    Route::post('/registers/{session}/close', [BackOfficeController::class, 'closeRegister'])->middleware('pos.permission:sales.create')->name('registers.close');
    Route::get('/administration/{section?}', [BackOfficeController::class, 'administration'])->whereIn('section', ['settings', 'users', 'roles', 'audit'])->name('administration.index');
    Route::put('/administration/settings', [BackOfficeController::class, 'updateSettings'])->middleware('pos.permission:settings.manage')->name('administration.settings.update');
});
