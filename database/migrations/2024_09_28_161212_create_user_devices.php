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
    Schema::create('user_devices', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id');
      $table->foreign('user_id')->references('id')->on('users');
      $table->string('device_type');
      $table->string('device_id');
      $table->string('brand');
      $table->string('board');
      $table->string('sdk_version');
      $table->string('model');
      $table->string('token')->unique();
      
      // Device lock and status fields
      $table->enum('status', ['active', 'inactive', 'blocked'])->default('inactive');
      $table->timestamp('last_login_at')->nullable();
      $table->timestamp('activated_at')->nullable();
      $table->integer('failed_attempts')->default(0);
      $table->timestamp('blocked_until')->nullable();
      $table->string('blocked_reason')->nullable();
      
      $table->string('app_version')->nullable();
      $table->integer('battery_percentage')->default(0);
      $table->boolean('is_charging')->default(false);
      $table->boolean('is_online')->default(0);
      $table->boolean('is_gps_on')->default(0);
      $table->boolean('is_wifi_on')->default(0);
      $table->boolean('is_mock')->default(0);
      $table->integer('signal_strength')->default(0);

      $table->string('ip_address')->nullable();
      $table->string('address')->nullable();

      //Location Info
      $table->decimal('latitude', 10, 8)->nullable();
      $table->decimal('longitude', 11, 8)->nullable();
      $table->decimal('bearing', 11, 8)->nullable();

      $table->decimal('horizontalAccuracy', 11, 8)->nullable();

      $table->decimal('altitude', 11, 8)->nullable();
      $table->decimal('verticalAccuracy', 11, 8)->nullable();

      $table->decimal('course', 11, 8)->nullable();
      $table->decimal('courseAccuracy', 11, 8)->nullable();

      $table->decimal('speed', 11, 8)->nullable();
      $table->decimal('speedAccuracy', 11, 8)->nullable();
      //Location Info End

      $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
      $table->foreignId('updated_by_id')->nullable()->constrained('users')->onDelete('set null');
      $table->string('tenant_id', 191)->nullable();
      $table->softDeletes();
      $table->timestamps();
      
      // Indexes for performance and constraints
      $table->index(['user_id', 'status']);
      $table->unique(['user_id', 'device_id']); // One device per user constraint
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('user_devices');
  }
};
