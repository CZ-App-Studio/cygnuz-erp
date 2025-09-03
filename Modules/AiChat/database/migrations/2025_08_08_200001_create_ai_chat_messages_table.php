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
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('role'); // user, assistant, system
            $table->text('content');
            $table->string('message_type')->default('text'); // text, code, image, file
            $table->json('metadata')->nullable(); // Store additional message data
            $table->unsignedBigInteger('model_id')->nullable();
            $table->unsignedBigInteger('provider_id')->nullable();
            $table->integer('prompt_tokens')->nullable();
            $table->integer('completion_tokens')->nullable();
            $table->integer('total_tokens')->nullable();
            $table->decimal('cost', 10, 6)->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->string('status')->default('sent'); // sent, delivered, read, failed
            $table->text('error_message')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_favorite')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('chat_id');
            $table->index('user_id');
            $table->index('role');
            $table->index('status');
            $table->index('created_at');
            $table->index(['chat_id', 'created_at']);

            // Foreign keys
            $table->foreign('chat_id')->references('id')->on('ai_chats')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('model_id')->references('id')->on('ai_models')->onDelete('set null');
            $table->foreign('provider_id')->references('id')->on('ai_providers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
