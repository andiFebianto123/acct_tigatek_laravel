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
        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('usd_rate', 18, 6)->default(16000.000000)->nullable()->after('currency_symbol')->comment('Nilai Kurs Konversi 1 USD ke IDR');
        });

        // Mengisi nilai default 16000 ke data settings yang sudah ada
        \Illuminate\Support\Facades\DB::table('settings')->whereNull('usd_rate')->update([
            'usd_rate' => 16000.000000
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('usd_rate');
        });
    }
};
