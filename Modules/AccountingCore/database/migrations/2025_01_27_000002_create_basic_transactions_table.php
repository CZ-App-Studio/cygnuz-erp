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
        Schema::create('basic_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('category_id');
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->string('reference_number', 100)->nullable();
            $table->string('attachment_path', 500)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['type', 'transaction_date']);
            $table->index('category_id');
            $table->index('transaction_date');
            $table->index('created_by_id');

            // Foreign keys
            $table->foreign('category_id')->references('id')->on('basic_transaction_categories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('basic_transactions');
    }
};
