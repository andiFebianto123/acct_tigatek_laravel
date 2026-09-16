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
        if (Schema::hasTable('proforma_invoices') && !Schema::hasColumn('proforma_invoices', 'type_device')) {
            Schema::table('proforma_invoices', function (Blueprint $table) {
                $table->string('type_device', 255)->nullable()->after('status')->comment('Tipe Barang: Persediaan, Billing Device, Billing SIMCARD');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('proforma_invoices') && Schema::hasColumn('proforma_invoices', 'type_device')) {
            Schema::table('proforma_invoices', function (Blueprint $table) {
                $table->dropColumn('type_device');
            });
        }
    }
};
