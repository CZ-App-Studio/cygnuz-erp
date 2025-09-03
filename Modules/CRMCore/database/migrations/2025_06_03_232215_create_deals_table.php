<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('code')->nullable()->unique();
            $table->decimal('value', 15, 2)->default(0.00);
            $table->date('expected_close_date')->nullable();
            $table->date('actual_close_date')->nullable();
            $table->unsignedTinyInteger('probability')->nullable()->comment('Percentage chance of winning (0-100)');

            $table->foreignId('pipeline_id')->constrained('deal_pipelines')->onDelete('restrict');
            $table->foreignId('deal_stage_id')->constrained('deal_stages')->onDelete('restrict');

            // A deal is typically primarily associated with one company and one main contact from that company
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('set null');
            // You could add a pivot table later if a deal needs multiple contacts (e.g., deal_contact_pivot)

            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('currency', 3)->default('USD');
            $table->text('lost_reason')->nullable();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Standard columns
            $table->string('tenant_id', 191)->nullable()->index();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            // Add indexes for performance
            $table->index('won_at');
            $table->index('lost_at');
            $table->index('closed_at');
            $table->index('expected_close_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
