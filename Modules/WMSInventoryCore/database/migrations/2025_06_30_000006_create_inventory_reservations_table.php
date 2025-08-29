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
    Schema::create('inventory_reservations', function (Blueprint $table) {
      $table->id();
      $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
      $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
      $table->foreignId('batch_id')->nullable()->constrained('product_batches')->onDelete('set null');
      $table->foreignId('bin_location_id')->nullable()->constrained('bin_locations')->onDelete('set null');
      $table->integer('quantity');
      $table->foreignId('unit_id')->constrained('units')->onDelete('restrict');
      $table->string('reservation_type'); // sale, transfer, production, etc.
      $table->unsignedBigInteger('reference_id'); // ID of the related document
      $table->string('reference_type'); // Model name of the related document
      $table->dateTime('reserved_until')->nullable();
      $table->string('status')->default('active'); // active, fulfilled, expired, cancelled
      $table->unsignedBigInteger('created_by_id')->nullable();
      $table->unsignedBigInteger('updated_by_id')->nullable();
      $table->softDeletes();
      $table->timestamps();

      $table->index(['warehouse_id', 'product_id']);
      $table->index(['reservation_type', 'reference_id']);
    });

    // Add available_quantity column to inventories table
    Schema::table('inventories', function (Blueprint $table) {
      $table->integer('reserved_quantity')->default(0)->after('stock_level');
      $table->integer('available_quantity')->storedAs('stock_level - reserved_quantity')->after('reserved_quantity');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('inventories', function (Blueprint $table) {
      $table->dropColumn(['reserved_quantity', 'available_quantity']);
    });

    Schema::dropIfExists('inventory_reservations');
  }
};
