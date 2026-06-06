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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('pi_prefix', 10)->nullable()->comment('Proforma Invoice Prefix');
            $table->string('quotation_prefix', 10)->nullable()->comment('Quotation Prefix');
            $table->string('surat_jalan_prefix', 10)->nullable()->comment('Surat Jalan Prefix');
            $table->string('bast_prefix', 10)->nullable()->comment('BAST Prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['pi_prefix', 'quotation_prefix', 'surat_jalan_prefix', 'bast_prefix']);
        });
    }
};
