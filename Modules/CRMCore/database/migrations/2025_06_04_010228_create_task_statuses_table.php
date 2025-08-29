<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('task_statuses', function (Blueprint $table) {
      $table->id();
      $table->string('name')->unique();
      $table->string('color', 20)->nullable()->comment('Hex or CSS color for UI');
      $table->unsignedInteger('position')->default(0)->comment('For ordering statuses');
      $table->boolean('is_default')->default(false)->comment('The default status for new tasks');
      $table->boolean('is_completed_status')->default(false)->comment('Indicates this status means the task is done');
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('task_statuses');
  }
};
