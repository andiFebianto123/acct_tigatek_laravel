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
        if (Schema::hasTable('invoice_clients') && !Schema::hasColumn('invoice_clients', 'is_recurring')) {
            Schema::table('invoice_clients', function (Blueprint $table) {
                $table->boolean('is_recurring')->nullable()->default(null)->comment('Penanda apakah invoice dibuat dari Notifikasi Billing (recurring)');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoice_clients') && Schema::hasColumn('invoice_clients', 'is_recurring')) {
            Schema::table('invoice_clients', function (Blueprint $table) {
                $table->dropColumn('is_recurring');
            });
        }
    }
};
