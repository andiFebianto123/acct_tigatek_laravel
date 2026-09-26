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
        if (Schema::hasTable('client_quotation_details') && !Schema::hasColumn('client_quotation_details', 'item_type')) {
            Schema::table('client_quotation_details', function (Blueprint $table) {
                $table->string('item_type', 30)->default('persediaan')->after('device_stock_id')->comment('Tipe item penawaran: persediaan / non_persediaan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('client_quotation_details') && Schema::hasColumn('client_quotation_details', 'item_type')) {
            Schema::table('client_quotation_details', function (Blueprint $table) {
                $table->dropColumn('item_type');
            });
        }
    }
};
