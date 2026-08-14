<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('email');
            $table->string('status')->default('active')->after('password');
            $table->timestamp('last_login_at')->nullable()->after('status');
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('currency', 3)->default('IDR');
            $table->string('timezone')->default('Asia/Jakarta');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('tax_number')->nullable();
            $table->timestamps();
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('invoice_prefix')->default('INV');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module');
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('user_stores', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->primary(['user_id', 'store_id']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol', 12)->unique();
            $table->decimal('conversion_factor', 19, 4)->default(1);
            $table->timestamps();
        });

        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('rate', 8, 4)->default(0);
            $table->boolean('inclusive')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('default_discount', 8, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->decimal('credit_limit', 19, 4)->default(0);
            $table->decimal('outstanding_balance', 19, 4)->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('company_name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('payment_terms')->nullable();
            $table->decimal('credit_limit', 19, 4)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('default_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('name')->index();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('product_type')->default('simple');
            $table->decimal('purchase_cost', 19, 4)->default(0);
            $table->decimal('selling_price', 19, 4)->default(0);
            $table->decimal('wholesale_price', 19, 4)->nullable();
            $table->decimal('minimum_price', 19, 4)->nullable();
            $table->decimal('low_stock_threshold', 19, 4)->default(0);
            $table->decimal('reorder_level', 19, 4)->default(0);
            $table->boolean('track_inventory')->default(true);
            $table->boolean('allow_negative_inventory')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['name', 'active']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->decimal('purchase_cost', 19, 4)->nullable();
            $table->decimal('selling_price', 19, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 19, 4)->default(0);
            $table->decimal('average_cost', 19, 4)->default(0);
            $table->timestamps();
            $table->unique(['warehouse_id', 'product_id']);
        });

        Schema::create('inventory_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('movement_type')->index();
            $table->decimal('quantity', 19, 4);
            $table->decimal('before_quantity', 19, 4);
            $table->decimal('after_quantity', 19, 4);
            $table->decimal('unit_cost', 19, 4)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'warehouse_id', 'created_at']);
        });

        Schema::create('register_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('register_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('opening_balance', 19, 4)->default(0);
            $table->decimal('expected_cash', 19, 4)->default(0);
            $table->decimal('actual_cash', 19, 4)->nullable();
            $table->decimal('variance', 19, 4)->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('po_number')->unique();
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('discount_total', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('grand_total', 19, 4)->default(0);
            $table->string('status')->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('ordered_quantity', 19, 4);
            $table->decimal('received_quantity', 19, 4)->default(0);
            $table->decimal('unit_cost', 19, 4);
            $table->decimal('line_total', 19, 4);
            $table->timestamps();
        });

        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receipt_number')->unique();
            $table->timestamp('received_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 19, 4);
            $table->decimal('unit_cost', 19, 4);
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('register_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->string('invoice_number')->unique();
            $table->timestamp('sold_at');
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('discount_total', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('rounding_total', 19, 4)->default(0);
            $table->decimal('grand_total', 19, 4)->default(0);
            $table->decimal('paid_total', 19, 4)->default(0);
            $table->string('status')->default('completed')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'sold_at']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->string('sku');
            $table->decimal('quantity', 19, 4);
            $table->decimal('unit_price', 19, 4);
            $table->decimal('unit_cost', 19, 4);
            $table->decimal('discount_amount', 19, 4)->default(0);
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method');
            $table->decimal('amount', 19, 4);
            $table->decimal('tendered_amount', 19, 4)->nullable();
            $table->decimal('change_amount', 19, 4)->default(0);
            $table->string('reference')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('return_number')->unique();
            $table->decimal('refund_total', 19, 4)->default(0);
            $table->string('refund_method')->default('cash');
            $table->string('status')->default('completed');
            $table->text('reason');
            $table->timestamps();
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 19, 4);
            $table->decimal('refund_amount', 19, 4);
            $table->timestamps();
        });

        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('register_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->decimal('amount', 19, 4);
            $table->text('reason');
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('register_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('expense_number')->unique();
            $table->string('category');
            $table->decimal('amount', 19, 4);
            $table->string('payment_method');
            $table->date('expense_date');
            $table->text('description')->nullable();
            $table->string('status')->default('approved');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('module');
            $table->string('record_type')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['module', 'created_at']);
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type');
            $table->string('prefix');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();
            $table->unique(['store_id', 'document_type']);
        });
    }

    public function down(): void
    {
        foreach (['document_sequences', 'system_settings', 'audit_logs', 'expenses', 'cash_movements', 'sale_return_items', 'sale_returns', 'payments', 'sale_items', 'sales', 'purchase_receipt_items', 'purchase_receipts', 'purchase_order_items', 'purchase_orders', 'register_sessions', 'inventory_ledgers', 'inventory_stocks', 'product_variants', 'products', 'suppliers', 'customers', 'customer_groups', 'taxes', 'units', 'brands', 'categories', 'user_stores', 'user_roles', 'role_permissions', 'permissions', 'roles', 'registers', 'warehouses', 'stores', 'companies'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'status', 'last_login_at']);
        });
    }
};
