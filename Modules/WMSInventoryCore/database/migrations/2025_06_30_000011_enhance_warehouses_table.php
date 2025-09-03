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
        Schema::table('warehouses', function (Blueprint $table) {
            // Contact information
            $table->string('contact_person')->nullable()->after('email');
            $table->string('alternate_phone')->nullable()->after('contact_person');

            // Location details
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('country')->nullable()->after('state');
            $table->string('postal_code')->nullable()->after('country');

            // Capacity details
            $table->decimal('total_area', 10, 2)->nullable()->after('longitude')->comment('in square meters/feet');
            $table->decimal('storage_capacity', 10, 2)->nullable()->after('total_area')->comment('in cubic meters/feet');
            $table->decimal('max_weight_capacity', 12, 2)->nullable()->after('storage_capacity')->comment('in kg/lbs');
            $table->integer('shelf_count')->nullable()->after('max_weight_capacity');
            $table->integer('rack_count')->nullable()->after('shelf_count');
            $table->integer('bin_count')->nullable()->after('rack_count');

            // Operational details
            $table->time('opening_time')->nullable()->after('bin_count');
            $table->time('closing_time')->nullable()->after('opening_time');
            $table->boolean('is_24_hours')->default(false)->after('closing_time');
            $table->json('operating_days')->nullable()->after('is_24_hours')->comment('Array of operating days [0-6], 0=Sunday');

            // Warehouse type and purpose
            $table->string('warehouse_type')->nullable()->after('operating_days')->comment('distribution, fulfillment, cold storage, etc.');
            $table->boolean('is_main')->default(false)->after('warehouse_type');
            $table->boolean('allow_negative_inventory')->default(false)->after('is_main');
            $table->boolean('requires_approval')->default(false)->after('allow_negative_inventory');

            // Indexes for better performance
            $table->index(['city', 'state', 'country']);
            $table->index('warehouse_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropIndex(['city', 'state', 'country']);
            $table->dropIndex(['warehouse_type']);

            $table->dropColumn([
                'contact_person',
                'alternate_phone',
                'city',
                'state',
                'country',
                'postal_code',
                'total_area',
                'storage_capacity',
                'max_weight_capacity',
                'shelf_count',
                'rack_count',
                'bin_count',
                'opening_time',
                'closing_time',
                'is_24_hours',
                'operating_days',
                'warehouse_type',
                'is_main',
                'allow_negative_inventory',
                'requires_approval',
            ]);
        });
    }
};
