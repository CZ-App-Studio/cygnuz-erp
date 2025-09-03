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
        Schema::table('adjustment_types', function (Blueprint $table) {
            $table->enum('effect', ['increase', 'decrease'])->default('decrease')->after('code');
            $table->text('description')->nullable()->after('effect');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adjustment_types', function (Blueprint $table) {
            $table->dropColumn(['effect', 'description']);
        });
    }
};
