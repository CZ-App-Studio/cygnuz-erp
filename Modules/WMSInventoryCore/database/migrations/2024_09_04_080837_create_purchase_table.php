<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('code')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('invoice_no')->nullable();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('draft');
            $table->string('payment_status')->default('pending');
            $table->string('payment_terms')->nullable();
            $table->date('payment_due_date')->nullable();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->string('shipping_method')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('approval_status')->default('pending');
            $table->unsignedBigInteger('approved_by_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('received_by_id')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('restrict');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('approved_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('received_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('set null');

            // Performance indexes
            $table->index('status');
            $table->index('payment_status');
            $table->index('approval_status');
            $table->index('date');
            $table->index('payment_due_date');
            $table->index('expected_delivery_date');
            $table->index('created_by_id');
            $table->index('updated_by_id');
            $table->index('approved_by_id');
            $table->index('received_by_id');
            $table->index(['status', 'deleted_at']);
            $table->index(['payment_status', 'deleted_at']);
            $table->index(['vendor_id', 'status']);
            $table->index(['warehouse_id', 'status']);
            $table->index('code');
            $table->index('reference_no');
            $table->index('invoice_no');
        });

        Schema::create('purchase_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->unsignedBigInteger('unit_id');
            $table->decimal('weight', 8, 2)->nullable();

            // Pricing details
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->decimal('tax_rate', 8, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->nullable();
            $table->decimal('discount_rate', 8, 2)->nullable();
            $table->decimal('discount_amount', 15, 2)->nullable();
            $table->decimal('subtotal', 15, 2)->nullable();

            // Received information
            $table->integer('received_quantity')->default(0);
            $table->boolean('is_fully_received')->default(false);

            // Quality information
            $table->integer('accepted_quantity')->default(0);
            $table->integer('rejected_quantity')->default(0);
            $table->text('rejection_reason')->nullable();

            // Audit fields
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('restrict');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('set null');

            // Performance indexes
            $table->index('created_by_id');
            $table->index('updated_by_id');
            $table->index('deleted_at');
        });

        Schema::create('purchase_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->string('file_path');
            $table->timestamps();

            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_attachments');
        Schema::dropIfExists('purchase_products');
        Schema::dropIfExists('purchases');
    }
};
