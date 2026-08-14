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
        Schema::table('proforma_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('proforma_invoices', 'category')) {
                $table->string('category', 20)->default('rutin')->after('invoice_date')->comment('Kategori proforma invoice: rutin/non_rutin');
            }
            if (!Schema::hasColumn('proforma_invoices', 'status')) {
                $table->string('status', 20)->default('Unpaid')->after('category')->comment('Status pembayaran: Paid/Unpaid');
            }
        });

        Schema::table('proforma_invoice_clients', function (Blueprint $table) {
            if (!Schema::hasColumn('proforma_invoice_clients', 'category')) {
                $table->string('category', 20)->default('rutin')->after('invoice_date')->comment('Kategori proforma invoice: rutin/non_rutin');
            }
            if (!Schema::hasColumn('proforma_invoice_clients', 'status')) {
                $table->string('status', 20)->default('Unpaid')->after('category')->comment('Status pembayaran: Paid/Unpaid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('proforma_invoices', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('proforma_invoices', 'category')) {
                $table->dropColumn('category');
            }
        });

        Schema::table('proforma_invoice_clients', function (Blueprint $table) {
            if (Schema::hasColumn('proforma_invoice_clients', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('proforma_invoice_clients', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
