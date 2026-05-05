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
        Schema::create('client_quotation_po', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_quotation_id');
            $table->unsignedBigInteger('client_po_id');
            $table->timestamps();

            $table->foreign('client_quotation_id')->references('id')->on('client_quotations')->onDelete('cascade');
            $table->foreign('client_po_id')->references('id')->on('client_po')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_quotation_po');
    }
};
