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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->boolean('is_stock_posted')->default(false)->after('document_path')->comment('Status apakah stok PO Supplier sudah diposting ke Master/Layer FIFO');
            $table->timestamp('stock_posted_at')->nullable()->after('is_stock_posted')->comment('Waktu posting stok dilakukan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['is_stock_posted', 'stock_posted_at']);
        });
    }
};
