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
        Schema::table('spk', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('IDR')->after('job_description')->comment('Kode mata uang transaksi: IDR/USD');
            $table->decimal('exchange_rate', 18, 6)->default(1.000000)->after('currency_code')->comment('Nilai kurs USD terhadap IDR saat transaksi disave');
            $table->decimal('job_value_base', 18, 4)->nullable()->after('job_value')->comment('Nilai ekuivalen dalam Rupiah (Base Amount)');
            $table->decimal('total_value_with_tax_base', 18, 4)->nullable()->after('total_value_with_tax')->comment('Nilai total include PPN ekuivalen dalam Rupiah (Base Amount)');
        });

        // Backfill existing rows with IDR base amounts
        DB::statement("UPDATE spk SET currency_code = 'IDR', exchange_rate = 1.000000, job_value_base = job_value, total_value_with_tax_base = total_value_with_tax WHERE job_value_base IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spk', function (Blueprint $table) {
            $table->dropColumn([
                'currency_code',
                'exchange_rate',
                'job_value_base',
                'total_value_with_tax_base',
            ]);
        });
    }
};
