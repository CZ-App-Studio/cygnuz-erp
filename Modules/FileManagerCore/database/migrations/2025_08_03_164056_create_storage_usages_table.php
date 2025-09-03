<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('storage_provider', 50);
            $table->unsignedBigInteger('used_space')->default(0); // in bytes
            $table->unsignedInteger('file_count')->default(0);
            $table->unsignedBigInteger('quota_limit')->nullable(); // in bytes
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'storage_provider'], 'unique_user_provider');
            $table->unique(['department_id', 'storage_provider'], 'unique_dept_provider');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // Note: department_id foreign key would depend on your department table structure
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_usages');
    }
};
