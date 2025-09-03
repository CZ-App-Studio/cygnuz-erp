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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('restrict');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->integer('quantity');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->foreignId('unit_id')->constrained('units')->onDelete('restrict');
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('transaction_type'); // purchase, sale, transfer_in, transfer_out, adjustment, etc.
            $table->unsignedBigInteger('reference_id'); // ID of the related transaction (purchase_id, sale_id, etc.)
            $table->string('reference_type'); // Model name of the transaction (Purchase, Sale, etc.)
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['warehouse_id', 'product_id']);
            $table->index(['transaction_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
