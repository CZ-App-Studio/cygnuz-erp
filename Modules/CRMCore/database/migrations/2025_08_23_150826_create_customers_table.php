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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->string('code')->unique();
            $table->unsignedBigInteger('customer_group_id')->nullable();

            // Purchase history
            $table->decimal('lifetime_value', 15, 2)->default(0);
            $table->timestamp('first_purchase_date')->nullable();
            $table->timestamp('last_purchase_date')->nullable();
            $table->integer('purchase_count')->default(0);
            $table->decimal('average_order_value', 15, 2)->default(0);

            // Credit management
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('credit_used', 15, 2)->default(0);
            $table->string('payment_terms')->default('cod'); // cod, net30, net60, prepaid

            // Discounts and tax
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->boolean('tax_exempt')->default(false);
            $table->string('tax_number')->nullable();
            $table->string('business_registration')->nullable();

            // Additional info
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->string('preferred_payment_method')->nullable();
            $table->string('preferred_delivery_method')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_blacklisted')->default(false);
            $table->text('blacklist_reason')->nullable();

            $table->timestamp('converted_to_customer_at')->nullable();

            // Multi-tenancy
            $table->unsignedBigInteger('tenant_id')->nullable();

            // User tracking
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('contact_id')->references('id')->on('contacts');
            $table->foreign('customer_group_id')->references('id')->on('customer_groups')->nullOnDelete();

            // Indexes
            $table->index('tenant_id');
            $table->index('is_active');
            $table->index('is_blacklisted');
            $table->index(['code', 'tenant_id']);
            $table->index(['contact_id', 'tenant_id']);
            $table->index('last_purchase_date');
            $table->index('lifetime_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
