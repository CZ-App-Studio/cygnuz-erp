<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            // Add new columns with expected names if they don't exist
            if (! Schema::hasColumn('transfers', 'transfer_date')) {
                $table->date('transfer_date')->nullable()->after('id');
            }
            if (! Schema::hasColumn('transfers', 'reference_no')) {
                $table->string('reference_no')->nullable()->after('code');
            }
            if (! Schema::hasColumn('transfers', 'source_warehouse_id')) {
                $table->unsignedBigInteger('source_warehouse_id')->nullable()->after('reference_no');
            }
            if (! Schema::hasColumn('transfers', 'destination_warehouse_id')) {
                $table->unsignedBigInteger('destination_warehouse_id')->nullable()->after('source_warehouse_id');
            }
            if (! Schema::hasColumn('transfers', 'shipping_cost')) {
                $table->decimal('shipping_cost', 10, 2)->default(0)->after('notes');
            }
            if (! Schema::hasColumn('transfers', 'shipping_method')) {
                $table->string('shipping_method')->nullable()->after('shipping_cost');
            }
            if (! Schema::hasColumn('transfers', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->after('shipping_method');
            }
            if (! Schema::hasColumn('transfers', 'expected_arrival_date')) {
                $table->date('expected_arrival_date')->nullable()->after('tracking_number');
            }
            if (! Schema::hasColumn('transfers', 'actual_arrival_date')) {
                $table->date('actual_arrival_date')->nullable()->after('expected_arrival_date');
            }
            if (! Schema::hasColumn('transfers', 'approved_by_id')) {
                $table->unsignedBigInteger('approved_by_id')->nullable()->after('actual_arrival_date');
            }
            if (! Schema::hasColumn('transfers', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by_id');
            }
            if (! Schema::hasColumn('transfers', 'shipped_by_id')) {
                $table->unsignedBigInteger('shipped_by_id')->nullable()->after('approved_at');
            }
            if (! Schema::hasColumn('transfers', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable()->after('shipped_by_id');
            }
            if (! Schema::hasColumn('transfers', 'received_by_id')) {
                $table->unsignedBigInteger('received_by_id')->nullable()->after('shipped_at');
            }
            if (! Schema::hasColumn('transfers', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('received_by_id');
            }
        });

        // Copy data from old columns to new columns if old columns exist
        if (Schema::hasColumn('transfers', 'date') && Schema::hasColumn('transfers', 'transfer_date')) {
            DB::statement('UPDATE transfers SET transfer_date = date WHERE transfer_date IS NULL');
        }
        if (Schema::hasColumn('transfers', 'from_warehouse_id') && Schema::hasColumn('transfers', 'source_warehouse_id')) {
            DB::statement('UPDATE transfers SET source_warehouse_id = from_warehouse_id WHERE source_warehouse_id IS NULL');
        }
        if (Schema::hasColumn('transfers', 'to_warehouse_id') && Schema::hasColumn('transfers', 'destination_warehouse_id')) {
            DB::statement('UPDATE transfers SET destination_warehouse_id = to_warehouse_id WHERE destination_warehouse_id IS NULL');
        }

        // Drop old columns and their constraints if they exist
        if (Schema::hasColumn('transfers', 'from_warehouse_id') || Schema::hasColumn('transfers', 'to_warehouse_id')) {
            Schema::table('transfers', function (Blueprint $table) {
                // Drop foreign key constraints first
                try {
                    $table->dropForeign(['from_warehouse_id']);
                } catch (Exception $e) {
                    // Foreign key might not exist
                }
                try {
                    $table->dropForeign(['to_warehouse_id']);
                } catch (Exception $e) {
                    // Foreign key might not exist
                }

                // Drop old columns if they exist
                if (Schema::hasColumn('transfers', 'date')) {
                    $table->dropColumn('date');
                }
                if (Schema::hasColumn('transfers', 'from_warehouse_id')) {
                    $table->dropColumn('from_warehouse_id');
                }
                if (Schema::hasColumn('transfers', 'to_warehouse_id')) {
                    $table->dropColumn('to_warehouse_id');
                }
            });
        }

        // Add foreign key constraints for new columns
        Schema::table('transfers', function (Blueprint $table) {
            try {
                $table->foreign('source_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            } catch (Exception $e) {
                // Foreign key might already exist
            }
            try {
                $table->foreign('destination_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            } catch (Exception $e) {
                // Foreign key might already exist
            }
        });

        // Update transfer_products table to add notes column if it doesn't exist
        if (! Schema::hasColumn('transfer_products', 'notes')) {
            Schema::table('transfer_products', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('quantity');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            // Add back old columns
            $table->date('date')->nullable()->after('id');
            $table->unsignedBigInteger('from_warehouse_id')->nullable()->after('destination_warehouse_id');
            $table->unsignedBigInteger('to_warehouse_id')->nullable()->after('from_warehouse_id');
        });

        // Copy data back from new columns to old columns
        DB::statement('UPDATE transfers SET date = transfer_date');
        DB::statement('UPDATE transfers SET from_warehouse_id = source_warehouse_id');
        DB::statement('UPDATE transfers SET to_warehouse_id = destination_warehouse_id');

        Schema::table('transfers', function (Blueprint $table) {
            // Drop foreign key constraints for new columns
            $table->dropForeign(['source_warehouse_id']);
            $table->dropForeign(['destination_warehouse_id']);

            // Drop new columns
            $table->dropColumn([
                'transfer_date',
                'reference_no',
                'source_warehouse_id',
                'destination_warehouse_id',
                'shipping_cost',
                'shipping_method',
                'tracking_number',
                'expected_arrival_date',
                'actual_arrival_date',
                'approved_by_id',
                'approved_at',
                'shipped_by_id',
                'shipped_at',
                'received_by_id',
                'received_at',
            ]);

            // Add back foreign key constraints for old columns
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
        });

        // Remove notes column from transfer_products if it was added
        if (Schema::hasColumn('transfer_products', 'notes')) {
            Schema::table('transfer_products', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }
    }
};
