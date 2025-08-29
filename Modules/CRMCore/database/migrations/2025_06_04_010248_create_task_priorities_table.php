<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('task_priorities', function (Blueprint $table) {
      $table->id();
      $table->string('name')->unique();
      $table->string('color', 20)->nullable()->comment('Hex or CSS color for UI');
      $table->unsignedInteger('level')->default(0)->comment('Numeric level for sorting by priority (e.g., 1=Low, 4=Urgent)');
      $table->boolean('is_default')->default(false)->comment('Default priority for new tasks');
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('task_priorities');
  }
};
