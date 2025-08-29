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
    Schema::create('warehouse_zones', function (Blueprint $table) {
      $table->id();
      $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
      $table->string('name');
      $table->string('code');
      $table->text('description')->nullable();
      $table->string('zone_type')->nullable(); // Receiving, Shipping, Storage, Picking, etc.
      $table->string('status')->default('active');
      $table->unsignedBigInteger('created_by_id')->nullable();
      $table->unsignedBigInteger('updated_by_id')->nullable();
      $table->softDeletes();
      $table->timestamps();

      $table->unique(['warehouse_id', 'code']);
    });

    Schema::create('bin_locations', function (Blueprint $table) {
      $table->id();
      $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
      $table->foreignId('zone_id')->nullable()->constrained('warehouse_zones')->onDelete('set null');
      $table->string('name');
      $table->string('code');
      $table->string('aisle')->nullable();
      $table->string('rack')->nullable();
      $table->string('shelf')->nullable();
      $table->string('bin')->nullable();
      $table->decimal('max_weight', 10, 2)->nullable();
      $table->decimal('max_volume', 10, 2)->nullable();
      $table->string('status')->default('active');
      $table->unsignedBigInteger('created_by_id')->nullable();
      $table->unsignedBigInteger('updated_by_id')->nullable();
      $table->softDeletes();
      $table->timestamps();

      $table->unique(['warehouse_id', 'code']);
    });

    Schema::create('product_bin_locations', function (Blueprint $table) {
      $table->id();
      $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
      $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
      $table->foreignId('bin_location_id')->constrained('bin_locations')->onDelete('cascade');
      $table->integer('quantity');
      $table->foreignId('unit_id')->constrained('units')->onDelete('restrict');
      $table->foreignId('batch_id')->nullable()->constrained('product_batches')->onDelete('set null');
      $table->unsignedBigInteger('created_by_id')->nullable();
      $table->unsignedBigInteger('updated_by_id')->nullable();
      $table->softDeletes();
      $table->timestamps();

      $table->unique(['product_id', 'warehouse_id', 'bin_location_id', 'batch_id'], 'product_bin_batch_unique');
    });

    // Update the products table to remove rack_location since we now have a more comprehensive system
    Schema::table('products', function (Blueprint $table) {
      $table->dropColumn('rack_location');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('products', function (Blueprint $table) {
      $table->string('rack_location')->nullable()->after('description');
    });

    Schema::dropIfExists('product_bin_locations');
    Schema::dropIfExists('bin_locations');
    Schema::dropIfExists('warehouse_zones');
  }
};
