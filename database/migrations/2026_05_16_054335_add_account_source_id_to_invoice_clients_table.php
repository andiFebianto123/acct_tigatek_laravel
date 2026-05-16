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
        Schema::table('invoice_clients', function (Blueprint $table) {
            $table->unsignedBigInteger('account_source_id')->nullable()->after('status')->comment('Sumber Rekening (Cast Account)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_clients', function (Blueprint $table) {
            $table->dropColumn('account_source_id');
        });
    }
};
