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
        Schema::table('inventories', function (Blueprint $table) {
            // Add fields to track different inventory statuses
            $table->decimal('weight', 10, 2)->nullable()->after('unit_id');
            $table->decimal('cost_price', 15, 4)->nullable()->after('weight');
            $table->decimal('value', 15, 2)->storedAs('stock_level * cost_price')->nullable()->after('cost_price');

            // Add fields to track quality status
            $table->integer('damaged_quantity')->default(0)->after('value');
            $table->integer('quarantine_quantity')->default(0)->after('damaged_quantity');
            $table->integer('in_transit_quantity')->default(0)->after('quarantine_quantity');

            // Add fields for stock aging
            $table->date('last_count_date')->nullable()->after('in_transit_quantity');
            $table->date('last_movement_date')->nullable()->after('last_count_date');

            // Add fields for low stock alerts
            $table->boolean('low_stock_alert_sent')->default(false)->after('last_movement_date');
            $table->timestamp('last_alert_sent_at')->nullable()->after('low_stock_alert_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn([
                'weight',
                'cost_price',
                'value',
                'damaged_quantity',
                'quarantine_quantity',
                'in_transit_quantity',
                'last_count_date',
                'last_movement_date',
                'low_stock_alert_sent',
                'last_alert_sent_at',
            ]);
        });
    }
};
