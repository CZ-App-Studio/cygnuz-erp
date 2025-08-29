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
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('type', 50); // e.g., 'openai', 'claude', 'gemini', 'azure-openai', etc.
            $table->string('endpoint_url', 500)->nullable();
            $table->text('api_key_encrypted')->nullable();
            $table->integer('max_requests_per_minute')->default(60);
            $table->integer('max_tokens_per_request')->default(4000);
            $table->decimal('cost_per_token', 10, 8)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(1);
            $table->json('configuration')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
