<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cast_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('cast_accounts', 'currency_code')) {
                $table->string('currency_code', 3)->default('IDR')->after('status')->comment('Kode mata uang: IDR/USD');
            }
            if (!Schema::hasColumn('cast_accounts', 'exchange_rate')) {
                $table->decimal('exchange_rate', 18, 6)->default(1.000000)->after('currency_code')->comment('Nilai kurs USD saat disave');
            }
            if (!Schema::hasColumn('cast_accounts', 'total_saldo_base')) {
                $table->decimal('total_saldo_base', 18, 4)->nullable()->after('total_saldo')->comment('Nilai ekuivalen Rupiah');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cast_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('cast_accounts', 'currency_code')) {
                $table->dropColumn(['currency_code', 'exchange_rate', 'total_saldo_base']);
            }
        });
    }
};
