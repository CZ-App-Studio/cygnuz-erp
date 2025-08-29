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
        Schema::create('ai_request_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('module_name')->index();
            $table->string('operation_type')->index();
            $table->unsignedBigInteger('model_id')->nullable()->index();
            $table->string('provider_name')->nullable();
            $table->string('model_name')->nullable();
            
            // Request details
            $table->text('request_prompt');
            $table->json('request_options')->nullable();
            $table->string('request_ip')->nullable();
            $table->string('request_user_agent')->nullable();
            
            // Response details
            $table->text('response_content')->nullable();
            $table->json('response_metadata')->nullable();
            
            // Usage and performance
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('cost', 10, 6)->default(0);
            $table->integer('processing_time_ms')->nullable();
            
            // Status and error tracking
            $table->enum('status', ['pending', 'success', 'error', 'timeout'])->default('pending');
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();
            
            // Audit fields
            $table->boolean('is_flagged')->default(false);
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('created_at');
            $table->index(['module_name', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('is_flagged');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_request_logs');
    }
};