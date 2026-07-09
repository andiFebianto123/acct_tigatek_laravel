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
        Schema::create('proforma_invoice_clients', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for proforma invoice client');
            $table->unsignedBigInteger('company_id')->nullable()->comment('Referenced company ID');
            $table->string('invoice_number', 50)->unique()->comment('Unique invoice number');
            $table->string('name', 100)->comment('Invoice name/label');
            $table->text('address_po')->nullable()->comment('PO address');
            $table->date('invoice_date')->comment('Date invoice was issued');
            $table->text('description')->nullable()->comment('Invoice description/notes');
            $table->unsignedBigInteger('client_po_id')->nullable()->comment('Referenced client PO ID');
            $table->date('po_date')->nullable()->comment('PO Date');
            $table->unsignedBigInteger('client_id')->nullable()->comment('Referenced client ID');
            $table->decimal('tax_ppn', 18, 2)->nullable()->comment('PPN percentage');
            $table->decimal('pph', 10, 2)->default(0)->comment('PPh percentage');
            $table->decimal('discount_pph', 20, 2)->default(0)->comment('PPh discount amount');
            $table->decimal('price_dpp', 18, 2)->nullable()->comment('DPP other price');
            $table->string('kdp', 100)->nullable()->comment('KDP code');
            $table->string('withholding_agent', 255)->nullable()->comment('Withholding agent (e.g. WAPU)');
            $table->date('send_invoice_normal_date')->nullable()->comment('Date normal invoice sent');
            $table->date('send_invoice_revision_date')->nullable()->comment('Date revision invoice sent');
            $table->decimal('price_total_exclude_ppn', 18, 2)->comment('Total price excluding PPN');
            $table->decimal('price_total_include_ppn', 18, 2)->comment('Total price including PPN');
            $table->decimal('price_total', 18, 2)->comment('Total net price');
            $table->string('status', 10)->default('Unpaid')->comment('Payment status (Paid/Unpaid)');
            $table->unsignedBigInteger('account_source_id')->nullable()->comment('Referenced cash account source ID');
            $table->string('invoice_document', 255)->nullable()->comment('Path to uploaded invoice document');
            $table->string('type_device', 255)->nullable()->comment('Device type');
            $table->text('term')->nullable()->comment('Keterangan Term');
            $table->timestamps();

            $table->foreign('client_po_id')->references('id')->on('client_po')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('account_source_id')->references('id')->on('cast_accounts');
            $table->index(['name', 'invoice_date'], 'prof_inv_client_name_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoice_clients');
    }
};
