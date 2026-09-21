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
        if (Schema::hasTable('invoice_client_details') && !Schema::hasColumn('invoice_client_details', 'reason')) {
            Schema::table('invoice_client_details', function (Blueprint $table) {
                $table->text('reason')->nullable()->after('name')->comment('Keterangan item invoice reguler');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoice_client_details') && Schema::hasColumn('invoice_client_details', 'reason')) {
            Schema::table('invoice_client_details', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }
    }
};
