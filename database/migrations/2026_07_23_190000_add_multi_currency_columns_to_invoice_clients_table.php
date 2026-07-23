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
        Schema::table('invoice_clients', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('IDR')->after('invoice_date')->comment('Kode mata uang transaksi: IDR/USD');
            $table->decimal('exchange_rate', 18, 6)->default(1.000000)->after('currency_code')->comment('Nilai kurs USD terhadap IDR saat disave');
            $table->decimal('price_total_exclude_ppn_base', 18, 4)->nullable()->after('price_total_exclude_ppn')->comment('Nilai Exclude PPN ekuivalen dalam Rupiah');
            $table->decimal('price_total_include_ppn_base', 18, 4)->nullable()->after('price_total_include_ppn')->comment('Nilai Include PPN ekuivalen dalam Rupiah');
            $table->decimal('discount_pph_base', 18, 4)->nullable()->default(0)->after('discount_pph')->comment('Nilai ekuivalen Diskon PPh dalam Rupiah (Base Amount)');
        });

        // Backfill existing rows with IDR base amounts
        DB::statement("UPDATE invoice_clients SET currency_code = 'IDR', exchange_rate = 1.000000, price_total_exclude_ppn_base = price_total_exclude_ppn, price_total_include_ppn_base = price_total_include_ppn, discount_pph_base = discount_pph WHERE price_total_exclude_ppn_base IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_clients', function (Blueprint $table) {
            $table->dropColumn([
                'currency_code',
                'exchange_rate',
                'price_total_exclude_ppn_base',
                'price_total_include_ppn_base',
                'discount_pph_base',
            ]);
        });
    }
};
