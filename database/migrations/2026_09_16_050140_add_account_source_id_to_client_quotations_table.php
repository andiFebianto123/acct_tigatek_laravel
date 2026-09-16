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
        Schema::table('client_quotations', function (Blueprint $table) {
            $table->unsignedBigInteger('account_source_id')->nullable()->after('job_name')->comment('Sumber Rekening (Cast Account)');
            $table->foreign('account_source_id')->references('id')->on('cast_accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_quotations', function (Blueprint $table) {
            $table->dropForeign(['account_source_id']);
            $table->dropColumn('account_source_id');
        });
    }
};
