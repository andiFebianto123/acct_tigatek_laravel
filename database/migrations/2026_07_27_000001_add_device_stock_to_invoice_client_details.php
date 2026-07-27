<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_client_details', function (Blueprint $table) {
            $table->unsignedBigInteger('device_stock_id')->nullable()->after('invoice_client_id')
                ->comment('FK ke device_stocks, diisi jika invoice item tipe Persediaan');
            $table->decimal('cogs_amount', 18, 4)->default(0)->after('price_base')
                ->comment('Total HPP/COGS dalam currency transaksi (akumulasi dari layer FIFO)');
            $table->decimal('cogs_amount_base', 18, 4)->default(0)->after('cogs_amount')
                ->comment('Total HPP/COGS ekuivalen Rupiah (IDR Base) hasil kalkulasi FIFO');

            $table->foreign('device_stock_id')->references('id')->on('device_stocks')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_client_details', function (Blueprint $table) {
            $table->dropForeign(['device_stock_id']);
            $table->dropColumn(['device_stock_id', 'cogs_amount', 'cogs_amount_base']);
        });
    }
};
