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
        Schema::create('proforma_invoice_client_details', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for proforma invoice client detail');
            $table->unsignedBigInteger('proforma_invoice_client_id')->comment('Referenced proforma invoice client ID');
            $table->string('name', 120)->comment('Detail item/service name');
            $table->integer('qty')->default(1)->comment('Quantity of items');
            $table->decimal('price', 18, 2)->comment('Price per unit');
            $table->timestamps();

            $table->foreign('proforma_invoice_client_id', 'pic_details_pic_id_foreign')
                ->references('id')
                ->on('proforma_invoice_clients')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoice_client_details');
    }
};
