<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_notes') && !Schema::hasColumn('delivery_notes', 'invoice_client_id')) {
            Schema::table('delivery_notes', function (Blueprint $table) {
                $table->unsignedBigInteger('invoice_client_id')->nullable()->after('client_po_id')->comment('ID Invoice Client terkait');
                $table->foreign('invoice_client_id')->references('id')->on('invoice_clients')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('delivery_notes') && Schema::hasColumn('delivery_notes', 'invoice_client_id')) {
            Schema::table('delivery_notes', function (Blueprint $table) {
                $table->dropForeign(['invoice_client_id']);
                $table->dropColumn('invoice_client_id');
            });
        }
    }
};
