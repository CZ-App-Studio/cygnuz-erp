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
        Schema::create('settings_metadata', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100);
            $table->string('key', 255)->unique();
            $table->string('label', 255);
            $table->string('type', 50);
            $table->string('input_type', 50)->default('text');
            $table->json('options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->text('help_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->text('default_value')->nullable();
            $table->timestamps();

            $table->index(['category', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings_metadata');
    }
};
