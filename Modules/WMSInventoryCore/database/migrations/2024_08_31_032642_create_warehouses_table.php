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
    Schema::create('warehouses', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('code', 255);
      $table->string('address');
      $table->string('phone_number');
      $table->string('email')->nullable();
      $table->string('logo')->nullable();
      $table->decimal('latitude', 16, 8)->nullable();
      $table->decimal('longitude', 16, 8)->nullable();
      $table->string('status')->default('active');
      $table->unsignedBigInteger('created_by_id')->nullable();
      $table->unsignedBigInteger('updated_by_id')->nullable();
      $table->softDeletes();
      $table->timestamps();

      $table->unique(['code']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('warehouses');
  }
};
