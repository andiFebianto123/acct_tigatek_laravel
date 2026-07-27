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
        Schema::create('device_stock_mutations', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->foreignId('device_stock_id')
                  ->constrained('device_stocks')
                  ->onDelete('cascade')
                  ->comment('ID Master Device Stock');
            
            $table->foreignId('device_stock_history_id')
                  ->nullable()
                  ->constrained('device_stock_histories')
                  ->onDelete('set null')
                  ->comment('ID Layer History terkait');
            
            $table->nullableMorphs('reference'); // reference_type & reference_id (PO / Invoice)
            $table->enum('type', ['IN', 'OUT', 'ADJUSTMENT'])->comment('Jenis mutasi stok: IN (Masuk), OUT (Keluar), ADJUSTMENT (Opname)');
            $table->integer('qty_change')->comment('Jumlah kuantitas perubahan (+/-)');
            $table->integer('qty_before')->default(0)->comment('Kuantitas layer/master sebelum mutasi');
            $table->integer('qty_after')->default(0)->comment('Kuantitas layer/master setelah mutasi');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_stock_mutations');
    }
};
