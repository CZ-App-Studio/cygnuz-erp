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
        Schema::create('accounting_migrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('basic_transaction_id');
            $table->unsignedBigInteger('journal_entry_id');
            $table->timestamp('migrated_at');
            $table->timestamps();

            // Indexes
            $table->unique('basic_transaction_id');
            $table->index('journal_entry_id');

            // Foreign keys
            $table->foreign('basic_transaction_id')->references('id')->on('basic_transactions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_migrations');
    }
};
