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
        Schema::table('proforma_invoice_clients', function (Blueprint $table) {
            $table->decimal('discount_pph_base', 18, 4)->nullable()->default(0)->after('discount_pph')->comment('Nilai ekuivalen Diskon PPh dalam Rupiah (Base Amount)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoice_clients', function (Blueprint $table) {
            $table->dropColumn('discount_pph_base');
        });
    }
};
