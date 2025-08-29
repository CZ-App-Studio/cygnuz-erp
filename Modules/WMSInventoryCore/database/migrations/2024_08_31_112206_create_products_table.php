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

    Schema::create('products', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('code', 255);
      $table->string('description')->nullable();
      $table->string('rack_location')->nullable();
      $table->string('sku')->nullable();
      $table->string('barcode')->nullable();
      $table->string('image')->nullable();
      $table->foreignId('unit_id')->references('id')->on('units');
      $table->foreignId('category_id')->references('id')->on('categories');
      $table->boolean('track_weight')->default(false);
      $table->boolean('track_quantity')->default(false);
      $table->decimal('alert_on', 8, 2)->nullable();
      $table->string('status')->default('active');
      $table->unsignedBigInteger('created_by_id')->nullable();
      $table->unsignedBigInteger('updated_by_id')->nullable();
      $table->softDeletes();
      $table->timestamps();

      $table->unique(['code']);
    });

    Schema::create('variants', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->enum('status', ['active', 'inactive'])->default('active');
      $table->foreignId('product_id')->references('id')->on('products')->onDelete('cascade');
      $table->unsignedBigInteger('created_by_id')->nullable();
      $table->unsignedBigInteger('updated_by_id')->nullable();
      $table->softDeletes();
      $table->timestamps();

    });

    Schema::create('options', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->foreignId('variant_id')->references('id')->on('variants')->onDelete('cascade');
      $table->enum('status', ['active', 'inactive'])->default('active');
      $table->unsignedBigInteger('created_by_id')->nullable();
      $table->unsignedBigInteger('updated_by_id')->nullable();
      $table->softDeletes();
      $table->timestamps();

    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('products');
    Schema::dropIfExists('variants');
    Schema::dropIfExists('options');
  }
};
