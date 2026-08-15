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
            if (!Schema::hasColumn('proforma_invoice_clients', 'document_imei_iccid')) {
                $table->string('document_imei_iccid', 255)->nullable()->after('invoice_document')->comment('Path file upload dokumen IMEI/ICCID (Excel/CSV)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoice_clients', function (Blueprint $table) {
            if (Schema::hasColumn('proforma_invoice_clients', 'document_imei_iccid')) {
                $table->dropColumn('document_imei_iccid');
            }
        });
    }
};
