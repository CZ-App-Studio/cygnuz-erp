<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_id');
            $table->unsignedInteger('version_number');
            $table->string('name');
            $table->text('path');
            $table->string('disk', 50);
            $table->unsignedBigInteger('size'); // in bytes
            $table->string('checksum', 64)->nullable();
            $table->text('change_description')->nullable();
            $table->unsignedBigInteger('created_by_id');
            $table->timestamps();
            
            $table->unique(['file_id', 'version_number']);
            $table->index('file_id');
            $table->index('created_by_id');
            
            $table->foreign('file_id')->references('id')->on('files')->onDelete('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_versions');
    }
};