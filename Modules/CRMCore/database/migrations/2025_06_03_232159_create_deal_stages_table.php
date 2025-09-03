<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('deal_pipelines')->onDelete('cascade');
            $table->string('name');
            $table->string('color', 20)->nullable()->comment('Hex or CSS color for UI');
            $table->unsignedInteger('position')->default(0)->comment('For ordering stages in Kanban');
            $table->boolean('is_default_for_pipeline')->default(false)->comment('Default stage for new deals in this pipeline');
            $table->boolean('is_won_stage')->default(false)->comment('Indicates this is a "Closed Won" stage');
            $table->boolean('is_lost_stage')->default(false)->comment('Indicates this is a "Closed Lost" stage');
            $table->timestamps();

            $table->unique(['pipeline_id', 'name']); // Stage name should be unique within a pipeline
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_stages');
    }
};
