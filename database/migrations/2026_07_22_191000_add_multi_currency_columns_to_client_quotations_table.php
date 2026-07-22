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
        Schema::table('client_quotations', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('IDR')->after('job_name')->comment('Mata Uang Quotation: IDR, USD, EUR');
            $table->decimal('exchange_rate', 18, 6)->default(1.000000)->after('currency_code')->comment('Nilai Kurs terhadap IDR saat dibuat');
            $table->decimal('rap_value_base', 18, 4)->nullable()->after('rap_value')->comment('RAP dalam Rupiah');
            $table->decimal('job_value_base', 18, 4)->nullable()->after('job_value')->comment('Job Value dalam Rupiah');
            $table->decimal('job_value_include_ppn_base', 18, 4)->nullable()->after('job_value_include_ppn')->comment('Job Value Inc PPN dalam Rupiah');
        });

        // Set default value base untuk data lama yang sudah ada
        \Illuminate\Support\Facades\DB::statement("
            UPDATE client_quotations 
            SET currency_code = 'IDR',
                exchange_rate = 1.000000,
                rap_value_base = rap_value,
                job_value_base = job_value,
                job_value_include_ppn_base = job_value_include_ppn
            WHERE currency_code IS NULL OR currency_code = 'IDR'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_quotations', function (Blueprint $table) {
            $table->dropColumn([
                'currency_code',
                'exchange_rate',
                'rap_value_base',
                'job_value_base',
                'job_value_include_ppn_base',
            ]);
        });
    }
};
