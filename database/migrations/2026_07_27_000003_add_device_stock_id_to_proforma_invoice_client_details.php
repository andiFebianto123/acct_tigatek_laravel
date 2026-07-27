<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('proforma_invoice_client_details') && !Schema::hasColumn('proforma_invoice_client_details', 'device_stock_id')) {
            Schema::table('proforma_invoice_client_details', function (Blueprint $table) {
                $table->unsignedBigInteger('device_stock_id')->nullable()->after('proforma_invoice_client_id')
                    ->comment('FK ke device_stocks, diisi jika proforma invoice item tipe Persediaan');
                $table->foreign('device_stock_id')->references('id')->on('device_stocks')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('proforma_invoice_client_details') && Schema::hasColumn('proforma_invoice_client_details', 'device_stock_id')) {
            Schema::table('proforma_invoice_client_details', function (Blueprint $table) {
                $table->dropForeign(['device_stock_id']);
                $table->dropColumn('device_stock_id');
            });
        }
    }
};
