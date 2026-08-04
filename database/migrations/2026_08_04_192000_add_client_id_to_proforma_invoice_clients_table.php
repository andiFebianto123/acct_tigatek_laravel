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
            if (!Schema::hasColumn('proforma_invoice_clients', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('company_id')->comment('Referenced client ID');
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoice_clients', function (Blueprint $table) {
            if (Schema::hasColumn('proforma_invoice_clients', 'client_id')) {
                $table->dropForeign(['client_id']);
                $table->dropColumn('client_id');
            }
        });
    }
};
