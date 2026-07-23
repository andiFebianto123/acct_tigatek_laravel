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
        Schema::table('proforma_invoice_details', function (Blueprint $table) {
            $table->decimal('price', 18, 4)->change();
            $table->decimal('price_base', 18, 4)->nullable()->after('price')->comment('Nilai ekuivalen dalam Rupiah (Base Amount)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoice_details', function (Blueprint $table) {
            $table->dropColumn(['price_base']);
        });
    }
};
