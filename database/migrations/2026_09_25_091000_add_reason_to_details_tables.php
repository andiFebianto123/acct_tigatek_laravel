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
        if (Schema::hasTable('proforma_invoice_client_details') && !Schema::hasColumn('proforma_invoice_client_details', 'reason')) {
            Schema::table('proforma_invoice_client_details', function (Blueprint $table) {
                $table->text('reason')->nullable()->after('price')->comment('Keterangan / Deskripsi detail item');
            });
        }

        if (Schema::hasTable('proforma_invoice_details') && !Schema::hasColumn('proforma_invoice_details', 'reason')) {
            Schema::table('proforma_invoice_details', function (Blueprint $table) {
                $table->text('reason')->nullable()->after('price')->comment('Keterangan / Deskripsi detail item');
            });
        }

        if (Schema::hasTable('client_quotation_details') && !Schema::hasColumn('client_quotation_details', 'reason')) {
            Schema::table('client_quotation_details', function (Blueprint $table) {
                $table->text('reason')->nullable()->after('total_price_base')->comment('Keterangan / Deskripsi detail item');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('proforma_invoice_client_details') && Schema::hasColumn('proforma_invoice_client_details', 'reason')) {
            Schema::table('proforma_invoice_client_details', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }

        if (Schema::hasTable('proforma_invoice_details') && Schema::hasColumn('proforma_invoice_details', 'reason')) {
            Schema::table('proforma_invoice_details', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }

        if (Schema::hasTable('client_quotation_details') && Schema::hasColumn('client_quotation_details', 'reason')) {
            Schema::table('client_quotation_details', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }
    }
};
