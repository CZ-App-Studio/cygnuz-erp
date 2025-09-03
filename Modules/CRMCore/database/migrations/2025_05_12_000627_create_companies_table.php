<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('website')->nullable();
            $table->string('phone_office')->nullable();
            $table->string('email_office')->nullable()->index();
            $table->string('code')->nullable()->unique();

            // Address Fields
            $table->string('address_street')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_state')->nullable();
            $table->string('address_postal_code')->nullable();
            $table->string('address_country')->nullable();

            $table->string('industry')->nullable()->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();

            $table->string('tenant_id', 191)->nullable()->index();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('Primary CRM user responsible for this company');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
