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
        Schema::table('client_po', function (Blueprint $blueprint) {
            $blueprint->unsignedBigInteger('company_id')->nullable()->after('id');
            $blueprint->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_po', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['company_id']);
            $blueprint->dropColumn('company_id');
        });
    }
};
