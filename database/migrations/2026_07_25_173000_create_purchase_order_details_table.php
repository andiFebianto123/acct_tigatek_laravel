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
        Schema::create('purchase_order_details', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for purchase order detail');
            $table->unsignedBigInteger('purchase_order_id')->comment('Referenced purchase order ID');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('ID referensi entitas stok/barang');
            $table->string('reference_type')->nullable()->comment('Tipe model referensi (misal App\\Models\\DeviceStock)');
            $table->string('name', 120)->comment('Detail item/service name');
            $table->integer('qty')->default(1)->comment('Quantity of items');
            $table->decimal('price', 18, 2)->comment('Price per unit');
            $table->decimal('price_base', 18, 4)->nullable()->comment('Price base in IDR');
            $table->timestamp('created_at')->nullable()->comment('Record creation time');
            $table->timestamp('updated_at')->nullable()->comment('Record last update time');

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_details');
    }
};
