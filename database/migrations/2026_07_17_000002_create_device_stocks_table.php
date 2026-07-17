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
        Schema::create('device_stocks', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->string('name')->comment('Nama barang');
            $table->string('code')->unique()->comment('Kode barang');
            $table->foreignId('category_id')->constrained('device_stock_categories')->onDelete('cascade')->comment('ID kategori barang');
            $table->integer('qty')->default(0)->comment('Jumlah stok barang');
            $table->decimal('sell_price', 15, 2)->default(0)->comment('Harga jual barang');
            $table->decimal('buy_price', 15, 2)->default(0)->comment('Harga beli barang');
            $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
            $table->timestamp('updated_at')->nullable()->comment('Waktu data diperbarui');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_stocks');
    }
};
