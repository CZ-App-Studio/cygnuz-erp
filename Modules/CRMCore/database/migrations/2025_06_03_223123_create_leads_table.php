<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('leads', function (Blueprint $table) {
      $table->id();
      $table->string('title');
      $table->text('description')->nullable();
      $table->string('code')->nullable()->unique();

      // Contact info is stored directly on the lead for quick capture
      $table->string('contact_name')->nullable();
      $table->string('contact_email')->nullable()->index();
      $table->string('contact_phone')->nullable();
      $table->string('company_name')->nullable();

      // Value & Source
      $table->decimal('value', 15, 2)->nullable()->comment('Estimated deal value');
      $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->onDelete('set null');

      // Status & Assignment
      $table->foreignId('lead_status_id')->constrained('lead_statuses')->onDelete('restrict');
      $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null');

      // Conversion Tracking
      $table->timestamp('converted_at')->nullable();
      $table->foreignId('converted_to_contact_id')->nullable()->constrained('contacts')->onDelete('set null');
      // We add the deal ID column now, anticipating the Deals module from Phase 3
      // Ensure a 'deals' table will exist or make this nullable without constraint initially.


      // Standard columns from your starter kit
      $table->string('tenant_id', 191)->nullable()->index();
      $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
      $table->foreignId('updated_by_id')->nullable()->constrained('users')->onDelete('set null');
      $table->softDeletes();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('leads');
  }
};
