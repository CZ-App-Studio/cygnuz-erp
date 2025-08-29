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
    Schema::create('transfers', function (Blueprint $table) {
      $table->id();
      $table->date('date');
      $table->string('code')->nullable();
      $table->text('notes')->nullable();
      $table->unsignedBigInteger('from_warehouse_id');
      $table->unsignedBigInteger('to_warehouse_id');
      $table->string('status')->default('draft');
      $table->unsignedBigInteger('created_by_id')->nullable();
      $table->unsignedBigInteger('updated_by_id')->nullable();
      $table->softDeletes();
      $table->timestamps();

      $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
      $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
    });

    Schema::create('transfer_products', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('transfer_id');
      $table->unsignedBigInteger('product_id');
      $table->integer('quantity');
      $table->unsignedBigInteger('unit_id');
      $table->decimal('weight', 8, 2)->nullable();
      $table->unsignedBigInteger('created_by_id')->nullable();
      $table->unsignedBigInteger('updated_by_id')->nullable();
      $table->softDeletes();
      $table->timestamps();

      $table->foreign('transfer_id')->references('id')->on('transfers')->onDelete('cascade');
      $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
      $table->foreign('unit_id')->references('id')->on('units')->onDelete('restrict');
    });

    Schema::create('transfer_attachments', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('transfer_id');
      $table->string('file_path');
      $table->unsignedBigInteger('created_by_id')->nullable();
      $table->unsignedBigInteger('updated_by_id')->nullable();
      $table->softDeletes();
      $table->timestamps();

      $table->foreign('transfer_id')->references('id')->on('transfers')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('transfers');
    Schema::dropIfExists('transfer_products');
    Schema::dropIfExists('transfer_attachments');
  }
};
