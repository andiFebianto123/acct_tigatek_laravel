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
        Schema::table('proforma_invoices', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('IDR')->after('description')->comment('Kode mata uang transaksi: IDR/USD');
            $table->decimal('exchange_rate', 18, 6)->default(1.000000)->after('currency_code')->comment('Nilai kurs USD saat transaksi disave');
            $table->decimal('nominal_exclude_ppn_base', 18, 4)->nullable()->after('price_total_exclude_ppn')->comment('Nilai Exclude PPN dalam Rupiah (Base Amount)');
            $table->decimal('nominal_include_ppn_base', 18, 4)->nullable()->after('price_total_include_ppn')->comment('Nilai Include PPN dalam Rupiah (Base Amount)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            $table->dropColumn(['currency_code', 'exchange_rate', 'nominal_exclude_ppn_base', 'nominal_include_ppn_base']);
        });
    }
};
