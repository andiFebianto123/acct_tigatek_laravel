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
        if (!Schema::hasTable('billing_simcards')) {
            Schema::create('billing_simcards', function (Blueprint $table) {
                $table->id()->comment('ID Primary Key');
                $table->unsignedBigInteger('company_id')->nullable()->comment('ID Perusahaan (Milik Perusahaan)');
                $table->string('product', 100)->nullable()->comment('Produk');
                $table->string('device_name', 150)->nullable()->comment('Nama Device');
                $table->string('technology', 50)->nullable()->comment('Teknologi');
                $table->string('device_profile_id', 100)->nullable()->comment('Device Profile ID');
                $table->string('iccid', 100)->nullable()->comment('ICCID');
                $table->string('msisdn', 50)->nullable()->comment('MSISDN');
                $table->string('status', 50)->nullable()->comment('Status');
                $table->string('rate_plan', 100)->nullable()->comment('Rate Plan');
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
        Schema::dropIfExists('billing_simcards');
    }
};
