<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_id');
            $table->enum('shared_with_type', ['user', 'role', 'public']);
            $table->unsignedBigInteger('shared_with_id')->nullable();
            $table->json('permissions'); // ['view', 'download', 'edit']
            $table->timestamp('expires_at')->nullable();
            $table->string('share_token', 100)->unique()->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('max_downloads')->nullable();
            $table->unsignedBigInteger('created_by_id');
            $table->timestamps();
            
            $table->index(['file_id', 'shared_with_type', 'shared_with_id']);
            $table->index('share_token');
            $table->index('expires_at');
            
            $table->foreign('file_id')->references('id')->on('files')->onDelete('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_shares');
    }
};