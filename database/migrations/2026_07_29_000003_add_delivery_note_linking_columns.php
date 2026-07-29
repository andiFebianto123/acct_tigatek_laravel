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
        Schema::table('invoice_clients', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_clients', 'delivery_note_id')) {
                $table->unsignedBigInteger('delivery_note_id')->nullable()->after('client_po_id')->comment('ID Surat Jalan (Delivery Note) terkait');
                $table->foreign('delivery_note_id')->references('id')->on('delivery_notes')->onDelete('set null');
            }
        });

        Schema::table('invoice_client_details', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_client_details', 'delivery_note_detail_id')) {
                $table->unsignedBigInteger('delivery_note_detail_id')->nullable()->after('device_stock_id')->comment('ID Detail Surat Jalan terkait');
                $table->foreign('delivery_note_detail_id')->references('id')->on('delivery_note_details')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_client_details', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_client_details', 'delivery_note_detail_id')) {
                $table->dropForeign(['delivery_note_detail_id']);
                $table->dropColumn('delivery_note_detail_id');
            }
        });

        Schema::table('invoice_clients', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_clients', 'delivery_note_id')) {
                $table->dropForeign(['delivery_note_id']);
                $table->dropColumn('delivery_note_id');
            }
        });
    }
};
