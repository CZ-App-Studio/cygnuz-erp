<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('code')->nullable()->unique();
            $table->dateTime('due_date')->nullable()->index();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('reminder_at')->nullable()->comment('When to send a reminder notification');

            $table->foreignId('task_status_id')->constrained('task_statuses')->onDelete('restrict');
            $table->foreignId('task_priority_id')->nullable()->constrained('task_priorities')->onDelete('set null');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Polymorphic relationship for "taskable" items (Contact, Company, Lead, Deal)
            $table->nullableMorphs('taskable'); // Creates taskable_id (unsignedBigInteger) and taskable_type (string)

            // Standard columns
            $table->string('tenant_id', 191)->nullable()->index();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['taskable_id', 'taskable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_tasks');
    }
};
