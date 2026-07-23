<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('device_stocks', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('IDR')->after('qty')->comment('Kode mata uang transaksi: IDR/USD');
            $table->decimal('exchange_rate', 18, 6)->default(1.000000)->after('currency_code')->comment('Nilai kurs USD terhadap IDR saat disave');
            $table->decimal('sell_price_base', 18, 4)->nullable()->after('sell_price')->comment('Nilai Harga Jual ekuivalen dalam Rupiah');
            $table->decimal('buy_price_base', 18, 4)->nullable()->after('buy_price')->comment('Nilai Harga Beli ekuivalen dalam Rupiah');
        });

        // Backfill existing rows with IDR base amounts
        DB::statement("UPDATE device_stocks SET currency_code = 'IDR', exchange_rate = 1.000000, sell_price_base = sell_price, buy_price_base = buy_price WHERE sell_price_base IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_stocks', function (Blueprint $table) {
            $table->dropColumn([
                'currency_code',
                'exchange_rate',
                'sell_price_base',
                'buy_price_base',
            ]);
        });
    }
};
