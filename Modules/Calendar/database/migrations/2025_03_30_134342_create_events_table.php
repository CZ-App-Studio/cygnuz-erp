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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start');
            $table->dateTime('end')->nullable(); // Nullable for single-point-in-time events
            $table->boolean('all_day')->default(false);
            $table->string('color', 7)->nullable(); // Store hex color code, e.g., #FF5733
            $table->string('tenant_id', 191)->nullable()->index(); // Consistent with your other tables
            $table->string('event_type', 50);
            $table->string('location')->nullable();
            $table->nullableMorphs('related'); // This creates related_type and related_id columns
            $table->string('meeting_link')->nullable();

            // Foreign key for the creator (soft relationships)
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        // Pivot table for many-to-many relationship between events and attendees (users)
        Schema::create('event_user', function (Blueprint $table) {
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('user_id');
            $table->primary(['event_id', 'user_id']); // Composite primary key
            // Add timestamps if you want to track when a user was added to an event
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_user');
        Schema::dropIfExists('events');
    }
};
