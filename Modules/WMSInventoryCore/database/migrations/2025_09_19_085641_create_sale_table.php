<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
     Schema::create('sales', function (Blueprint $table) {
        $table->id();
        $table->date('date');
        $table->string('code')->nullable();

        // Order and invoice references
        $table->string('reference_no')->nullable();
        $table->string('invoice_no')->nullable();
        $table->string('order_no')->nullable();

        $table->unsignedBigInteger('customer_id');
        $table->unsignedBigInteger('warehouse_id');

        // Financial information
        $table->decimal('subtotal', 15, 2)->nullable();
        $table->decimal('tax_amount', 15, 2)->nullable();
        $table->decimal('discount_amount', 15, 2)->nullable();
        $table->decimal('shipping_cost', 15, 2)->nullable();
        $table->decimal('total_amount', 15, 2)->nullable();

        $table->text('notes')->nullable();
        $table->string('status')->default('draft');

        // Payment details
        $table->string('payment_status')->default('pending');
        $table->string('payment_terms')->nullable();
        $table->date('payment_due_date')->nullable();
        $table->decimal('paid_amount', 15, 2)->default(0);

        // Shipping details
        $table->text('shipping_address')->nullable();
        $table->string('shipping_method')->nullable();
        $table->string('tracking_number')->nullable();
        $table->date('expected_delivery_date')->nullable();
        $table->date('actual_delivery_date')->nullable();

        // Order fulfillment
        $table->string('fulfillment_status')->default('pending');
        $table->unsignedBigInteger('fulfilled_by_id')->nullable();
        $table->timestamp('fulfilled_at')->nullable();

        // Sales information
        $table->unsignedBigInteger('sales_person_id')->nullable();
        $table->decimal('profit_margin', 8, 2)->nullable();
        $table->decimal('total_cost', 15, 2)->nullable();
        $table->decimal('total_profit', 15, 2)->nullable();

        $table->timestamps();

        // Indexes
        $table->index('reference_no');
        $table->index('invoice_no');
        $table->index('order_no');
        $table->index('payment_status');
        $table->index('fulfillment_status');

        // Foreign keys
        $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
        $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');

        $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->softDeletes();

            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('set null');
      });

     Schema::create('sale_products', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('sale_id');
        $table->unsignedBigInteger('product_id');
        $table->integer('quantity');
        $table->unsignedBigInteger('unit_id');
        $table->decimal('weight', 8, 2)->nullable();

        // Pricing details
        $table->decimal('unit_price', 15, 4)->nullable();
        $table->decimal('unit_cost', 15, 4)->nullable();
        $table->decimal('tax_rate', 8, 2)->nullable();
        $table->decimal('tax_amount', 15, 2)->nullable();
        $table->decimal('discount_rate', 8, 2)->nullable();
        $table->decimal('discount_amount', 15, 2)->nullable();
        $table->decimal('subtotal', 15, 2)->nullable();
        $table->decimal('profit', 15, 2)->nullable();

        // Fulfillment information
        $table->integer('fulfilled_quantity')->default(0);
        $table->boolean('is_fully_fulfilled')->default(false);

        // Return information
        $table->integer('returned_quantity')->default(0);
        $table->text('return_reason')->nullable();

        // Price list reference (will be added later when price_lists table exists)
        // $table->unsignedBigInteger('price_list_id')->nullable();

        $table->timestamps();

        $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
        $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        $table->foreign('unit_id')->references('id')->on('units')->onDelete('restrict');

         $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->softDeletes();

            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('set null');

      });

    Schema::create('sale_attachments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('sale_id');
        $table->string('file_path');
        $table->timestamps();

        $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');

          $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->softDeletes();

            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('set null');
      });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('sales');
    Schema::dropIfExists('sale_products');
    Schema::dropIfExists('sale_attachments');
  }
};
