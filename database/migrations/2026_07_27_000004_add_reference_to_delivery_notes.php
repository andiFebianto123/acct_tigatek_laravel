<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_notes', 'reference_type')) {
                $table->string('reference_type', 50)->nullable()
                    ->after('invoice_client_id')
                    ->comment('Tipe referensi dokumen: quotation, proforma_invoice, client_po, invoice_client');
            }
            if (!Schema::hasColumn('delivery_notes', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable()
                    ->after('reference_type')
                    ->comment('ID dokumen referensi sesuai reference_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_notes', 'reference_type')) {
                $table->dropColumn('reference_type');
            }
            if (Schema::hasColumn('delivery_notes', 'reference_id')) {
                $table->dropColumn('reference_id');
            }
        });
    }
};
