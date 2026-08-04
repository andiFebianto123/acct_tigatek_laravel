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
            $table->unsignedBigInteger('client_quotation_id')->nullable()->after('company_id')->comment('Referenced client quotation ID');
            $table->foreign('client_quotation_id')->references('id')->on('client_quotations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoice_clients', function (Blueprint $table) {
            $table->dropForeign(['client_quotation_id']);
            $table->dropColumn('client_quotation_id');
        });
    }
};
