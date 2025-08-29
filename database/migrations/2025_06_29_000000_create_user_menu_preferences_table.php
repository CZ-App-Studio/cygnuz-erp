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
        Schema::create('user_menu_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('menu_slug');
            $table->boolean('is_pinned')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            // Unique constraint to prevent duplicate entries
            $table->unique(['user_id', 'menu_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_menu_preferences');
    }
};
