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
        if (Schema::hasTable('client_quotations') && !Schema::hasColumn('client_quotations', 'pic')) {
            Schema::table('client_quotations', function (Blueprint $table) {
                $table->string('pic', 150)->nullable()->after('client_id')->comment('Nama PIC / Penanggung Jawab Penawaran Client');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('client_quotations') && Schema::hasColumn('client_quotations', 'pic')) {
            Schema::table('client_quotations', function (Blueprint $table) {
                $table->dropColumn('pic');
            });
        }
    }
};
