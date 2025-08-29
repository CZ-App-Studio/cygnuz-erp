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
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('ai_providers')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('model_identifier', 200);
            $table->string('type', 50); // e.g., 'text', 'embedding', 'image', 'audio', 'multimodal', 'code'
            $table->integer('max_tokens')->default(4000);
            $table->boolean('supports_streaming')->default(false);
            $table->decimal('cost_per_input_token', 10, 8)->nullable();
            $table->decimal('cost_per_output_token', 10, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('configuration')->nullable();
            $table->timestamps();
            
            $table->index(['provider_id', 'is_active']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
