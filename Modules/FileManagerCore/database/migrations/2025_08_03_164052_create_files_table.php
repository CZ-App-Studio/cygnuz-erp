<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('original_name');
            $table->text('path');
            $table->string('disk', 50)->default('public');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size'); // in bytes
            $table->unsignedBigInteger('category_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->enum('visibility', ['public', 'private', 'internal'])->default('private');
            $table->string('thumbnail_path', 500)->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('storage_provider', 50)->default('local');
            
            // Polymorphic relations
            $table->string('attachable_type')->nullable();
            $table->unsignedBigInteger('attachable_id')->nullable();
            
            // File status and versioning
            $table->enum('status', ['uploading', 'active', 'processing', 'archived', 'deleted', 'failed'])->default('active');
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('parent_file_id')->nullable();
            
            // Audit fields
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['attachable_type', 'attachable_id']);
            $table->index('category_id');
            $table->index('created_by_id');
            $table->index('mime_type');
            $table->index('storage_provider');
            $table->index('status');
            $table->index('created_at');
            $table->index('size');
            
            // Foreign keys
            $table->foreign('category_id')->references('id')->on('file_categories')->onDelete('set null');
            $table->foreign('parent_file_id')->references('id')->on('files')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};