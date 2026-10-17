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
        if (!Schema::hasTable('billing_reminders')) {
            Schema::create('billing_reminders', function (Blueprint $table) {
                $table->id()->comment('ID Primary Key');
                $table->unsignedBigInteger('company_id')->nullable()->comment('ID Perusahaan (Milik Perusahaan)');
                $table->string('code_billing', 100)->comment('Kode Billing grup perangkat / SIM Card');
                $table->date('installation_date')->nullable()->comment('Tanggal Pemasangan / Instalasi');
                $table->date('start_date')->nullable()->comment('Tanggal Mulai Tagihan');
                $table->date('end_date')->nullable()->comment('Tanggal Akhir Tagihan / Expired Date');
                $table->string('period', 50)->nullable()->comment('Tipe / Durasi Periode Reminder (1_month, 3_months, 6_months, 1_year)');
                $table->date('next_notification_date')->nullable()->comment('Tanggal Notifikasi Berikutnya');
                $table->timestamps();
                $table->softDeletes()->comment('Waktu data dihapus soft delete');

                $table->index(['company_id', 'code_billing']);
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_reminders');
    }
};
