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
        if (Schema::hasTable('purchase_order_details') && Schema::hasColumn('purchase_order_details', 'name_alias')) {
            Schema::table('purchase_order_details', function (Blueprint $table) {
                $table->text('name_alias')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchase_order_details') && Schema::hasColumn('purchase_order_details', 'name_alias')) {
            Schema::table('purchase_order_details', function (Blueprint $table) {
                $table->string('name_alias', 120)->nullable()->change();
            });
        }
    }
};
