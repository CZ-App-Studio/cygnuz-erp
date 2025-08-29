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
    Schema::create('contacts', function (Blueprint $table) {
      $table->id();
      $table->string('first_name');
      $table->string('last_name')->nullable();
      $table->string('email_primary')->nullable()->index(); // Primary email, might be unique per tenant
      $table->string('email_secondary')->nullable()->index();

      $table->string('code')->nullable()->unique();

      $table->string('phone_primary')->nullable()->index();
      $table->string('phone_mobile')->nullable();
      $table->string('phone_office')->nullable(); // Office direct line if different from company

      $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
      $table->string('job_title')->nullable();
      $table->string('department')->nullable();

      // Address Fields for Contact (can be different from Company)
      $table->string('address_street')->nullable();
      $table->string('address_city')->nullable();
      $table->string('address_state')->nullable();
      $table->string('address_postal_code')->nullable();
      $table->string('address_country')->nullable();

      $table->date('date_of_birth')->nullable();
      $table->string('lead_source_name')->nullable()->index(); // e.g., "Website", "Referral", "Cold Call". Can be backed by PHP Enum.
      $table->text('description')->nullable(); // General notes about the contact

      $table->boolean('do_not_email')->default(false);
      $table->boolean('do_not_call')->default(false);
      $table->boolean('is_primary_contact_for_company')->default(false)->comment('Indicates if this is the main contact for the linked company');
      $table->boolean('is_active')->default(true)->index();


      // Standard columns from your starter kit
      $table->string('tenant_id', 191)->nullable()->index();
      $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('Primary CRM user responsible for this contact');
      $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
      $table->foreignId('updated_by_id')->nullable()->constrained('users')->onDelete('set null');
      $table->softDeletes();
      $table->timestamps();

      // Optional: Composite unique key if email must be unique within a tenant (if tenant_id is used actively)
      // if (Schema::hasColumn('contacts', 'tenant_id')) {
      //     $table->unique(['tenant_id', 'email_primary'], 'tenant_primary_email_unique');
      // }
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('contacts');
  }
};
