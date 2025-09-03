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
        Schema::create('ai_chats', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('chat_type')->default('general'); // general, support, technical, creative, etc.
            $table->string('status')->default('active'); // active, archived, deleted
            $table->json('settings')->nullable(); // Store chat-specific settings
            $table->json('context')->nullable(); // Store conversation context
            $table->integer('message_count')->default(0);
            $table->decimal('total_cost', 10, 6)->default(0);
            $table->integer('total_tokens')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('company_id');
            $table->index('status');
            $table->index('chat_type');
            $table->index('created_at');

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chats');
    }
};
