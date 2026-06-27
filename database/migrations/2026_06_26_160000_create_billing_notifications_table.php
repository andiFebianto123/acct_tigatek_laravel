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
        if (!Schema::hasTable('billing_notifications')) {
            Schema::create('billing_notifications', function (Blueprint $table) {
                $table->id()->comment('ID Primary Key');
                $table->unsignedBigInteger('company_id')->nullable()->comment('ID Perusahaan');
                $table->string('billable_type')->comment('Nama class model (Polymorphism)');
                $table->unsignedBigInteger('billable_id')->comment('ID dari model (Polymorphism)');
                $table->date('notification_date')->comment('Tanggal Notifikasi');
                $table->text('message')->comment('Isi Pesan Notifikasi');
                $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
                $table->timestamp('updated_at')->nullable()->comment('Waktu data diubah');
                $table->softDeletes()->comment('Waktu data dihapus soft delete');

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
                $table->index(['billable_type', 'billable_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_notifications');
    }
};
