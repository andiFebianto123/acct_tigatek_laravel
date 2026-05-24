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
        if (!Schema::hasTable('basts')) {
            Schema::create('basts', function (Blueprint $table) {
                $table->id()->comment('ID Primary Key');
                $table->unsignedBigInteger('company_id')->nullable()->comment('ID Perusahaan (Milik Perusahaan)');
                $table->unsignedBigInteger('client_po_id')->nullable()->comment('ID PO Client terkait');
                $table->unsignedBigInteger('client_id')->nullable()->comment('ID Client (Pihak Kedua / Penerima)');
                $table->string('number', 100)->comment('Nomor BAST');
                $table->date('date')->comment('Tanggal BAST');
                $table->string('first_party')->comment('Pihak Pertama (Penyerah)');
                $table->text('first_party_address')->nullable()->comment('Alamat Pihak Pertama');
                $table->text('address')->nullable()->comment('Alamat Pihak Kedua (Client)');
                $table->text('description')->nullable()->comment('Item deskripsi barang / pekerjaan');
                $table->integer('qty')->default(1)->comment('Quantity barang / pekerjaan');
                $table->text('information')->nullable()->comment('Keterangan tambahan');
                $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
                $table->timestamp('updated_at')->nullable()->comment('Waktu data diubah');
                $table->softDeletes()->comment('Waktu data dihapus soft delete');

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
                $table->foreign('client_po_id')->references('id')->on('client_po')->onDelete('set null');
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('basts');
    }
};
