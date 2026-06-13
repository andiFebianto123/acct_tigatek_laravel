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
        if (!Schema::hasTable('billing_devices')) {
            Schema::create('billing_devices', function (Blueprint $table) {
                $table->id()->comment('ID Primary Key');
                $table->unsignedBigInteger('company_id')->nullable()->comment('ID Perusahaan (Milik Perusahaan)');
                $table->string('device_id', 100)->comment('Kode Device');
                $table->string('phone', 50)->nullable()->comment('Nomor Telepon');
                $table->string('vehicle_uid', 100)->nullable()->comment('UID Kendaraan');
                $table->string('vehicle_name', 150)->nullable()->comment('Nama Kendaraan');
                $table->string('imei', 100)->nullable()->comment('Nomor IMEI');
                $table->integer('speed_limit')->nullable()->comment('Batas Kecepatan');
                $table->string('sim_network', 50)->nullable()->comment('Jaringan Kartu SIM');
                $table->string('category', 100)->nullable()->comment('Kategori Device');
                $table->string('model', 100)->nullable()->comment('Model Device');
                $table->date('subscription_expiry_date')->nullable()->comment('Tanggal Kedaluwarsa Langganan');
                $table->date('installation_date')->nullable()->comment('Tanggal Pemasangan');
                $table->date('expired_date')->nullable()->comment('Tanggal Kedaluwarsa');
                $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
                $table->timestamp('updated_at')->nullable()->comment('Waktu data diubah');
                $table->softDeletes()->comment('Waktu data dihapus soft delete');

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_devices');
    }
};
