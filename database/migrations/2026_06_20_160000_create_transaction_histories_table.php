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
        if (!Schema::hasTable('transaction_histories')) {
            Schema::create('transaction_histories', function (Blueprint $table) {
                $table->id()->comment('ID Primary Key');
                $table->unsignedBigInteger('company_id')->nullable()->comment('ID Perusahaan (Milik Perusahaan)');
                $table->string('transaction_id', 100)->nullable()->comment('Transaction ID (UUID format)');
                $table->string('device_id', 150)->nullable()->comment('Device ID');
                $table->string('msisdn', 50)->nullable()->comment('MSISDN');
                $table->dateTime('op_completion_time')->nullable()->comment('Operation Completion Time');
                $table->string('operations', 150)->nullable()->comment('Operations');
                $table->integer('devices_upload')->nullable()->comment('Devices Upload (number)');
                $table->integer('device_prosses')->nullable()->comment('Device Process (number)');
                $table->integer('device_update')->nullable()->comment('Device Update (number)');
                $table->dateTime('last_update')->nullable()->comment('Last Update Time');
                $table->string('status', 50)->nullable()->comment('Status');
                $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
                $table->timestamp('updated_at')->nullable()->comment('Waktu data diubah');
                $table->softDeletes()->comment('Waktu data dihapus soft delete');

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
                $table->index(['transaction_id', 'company_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_histories');
    }
};
