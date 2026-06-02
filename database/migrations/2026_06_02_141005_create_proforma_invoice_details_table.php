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
        Schema::create('proforma_invoice_details', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for proforma invoice detail');
            $table->unsignedBigInteger('proforma_invoice_id')->comment('Referenced proforma invoice ID');
            $table->string('name', 120)->comment('Detail item/service name');
            $table->integer('qty')->default(1)->comment('Quantity of items');
            $table->decimal('price', 18, 2)->comment('Price per unit');
            $table->timestamp('created_at')->nullable()->comment('Record creation time');
            $table->timestamp('updated_at')->nullable()->comment('Record last update time');

            $table->foreign('proforma_invoice_id')->references('id')->on('proforma_invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoice_details');
    }
};
