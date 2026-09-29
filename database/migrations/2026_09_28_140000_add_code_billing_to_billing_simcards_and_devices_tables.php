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
        Schema::table('billing_simcards', function (Blueprint $table) {
            if (!Schema::hasColumn('billing_simcards', 'code_billing')) {
                $table->string('code_billing', 20)->nullable()->after('client_id')->comment('Kode Billing');
            }
        });

        Schema::table('billing_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('billing_devices', 'code_billing')) {
                $table->string('code_billing', 20)->nullable()->after('client_id')->comment('Kode Billing');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_simcards', function (Blueprint $table) {
            if (Schema::hasColumn('billing_simcards', 'code_billing')) {
                $table->dropColumn('code_billing');
            }
        });

        Schema::table('billing_devices', function (Blueprint $table) {
            if (Schema::hasColumn('billing_devices', 'code_billing')) {
                $table->dropColumn('code_billing');
            }
        });
    }
};
