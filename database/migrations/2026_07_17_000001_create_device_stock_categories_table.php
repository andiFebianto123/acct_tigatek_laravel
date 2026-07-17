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
        Schema::create('device_stock_categories', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->string('name')->unique()->comment('Nama kategori barang');
            $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
            $table->timestamp('updated_at')->nullable()->comment('Waktu data diperbarui');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_stock_categories');
    }
};
