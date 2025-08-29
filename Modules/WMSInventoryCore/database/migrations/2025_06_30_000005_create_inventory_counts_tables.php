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
    Schema::create('inventory_counts', function (Blueprint $table) {
      $table->id();
      $table->string('reference_no');
      $table->date('count_date');
      $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('restrict');
      $table->foreignId('zone_id')->nullable()->constrained('warehouse_zones')->onDelete('set null');
      $table->string('count_type'); // 'full', 'partial', 'cycle'
      $table->string('status')->default('draft'); // draft, in_progress, completed, cancelled
      $table->text('notes')->nullable();
      $table->dateTime('started_at')->nullable();
      $table->dateTime('completed_at')->nullable();
      $table->unsignedBigInteger('created_by_id')->nullable();
      $table->unsignedBigInteger('updated_by_id')->nullable();
      $table->softDeletes();
      $table->timestamps();
    });

    Schema::create('inventory_count_items', function (Blueprint $table) {
      $table->id();
      $table->foreignId('inventory_count_id')->constrained('inventory_counts')->onDelete('cascade');
      $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
      $table->foreignId('bin_location_id')->nullable()->constrained('bin_locations')->onDelete('set null');
      $table->foreignId('batch_id')->nullable()->constrained('product_batches')->onDelete('set null');
      $table->integer('expected_quantity');
      $table->integer('counted_quantity')->nullable();
      $table->integer('difference')->nullable();
      $table->foreignId('unit_id')->constrained('units')->onDelete('restrict');
      $table->string('status')->default('pending'); // pending, counted, adjusted
      $table->boolean('is_adjusted')->default(false);
      $table->unsignedBigInteger('adjustment_id')->nullable();
      $table->unsignedBigInteger('counted_by_id')->nullable();
      $table->unsignedBigInteger('created_by_id')->nullable();
      $table->unsignedBigInteger('updated_by_id')->nullable();
      $table->softDeletes();
      $table->timestamps();

      $table->index(['inventory_count_id', 'product_id']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('inventory_count_items');
    Schema::dropIfExists('inventory_counts');
  }
};
