<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('destination_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('transfer_number')->unique();
            $table->string('status')->default('draft')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['source_warehouse_id', 'destination_warehouse_id', 'status'], 'stock_transfers_route_status_idx');
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('requested_quantity', 19, 4);
            $table->decimal('shipped_quantity', 19, 4)->default(0);
            $table->decimal('received_quantity', 19, 4)->default(0);
            $table->decimal('unit_cost', 19, 4)->default(0);
            $table->timestamps();
            $table->unique(['stock_transfer_id', 'product_id']);
        });

        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('count_number')->unique();
            $table->string('status')->default('draft')->index();
            $table->timestamp('snapshot_at')->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['warehouse_id', 'status']);
        });

        Schema::create('stock_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('system_quantity', 19, 4);
            $table->decimal('counted_quantity', 19, 4)->nullable();
            $table->decimal('variance_quantity', 19, 4)->nullable();
            $table->timestamps();
            $table->unique(['stock_count_id', 'product_id']);
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('quote_number')->unique();
            $table->date('quote_date');
            $table->date('valid_until')->nullable();
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('discount_total', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('grand_total', 19, 4)->default(0);
            $table->string('status')->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'quote_date']);
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->string('sku');
            $table->decimal('quantity', 19, 4);
            $table->decimal('unit_price', 19, 4);
            $table->decimal('discount_amount', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4);
            $table->timestamps();
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_number')->unique();
            $table->date('order_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('discount_total', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('grand_total', 19, 4)->default(0);
            $table->string('status')->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'order_date']);
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->string('sku');
            $table->decimal('quantity', 19, 4);
            $table->decimal('fulfilled_quantity', 19, 4)->default(0);
            $table->decimal('unit_price', 19, 4);
            $table->decimal('discount_amount', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('stock_count_items');
        Schema::dropIfExists('stock_counts');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
