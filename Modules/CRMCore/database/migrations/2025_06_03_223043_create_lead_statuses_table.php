<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('lead_statuses', function (Blueprint $table) {
      $table->id();
      $table->string('name')->unique();
      $table->string('color', 20)->nullable()->comment('Hex or CSS color for UI, e.g., #4CAF50');
      $table->unsignedInteger('position')->default(0)->comment('For ordering statuses in a Kanban view');
      $table->boolean('is_default')->default(false)->comment('The default status for new leads');
      $table->boolean('is_final')->default(false)->comment('Indicates a final state, e.g., Converted or Unqualified');
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('lead_statuses');
  }
};
