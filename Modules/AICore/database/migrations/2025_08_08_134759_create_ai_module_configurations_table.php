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
        Schema::create('ai_module_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('module_name')->unique(); // e.g., 'AIChat', 'DocumentSummarizerAI', etc.
            $table->string('module_display_name'); // Human-readable name
            $table->text('module_description')->nullable();
            $table->unsignedBigInteger('default_provider_id')->nullable();
            $table->unsignedBigInteger('default_model_id')->nullable();
            $table->json('allowed_providers')->nullable(); // Array of allowed provider IDs
            $table->json('allowed_models')->nullable(); // Array of allowed model IDs
            $table->json('settings')->nullable(); // Module-specific settings
            $table->integer('max_tokens_limit')->default(4096);
            $table->decimal('temperature_default', 3, 2)->default(0.7);
            $table->boolean('streaming_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // For ordering in UI
            $table->timestamps();

            // Foreign keys
            $table->foreign('default_provider_id')->references('id')->on('ai_providers')->onDelete('set null');
            $table->foreign('default_model_id')->references('id')->on('ai_models')->onDelete('set null');

            // Indexes
            $table->index('module_name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_module_configurations');
    }
};
