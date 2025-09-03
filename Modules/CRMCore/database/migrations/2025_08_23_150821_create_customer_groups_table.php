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
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->integer('priority')->default(0);
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->string('payment_terms')->default('cod'); // cod, net30, net60, prepaid
            $table->boolean('priority_support')->default(false);
            $table->boolean('free_shipping')->default(false);
            $table->decimal('min_order_amount', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);

            // Multi-tenancy
            $table->unsignedBigInteger('tenant_id')->nullable();

            // User tracking
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('is_active');
            $table->index('priority');
            $table->index(['code', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_groups');
    }
};
