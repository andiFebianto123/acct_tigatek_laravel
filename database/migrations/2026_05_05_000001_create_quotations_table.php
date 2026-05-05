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
        Schema::create('client_quotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('work_code', 30)->nullable();
            $table->string('po_number', 30)->nullable();
            $table->string('status')->nullable();
            $table->string('job_name')->nullable();
            $table->decimal('rap_value', 18, 2)->nullable();
            $table->decimal('job_value', 18, 2)->nullable();
            $table->decimal('tax_ppn', 18, 2)->nullable();
            $table->decimal('job_value_include_ppn', 18, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('date_po')->nullable();
            $table->string('reimburse_type', 50)->nullable();
            $table->string('document_path')->nullable();
            $table->string('category')->nullable();
            $table->decimal('load_general_value', 18, 2)->nullable();
            $table->date('date_invoice')->nullable();
            $table->decimal('price_total', 18, 2)->nullable();
            $table->decimal('profit_and_loss', 18, 2)->nullable();
            $table->decimal('price_after_year', 18, 2)->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_quotations');
    }
};
