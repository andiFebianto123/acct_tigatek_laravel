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
        Schema::table('proforma_invoice_client_details', function (Blueprint $table) {
            $table->decimal('price_base', 18, 4)->nullable()->after('price')->comment('Harga per unit ekuivalen dalam Rupiah');
        });

        // Backfill existing rows with IDR base price
        DB::statement("UPDATE proforma_invoice_client_details SET price_base = price WHERE price_base IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoice_client_details', function (Blueprint $table) {
            $table->dropColumn('price_base');
        });
    }
};
