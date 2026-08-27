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
        if (Schema::hasTable('client_quotations') && !Schema::hasColumn('client_quotations', 'term')) {
            Schema::table('client_quotations', function (Blueprint $table) {
                $table->text('term')->nullable()->comment('Keterangan Term');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('client_quotations') && Schema::hasColumn('client_quotations', 'term')) {
            Schema::table('client_quotations', function (Blueprint $table) {
                $table->dropColumn('term');
            });
        }
    }
};
