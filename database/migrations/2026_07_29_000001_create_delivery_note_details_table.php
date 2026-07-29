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
        if (!Schema::hasTable('delivery_note_details')) {
            Schema::create('delivery_note_details', function (Blueprint $table) {
                $table->id()->comment('ID Primary Key');
                $table->unsignedBigInteger('delivery_note_id')->comment('ID Surat Jalan terkait');
                $table->unsignedBigInteger('device_stock_id')->nullable()->comment('ID Master Barang Persediaan (jika ada)');
                $table->text('description')->nullable()->comment('Deskripsi / Nama Barang');
                $table->integer('qty')->default(1)->comment('Kuantitas Barang');
                $table->decimal('cogs_amount', 18, 4)->default(0)->comment('Nilai HPP / COGS');
                $table->decimal('cogs_amount_base', 18, 4)->default(0)->comment('Nilai HPP / COGS Base IDR');
                $table->timestamps();

                $table->foreign('delivery_note_id')->references('id')->on('delivery_notes')->onDelete('cascade');
                $table->foreign('device_stock_id')->references('id')->on('device_stocks')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_note_details');
    }
};
