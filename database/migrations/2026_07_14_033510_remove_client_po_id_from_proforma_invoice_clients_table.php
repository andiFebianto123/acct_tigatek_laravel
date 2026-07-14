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
            $table->dropForeign(['client_po_id']);
            $table->dropColumn('client_po_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoice_clients', function (Blueprint $table) {
            $table->unsignedBigInteger('client_po_id')->nullable()->comment('Referenced client PO ID');
            $table->foreign('client_po_id')->references('id')->on('client_po')->onDelete('cascade');
        });
    }
};
