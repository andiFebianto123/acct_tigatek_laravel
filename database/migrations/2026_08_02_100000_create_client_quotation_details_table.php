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
        if (!Schema::hasTable('client_quotation_details')) {
            Schema::create('client_quotation_details', function (Blueprint $table) {
                $table->id()->comment('ID Primary Key');
                $table->unsignedBigInteger('client_quotation_id')->comment('ID Penawaran Client terkait');
                $table->unsignedBigInteger('device_stock_id')->nullable()->comment('ID Master Barang / Device Stock');
                $table->text('item_name')->comment('Nama / Deskripsi item penawaran');
                $table->decimal('qty', 18, 4)->default(1.0000)->comment('Jumlah / kuantitas item penawaran');
                $table->string('unit', 50)->nullable()->comment('Satuan item penawaran (misal: Pcs, Set, Lot)');
                $table->decimal('unit_price', 18, 4)->default(0.0000)->comment('Harga satuan item penawaran');
                $table->decimal('total_price', 18, 4)->default(0.0000)->comment('Total harga item penawaran (qty * unit_price)');
                $table->decimal('unit_price_base', 18, 4)->default(0.0000)->comment('Harga satuan ekuivalen dalam Rupiah (Base Amount)');
                $table->decimal('total_price_base', 18, 4)->default(0.0000)->comment('Total harga ekuivalen dalam Rupiah (Base Amount)');
                $table->timestamps();

                $table->foreign('client_quotation_id')->references('id')->on('client_quotations')->onDelete('cascade');
                $table->foreign('device_stock_id')->references('id')->on('device_stocks')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_quotation_details');
    }
};
