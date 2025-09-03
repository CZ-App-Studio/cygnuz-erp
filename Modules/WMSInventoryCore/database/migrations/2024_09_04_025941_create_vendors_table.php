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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('website')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->string('payment_terms')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->decimal('minimum_order_value', 10, 2)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('status');
            $table->index('created_by_id');
            $table->index('updated_by_id');
            $table->index(['status', 'deleted_at']);
            $table->index('email');
            $table->index('company_name');
            $table->index('name');

            // Foreign key constraints
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
