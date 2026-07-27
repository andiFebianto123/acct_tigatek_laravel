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
            $table->unsignedBigInteger('reference_id')->nullable()->after('proforma_invoice_id')->comment('ID referensi entitas stok/barang');
            $table->string('reference_type')->nullable()->after('reference_id')->comment('Tipe model referensi (misal App\\Models\\DeviceStock)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoice_details', function (Blueprint $table) {
            $table->dropColumn(['reference_id', 'reference_type']);
        });
    }
};
