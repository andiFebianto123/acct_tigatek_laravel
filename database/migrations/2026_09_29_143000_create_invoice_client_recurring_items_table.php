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
        if (!Schema::hasTable('invoice_client_recurring_items')) {
            Schema::create('invoice_client_recurring_items', function (Blueprint $table) {
                $table->id()->comment('ID Primary Key');
                $table->foreignId('invoice_client_id')->constrained('invoice_clients')->onDelete('cascade')->comment('Relasi ke Invoice Client');
                $table->unsignedBigInteger('invoice_client_detail_id')->nullable()->comment('Relasi ke Detail Invoice Client');
                $table->string('code_billing', 50)->nullable()->comment('Kode Billing');
                $table->string('item_type', 20)->comment('Tipe Item: Device atau SIMCARD');
                $table->string('billable_type', 100)->nullable()->comment('Model class target: BillingDevice / BillingSimcard');
                $table->unsignedBigInteger('billable_id')->nullable()->comment('ID record asli di billing_devices / billing_simcards');
                $table->string('identifier', 100)->nullable()->comment('Identifier utama: device_id atau msisdn');
                $table->string('secondary_identifier', 100)->nullable()->comment('Identifier sekunder: imei/vehicle_uid atau iccid');
                $table->string('item_name', 150)->nullable()->comment('Nama kendaraan atau nama produk');
                $table->json('snapshot_data')->nullable()->comment('Snapshot lengkap atribut record saat invoice dibuat');
                $table->timestamps();
                $table->softDeletes()->comment('Waktu data dihapus soft delete');

                $table->foreign('invoice_client_detail_id')->references('id')->on('invoice_client_details')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('invoice_client_details') && !Schema::hasColumn('invoice_client_details', 'code_billing')) {
            Schema::table('invoice_client_details', function (Blueprint $table) {
                $table->string('code_billing', 50)->nullable()->after('device_stock_id')->comment('Kode Billing untuk tagihan recurring');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoice_client_details') && Schema::hasColumn('invoice_client_details', 'code_billing')) {
            Schema::table('invoice_client_details', function (Blueprint $table) {
                $table->dropColumn('code_billing');
            });
        }

        Schema::dropIfExists('invoice_client_recurring_items');
    }
};
