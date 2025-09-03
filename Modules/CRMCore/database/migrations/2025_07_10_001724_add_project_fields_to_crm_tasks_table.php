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
        Schema::table('crm_tasks', function (Blueprint $table) {

            // Add estimated_hours and actual_hours for project time tracking
            $table->decimal('estimated_hours', 8, 2)->nullable()->after('reminder_at');
            $table->decimal('actual_hours', 8, 2)->nullable()->after('estimated_hours');

            // Add task order for project task sequencing
            $table->integer('task_order')->nullable()->after('actual_hours');

            // Add parent_task_id for task hierarchies
            $table->unsignedBigInteger('parent_task_id')->nullable()->after('task_order');
            $table->index('parent_task_id');

            // Add is_milestone flag
            $table->boolean('is_milestone')->default(false)->after('parent_task_id');

            // Add time tracking fields
            $table->timestamp('time_started_at')->nullable()->after('is_milestone');
            $table->unsignedBigInteger('completed_by')->nullable()->after('time_started_at');
            $table->index('completed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_tasks', function (Blueprint $table) {
            $table->dropIndex(['parent_task_id']);
            $table->dropColumn([
                'estimated_hours',
                'actual_hours',
                'task_order',
                'parent_task_id',
                'is_milestone',
                'time_started_at',
                'completed_by',
            ]);
        });
    }
};
