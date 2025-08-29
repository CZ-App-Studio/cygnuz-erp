<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->longText('content')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('type', ['general', 'important', 'event', 'policy', 'update'])->default('general');
            $table->enum('target_audience', ['all', 'departments', 'teams', 'specific_users'])->default('all');
            $table->boolean('send_email')->default(false);
            $table->boolean('send_notification')->default(true);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('requires_acknowledgment')->default(false);
            $table->datetime('publish_date')->nullable();
            $table->datetime('expiry_date')->nullable();
            $table->enum('status', ['draft', 'published', 'scheduled', 'expired', 'archived'])->default('draft');
            $table->string('attachment')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['status', 'publish_date', 'expiry_date']);
            $table->index('priority');
            $table->index('is_pinned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};