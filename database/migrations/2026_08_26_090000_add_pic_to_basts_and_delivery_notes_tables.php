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
        if (Schema::hasTable('basts') && !Schema::hasColumn('basts', 'pic')) {
            Schema::table('basts', function (Blueprint $table) {
                $table->string('pic', 150)->nullable()->after('client_id')->comment('Nama PIC / Penanggung Jawab');
            });
        }

        if (Schema::hasTable('delivery_notes') && !Schema::hasColumn('delivery_notes', 'pic')) {
            Schema::table('delivery_notes', function (Blueprint $table) {
                $table->string('pic', 150)->nullable()->after('client_id')->comment('Nama PIC / Penanggung Jawab');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('basts') && Schema::hasColumn('basts', 'pic')) {
            Schema::table('basts', function (Blueprint $table) {
                $table->dropColumn('pic');
            });
        }

        if (Schema::hasTable('delivery_notes') && Schema::hasColumn('delivery_notes', 'pic')) {
            Schema::table('delivery_notes', function (Blueprint $table) {
                $table->dropColumn('pic');
            });
        }
    }
};
