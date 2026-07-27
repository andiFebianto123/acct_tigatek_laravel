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
        Schema::create('device_stock_histories', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->foreignId('device_stock_id')
                  ->constrained('device_stocks')
                  ->onDelete('cascade')
                  ->comment('ID Master Device Stock');
            
            $table->integer('qty')->default(0)->comment('Sisa stok fisik pada layer modal ini');
            $table->string('currency_code', 3)->default('IDR')->comment('Kode mata uang saat layer dibuat: IDR/USD');
            $table->decimal('buy_price', 18, 4)->default(0)->comment('Harga beli original per unit');
            $table->decimal('exchange_rate', 18, 6)->default(1.000000)->comment('Nilai kurs saat layer dibuat');
            $table->decimal('buy_price_base', 18, 4)->comment('Nilai modal ekuivalen Rupiah per unit (Kunci Unik Layer)');

            $table->timestamps();

            // Constraint & Indexing
            $table->unique(['device_stock_id', 'buy_price_base'], 'idx_device_buy_price_base_unique');
            $table->index(['device_stock_id', 'qty'], 'idx_device_stock_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_stock_histories');
    }
};
