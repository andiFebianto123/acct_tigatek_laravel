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
        Schema::table('vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('vouchers', 'currency_code')) {
                $table->string('currency_code', 3)->default('IDR')->after('date_voucher')->comment('Kode mata uang transaksi: IDR/USD');
            }
            if (!Schema::hasColumn('vouchers', 'exchange_rate')) {
                $table->decimal('exchange_rate', 18, 6)->default(1.000000)->after('currency_code')->comment('Nilai kurs USD terhadap IDR saat transaksi disave');
            }
            if (!Schema::hasColumn('vouchers', 'bill_value_base')) {
                $table->decimal('bill_value_base', 18, 4)->nullable()->after('bill_value')->comment('Nilai ekuivalen Tagihan dalam Rupiah (Base Amount)');
            }
            if (!Schema::hasColumn('vouchers', 'dpp_value_base')) {
                $table->decimal('dpp_value_base', 18, 4)->nullable()->default(0)->after('dpp_value')->comment('Nilai ekuivalen DPP dalam Rupiah (Base Amount)');
            }
            if (!Schema::hasColumn('vouchers', 'total_price_ppn_base')) {
                $table->decimal('total_price_ppn_base', 18, 4)->nullable()->default(0)->comment('Nilai ekuivalen PPN dalam Rupiah (Base Amount)');
            }
            if (!Schema::hasColumn('vouchers', 'total_base')) {
                $table->decimal('total_base', 18, 4)->nullable()->after('total')->comment('Nilai ekuivalen Total + PPN dalam Rupiah (Base Amount)');
            }
            if (!Schema::hasColumn('vouchers', 'discount_pph_23_base')) {
                $table->decimal('discount_pph_23_base', 18, 4)->nullable()->default(0)->after('discount_pph_23')->comment('Nilai ekuivalen Potongan PPh 23 dalam Rupiah (Base Amount)');
            }
            if (!Schema::hasColumn('vouchers', 'discount_pph_4_base')) {
                $table->decimal('discount_pph_4_base', 18, 4)->nullable()->default(0)->after('discount_pph_4')->comment('Nilai ekuivalen Potongan PPh 4 dalam Rupiah (Base Amount)');
            }
            if (!Schema::hasColumn('vouchers', 'discount_pph_21_base')) {
                $table->decimal('discount_pph_21_base', 18, 4)->nullable()->default(0)->after('discount_pph_21')->comment('Nilai ekuivalen Potongan PPh 21 dalam Rupiah (Base Amount)');
            }
            if (!Schema::hasColumn('vouchers', 'payment_transfer_base')) {
                $table->decimal('payment_transfer_base', 18, 4)->nullable()->after('payment_transfer')->comment('Nilai ekuivalen Transfer Bersih dalam Rupiah (Base Amount)');
            }
        });

        // Backfill existing vouchers records with IDR defaults
        DB::statement("
            UPDATE vouchers
            SET currency_code = 'IDR',
                exchange_rate = 1.000000,
                bill_value_base = bill_value,
                dpp_value_base = COALESCE(dpp_value, 0),
                total_base = total,
                discount_pph_23_base = discount_pph_23,
                discount_pph_4_base = discount_pph_4,
                discount_pph_21_base = discount_pph_21,
                payment_transfer_base = payment_transfer
            WHERE currency_code IS NULL OR currency_code = 'IDR'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn([
                'currency_code',
                'exchange_rate',
                'bill_value_base',
                'dpp_value_base',
                'total_price_ppn_base',
                'total_base',
                'discount_pph_23_base',
                'discount_pph_4_base',
                'discount_pph_21_base',
                'payment_transfer_base',
            ]);
        });
    }
};
