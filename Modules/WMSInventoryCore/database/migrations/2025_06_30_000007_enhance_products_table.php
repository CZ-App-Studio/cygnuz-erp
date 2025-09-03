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
        Schema::table('products', function (Blueprint $table) {
            // Cost and pricing fields
            $table->decimal('cost_price', 15, 4)->nullable()->after('alert_on');
            $table->decimal('selling_price', 15, 4)->nullable()->after('cost_price');

            // Inventory management fields
            $table->decimal('min_stock_level', 10, 2)->nullable()->after('selling_price');
            $table->decimal('max_stock_level', 10, 2)->nullable()->after('min_stock_level');
            $table->decimal('reorder_point', 10, 2)->nullable()->after('max_stock_level');
            $table->decimal('safety_stock', 10, 2)->nullable()->after('reorder_point');

            // Physical attributes
            $table->decimal('weight', 10, 3)->nullable()->after('safety_stock');
            $table->decimal('width', 10, 3)->nullable()->after('weight');
            $table->decimal('height', 10, 3)->nullable()->after('width');
            $table->decimal('length', 10, 3)->nullable()->after('height');

            // Additional tracking options
            $table->boolean('track_serial_number')->default(false)->after('track_quantity');
            $table->boolean('track_batch')->default(false)->after('track_serial_number');
            $table->boolean('track_expiry')->default(false)->after('track_batch');

            // Warehouse handling attributes
            $table->integer('lead_time_days')->nullable()->after('track_expiry');
            $table->boolean('is_returnable')->default(true)->after('lead_time_days');
            $table->boolean('is_purchasable')->default(true)->after('is_returnable');
            $table->boolean('is_sellable')->default(true)->after('is_purchasable');

            // Multiple barcodes support
            $table->json('additional_barcodes')->nullable()->after('barcode');

            // Indexes for better performance
            $table->index('sku');
            $table->index('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['sku']);
            $table->dropIndex(['barcode']);

            $table->dropColumn([
                'cost_price',
                'selling_price',
                'min_stock_level',
                'max_stock_level',
                'reorder_point',
                'safety_stock',
                'weight',
                'width',
                'height',
                'length',
                'track_serial_number',
                'track_batch',
                'track_expiry',
                'lead_time_days',
                'is_returnable',
                'is_purchasable',
                'is_sellable',
                'additional_barcodes',
            ]);
        });
    }
};
