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
        if (Schema::hasTable('account_transactions')) {
            Schema::table('account_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('account_transactions', 'currency_code')) {
                    $table->string('currency_code', 3)->default('IDR')->after('status')->comment('Kode mata uang transaksi: IDR/USD');
                }
                if (!Schema::hasColumn('account_transactions', 'exchange_rate')) {
                    $table->decimal('exchange_rate', 18, 6)->default(1.000000)->after('currency_code')->comment('Nilai kurs USD terhadap IDR saat transaksi disave');
                }
                if (!Schema::hasColumn('account_transactions', 'nominal_transaction_base')) {
                    $table->decimal('nominal_transaction_base', 18, 4)->default(0.0000)->after('nominal_transaction')->comment('Nilai ekuivalen nominal transaksi dalam Rupiah (Base Amount)');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('account_transactions')) {
            Schema::table('account_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('account_transactions', 'nominal_transaction_base')) {
                    $table->dropColumn('nominal_transaction_base');
                }
                if (Schema::hasColumn('account_transactions', 'exchange_rate')) {
                    $table->dropColumn('exchange_rate');
                }
                if (Schema::hasColumn('account_transactions', 'currency_code')) {
                    $table->dropColumn('currency_code');
                }
            });
        }
    }
};
