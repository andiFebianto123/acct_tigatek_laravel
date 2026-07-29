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
        Schema::table('device_stock_mutations', function (Blueprint $table) {
            if (!Schema::hasColumn('device_stock_mutations', 'buy_price_base')) {
                $table->decimal('buy_price_base', 18, 4)->nullable()->after('qty_after')->comment('Nilai modal beli Rupiah saat mutasi');
            }
            if (!Schema::hasColumn('device_stock_mutations', 'note')) {
                $table->text('note')->nullable()->after('buy_price_base')->comment('Catatan / keterangan mutasi stok');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_stock_mutations', function (Blueprint $table) {
            if (Schema::hasColumn('device_stock_mutations', 'buy_price_base')) {
                $table->dropColumn('buy_price_base');
            }
            if (Schema::hasColumn('device_stock_mutations', 'note')) {
                $table->dropColumn('note');
            }
        });
    }
};
