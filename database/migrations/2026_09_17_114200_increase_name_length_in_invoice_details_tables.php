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
        if (Schema::hasTable('proforma_invoice_details')) {
            Schema::table('proforma_invoice_details', function (Blueprint $table) {
                $table->string('name', 200)->comment('Detail item/service name')->change();
            });
        }

        if (Schema::hasTable('proforma_invoice_client_details')) {
            Schema::table('proforma_invoice_client_details', function (Blueprint $table) {
                $table->string('name', 200)->comment('Detail item/service name')->change();
            });
        }

        if (Schema::hasTable('invoice_client_details')) {
            Schema::table('invoice_client_details', function (Blueprint $table) {
                $table->string('name', 200)->comment('Detail item/service name')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('proforma_invoice_details')) {
            Schema::table('proforma_invoice_details', function (Blueprint $table) {
                $table->string('name', 120)->comment('Detail item/service name')->change();
            });
        }

        if (Schema::hasTable('proforma_invoice_client_details')) {
            Schema::table('proforma_invoice_client_details', function (Blueprint $table) {
                $table->string('name', 120)->comment('Detail item/service name')->change();
            });
        }

        if (Schema::hasTable('invoice_client_details')) {
            Schema::table('invoice_client_details', function (Blueprint $table) {
                $table->string('name', 120)->comment('Detail item/service name')->change();
            });
        }
    }
};
