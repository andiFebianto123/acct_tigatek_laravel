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
        if (Schema::hasTable('basts') && !Schema::hasColumn('basts', 'phone')) {
            Schema::table('basts', function (Blueprint $table) {
                $table->string('phone', 50)->nullable()->after('pic')->comment('Nomor Telepon');
            });
        }

        if (Schema::hasTable('delivery_notes') && !Schema::hasColumn('delivery_notes', 'phone')) {
            Schema::table('delivery_notes', function (Blueprint $table) {
                $table->string('phone', 50)->nullable()->after('pic')->comment('Nomor Telepon');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('basts') && Schema::hasColumn('basts', 'phone')) {
            Schema::table('basts', function (Blueprint $table) {
                $table->dropColumn('phone');
            });
        }

        if (Schema::hasTable('delivery_notes') && Schema::hasColumn('delivery_notes', 'phone')) {
            Schema::table('delivery_notes', function (Blueprint $table) {
                $table->dropColumn('phone');
            });
        }
    }
};
