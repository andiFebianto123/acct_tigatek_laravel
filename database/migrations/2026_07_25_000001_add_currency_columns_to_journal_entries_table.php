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
        if (Schema::hasTable('journal_entries')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                if (!Schema::hasColumn('journal_entries', 'currency_code')) {
                    $table->string('currency_code', 3)->default('IDR')->after('date')->comment('Kode mata uang transaksi: IDR/USD');
                }
                if (!Schema::hasColumn('journal_entries', 'exchange_rate')) {
                    $table->decimal('exchange_rate', 18, 6)->default(1.000000)->after('currency_code')->comment('Nilai kurs USD terhadap IDR saat transaksi disave');
                }
                if (!Schema::hasColumn('journal_entries', 'debit_base')) {
                    $table->decimal('debit_base', 18, 4)->default(0.0000)->after('credit')->comment('Nilai ekuivalen debit dalam Rupiah (Base Amount)');
                }
                if (!Schema::hasColumn('journal_entries', 'credit_base')) {
                    $table->decimal('credit_base', 18, 4)->default(0.0000)->after('debit_base')->comment('Nilai ekuivalen kredit dalam Rupiah (Base Amount)');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('journal_entries')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                if (Schema::hasColumn('journal_entries', 'credit_base')) {
                    $table->dropColumn('credit_base');
                }
                if (Schema::hasColumn('journal_entries', 'debit_base')) {
                    $table->dropColumn('debit_base');
                }
                if (Schema::hasColumn('journal_entries', 'exchange_rate')) {
                    $table->dropColumn('exchange_rate');
                }
                if (Schema::hasColumn('journal_entries', 'currency_code')) {
                    $table->dropColumn('currency_code');
                }
            });
        }
    }
};
