<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false)->comment('The default pipeline for new deals');
            $table->unsignedInteger('position')->default(0)->comment('Order of pipelines if multiple are displayed');

            // Standard columns
            $table->string('tenant_id', 191)->nullable()->index();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            // No SoftDeletes for pipelines usually, as deleting one with stages/deals can be complex.
            // Better to mark as inactive or archive. Add if your policy differs.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_pipelines');
    }
};
