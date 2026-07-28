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
        if (!Schema::hasColumn('client_po', 'currency_code')) {
            Schema::table('client_po', function (Blueprint $table) {
                $table->string('currency_code', 3)->default('IDR')->after('job_name')->comment('Kode mata uang transaksi: IDR/USD');
                $table->decimal('exchange_rate', 18, 6)->default(1.000000)->after('currency_code')->comment('Nilai kurs USD terhadap IDR saat transaksi disave');
                $table->decimal('rap_value_base', 18, 4)->nullable()->after('rap_value')->comment('Nilai ekuivalen dalam Rupiah (Base Amount)');
                $table->decimal('job_value_base', 18, 4)->nullable()->after('job_value')->comment('Nilai ekuivalen dalam Rupiah (Base Amount)');
                $table->decimal('job_value_include_ppn_base', 18, 4)->nullable()->after('job_value_include_ppn')->comment('Nilai ekuivalen dalam Rupiah (Base Amount)');
            });

            \Illuminate\Support\Facades\DB::statement("
                UPDATE client_po 
                SET currency_code = 'IDR',
                    exchange_rate = 1.000000,
                    rap_value_base = rap_value,
                    job_value_base = job_value,
                    job_value_include_ppn_base = job_value_include_ppn
                WHERE currency_code IS NULL OR currency_code = 'IDR'
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('client_po', 'currency_code')) {
            Schema::table('client_po', function (Blueprint $table) {
                $table->dropColumn([
                    'currency_code',
                    'exchange_rate',
                    'rap_value_base',
                    'job_value_base',
                    'job_value_include_ppn_base',
                ]);
            });
        }
    }
};
