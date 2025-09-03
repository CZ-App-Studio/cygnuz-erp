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
        if (! Schema::hasTable('tax_rates')) {
            Schema::create('tax_rates', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->decimal('rate', 10, 4); // e.g., 5.0000 for 5%, 18.5000 for 18.5%
                $table->enum('type', ['percentage', 'fixed'])->default('percentage');
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->string('tax_authority')->nullable(); // e.g., "Federal", "State", "GST", "VAT"
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->unsignedBigInteger('updated_by_id')->nullable();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index(['is_active', 'is_default']);
                $table->index('name');
                $table->index('tenant_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
