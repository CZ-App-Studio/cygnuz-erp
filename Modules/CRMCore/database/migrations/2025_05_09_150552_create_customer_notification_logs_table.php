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
    Schema::create('customer_notification_logs', function (Blueprint $table) {
      $table->id();
      $table->string('title');
      $table->text('message');
      $table->json('target_criteria')->comment('Stores selected criteria for this batch');
      $table->string('target_type')->comment('e.g., all, tier, specific_users');
      $table->enum('status', ['queued', 'processing', 'sent', 'failed'])->default('queued');
      $table->timestamp('sent_at')->nullable();
      $table->unsignedInteger('estimated_recipients')->nullable(); // Can be set during query
      $table->text('error_message')->nullable();
      $table->string('link')->nullable();
      $table->string('icon')->nullable();

      $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null'); // Admin who sent
      $table->string('tenant_id', 191)->nullable()->index(); // If applicable
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('customer_notification_logs');
  }
};
