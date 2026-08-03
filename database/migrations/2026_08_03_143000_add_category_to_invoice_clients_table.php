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
            if (!Schema::hasColumn('invoice_clients', 'category')) {
                $table->string('category', 20)->default('rutin')->after('invoice_date')->comment('Kategori invoice: rutin/non_rutin');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_clients', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_clients', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
