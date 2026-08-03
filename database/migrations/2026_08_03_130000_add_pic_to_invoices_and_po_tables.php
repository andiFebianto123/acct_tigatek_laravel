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
        $tables = [
            'proforma_invoice_clients',
            'proforma_invoices',
            'spk',
            'purchase_orders',
            'invoice_clients',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'pic')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('pic', 150)->nullable()->comment('Nama PIC / Penanggung Jawab');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'proforma_invoice_clients',
            'proforma_invoices',
            'spk',
            'purchase_orders',
            'invoice_clients',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'pic')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('pic');
                });
            }
        }
    }
};
