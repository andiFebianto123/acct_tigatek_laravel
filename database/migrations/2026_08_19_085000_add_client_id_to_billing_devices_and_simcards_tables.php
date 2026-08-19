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
        Schema::table('billing_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('billing_devices', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('company_id')->comment('ID Klien / Pelanggan');
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            }
        });

        Schema::table('billing_simcards', function (Blueprint $table) {
            if (!Schema::hasColumn('billing_simcards', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('company_id')->comment('ID Klien / Pelanggan');
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_devices', function (Blueprint $table) {
            if (Schema::hasColumn('billing_devices', 'client_id')) {
                $table->dropForeign(['client_id']);
                $table->dropColumn('client_id');
            }
        });

        Schema::table('billing_simcards', function (Blueprint $table) {
            if (Schema::hasColumn('billing_simcards', 'client_id')) {
                $table->dropForeign(['client_id']);
                $table->dropColumn('client_id');
            }
        });
    }
};
