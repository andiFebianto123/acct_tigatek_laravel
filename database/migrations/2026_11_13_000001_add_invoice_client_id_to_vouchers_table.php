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
        Schema::table('vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('vouchers', 'invoice_client_id')) {
                $table->unsignedBigInteger('invoice_client_id')
                    ->nullable()
                    ->after('client_po_id')
                    ->comment('ID Invoice Client yang dipilih pada transaksi voucher');

                $table->foreign('invoice_client_id')
                    ->references('id')
                    ->on('invoice_clients')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('vouchers', 'invoice_client_id')) {
                $table->dropForeign(['invoice_client_id']);
                $table->dropColumn('invoice_client_id');
            }
        });
    }
};
