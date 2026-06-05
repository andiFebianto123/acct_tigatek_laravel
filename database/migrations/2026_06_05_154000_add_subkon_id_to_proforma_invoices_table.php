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
        Schema::table('proforma_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('subkon_id')->nullable()->comment('Referenced subkon ID')->after('client_id');
            $table->foreign('subkon_id')->references('id')->on('subkons')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            $table->dropForeign(['subkon_id']);
            $table->dropColumn('subkon_id');
        });
    }
};
