<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('transfers', 'shipping_notes')) {
                $table->text('shipping_notes')->nullable()->after('shipped_at');
            }
            if (!Schema::hasColumn('transfers', 'receiving_notes')) {
                $table->text('receiving_notes')->nullable()->after('received_at');
            }
            if (!Schema::hasColumn('transfers', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('receiving_notes');
            }
            if (!Schema::hasColumn('transfers', 'cancelled_by_id')) {
                $table->unsignedBigInteger('cancelled_by_id')->nullable()->after('cancellation_reason');
            }
            if (!Schema::hasColumn('transfers', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancelled_by_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_notes',
                'receiving_notes',
                'cancellation_reason',
                'cancelled_by_id',
                'cancelled_at'
            ]);
        });
    }
};
