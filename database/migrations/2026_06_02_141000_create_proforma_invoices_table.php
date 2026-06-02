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
        Schema::create('proforma_invoices', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for proforma invoice');
            $table->string('invoice_number', 50)->unique()->comment('Unique invoice number');
            $table->string('name', 100)->comment('Invoice name/label');
            $table->date('invoice_date')->comment('Date invoice was issued');
            $table->unsignedBigInteger('client_po_id')->comment('Referenced client PO ID');
            $table->date('po_date')->nullable()->comment('PO Date');
            $table->unsignedBigInteger('client_id')->nullable()->comment('Referenced client ID');
            $table->text('description')->nullable()->comment('Invoice description/notes');
            $table->text('address_po')->nullable()->comment('PO address');
            $table->decimal('price_dpp', 18, 2)->nullable()->comment('DPP other price');
            $table->string('kdp', 100)->nullable()->comment('KDP code');
            $table->date('send_invoice_normal_date')->nullable()->comment('Date normal invoice sent');
            $table->date('send_invoice_revision_date')->nullable()->comment('Date revision invoice sent');
            $table->decimal('price_total_exclude_ppn', 18, 2)->comment('Total price excluding PPN');
            $table->decimal('price_total_include_ppn', 18, 2)->comment('Total price including PPN');
            $table->decimal('price_total', 18, 2)->comment('Total net price');
            $table->decimal('tax_ppn', 10, 2)->default(0)->comment('PPN percentage');
            $table->decimal('pph', 10, 2)->default(0)->comment('PPh percentage');
            $table->decimal('discount_pph', 20, 2)->default(0)->comment('PPh discount amount');
            $table->string('withholding_agent', 50)->nullable()->comment('Withholding agent (e.g. WAPU)');
            $table->string('status', 10)->default('Unpaid')->comment('Payment status (Paid/Unpaid)');
            $table->unsignedBigInteger('company_id')->nullable()->comment('Referenced company ID');
            $table->unsignedBigInteger('account_source_id')->nullable()->comment('Referenced cash account source ID');
            $table->string('invoice_document')->nullable()->comment('Path to uploaded invoice document');
            $table->timestamp('created_at')->nullable()->comment('Record creation time');
            $table->timestamp('updated_at')->nullable()->comment('Record last update time');

            $table->foreign('client_po_id')->references('id')->on('client_po')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('account_source_id')->references('id')->on('cast_accounts');
            $table->index(['name', 'invoice_date'], 'proforma_invoice_name_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoices');
    }
};
