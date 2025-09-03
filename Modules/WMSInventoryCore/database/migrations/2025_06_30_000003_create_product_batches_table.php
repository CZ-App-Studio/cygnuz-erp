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
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->string('batch_number');
            $table->string('lot_number')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('quantity');
            $table->foreignId('unit_id')->constrained('units')->onDelete('restrict');
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id']);
            $table->index(['batch_number']);
            $table->index(['expiry_date']);
        });

        // Modify existing transaction tables to include batch tracking
        if (Schema::hasTable('purchase_products')) {
            Schema::table('purchase_products', function (Blueprint $table) {
                $table->unsignedBigInteger('batch_id')->nullable()->after('unit_id');
                $table->date('expiry_date')->nullable()->after('batch_id');
                $table->string('batch_number')->nullable()->after('expiry_date');
                $table->string('lot_number')->nullable()->after('batch_number');

                $table->foreign('batch_id')->references('id')->on('product_batches')->onDelete('set null');
            });
        }

        if (Schema::hasTable('sale_products')) {
            Schema::table('sale_products', function (Blueprint $table) {
                $table->unsignedBigInteger('batch_id')->nullable()->after('unit_id');

                $table->foreign('batch_id')->references('id')->on('product_batches')->onDelete('set null');
            });
        }

        if (Schema::hasTable('transfer_products')) {
            Schema::table('transfer_products', function (Blueprint $table) {
                $table->unsignedBigInteger('batch_id')->nullable()->after('unit_id');

                $table->foreign('batch_id')->references('id')->on('product_batches')->onDelete('set null');
            });
        }

        if (Schema::hasTable('adjustment_products')) {
            Schema::table('adjustment_products', function (Blueprint $table) {
                $table->unsignedBigInteger('batch_id')->nullable()->after('product_id');

                $table->foreign('batch_id')->references('id')->on('product_batches')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('adjustment_products') && Schema::hasColumn('adjustment_products', 'batch_id')) {
            Schema::table('adjustment_products', function (Blueprint $table) {
                $table->dropForeign(['batch_id']);
                $table->dropColumn('batch_id');
            });
        }

        if (Schema::hasTable('transfer_products') && Schema::hasColumn('transfer_products', 'batch_id')) {
            Schema::table('transfer_products', function (Blueprint $table) {
                $table->dropForeign(['batch_id']);
                $table->dropColumn('batch_id');
            });
        }

        if (Schema::hasTable('sale_products') && Schema::hasColumn('sale_products', 'batch_id')) {
            Schema::table('sale_products', function (Blueprint $table) {
                $table->dropForeign(['batch_id']);
                $table->dropColumn('batch_id');
            });
        }

        if (Schema::hasTable('purchase_products')) {
            Schema::table('purchase_products', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_products', 'batch_id')) {
                    $table->dropForeign(['batch_id']);
                    $table->dropColumn(['batch_id', 'expiry_date', 'batch_number', 'lot_number']);
                }
            });
        }

        Schema::dropIfExists('product_batches');
    }
};
