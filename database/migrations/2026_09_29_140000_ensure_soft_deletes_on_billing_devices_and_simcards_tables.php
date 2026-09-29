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
        Schema::table('billing_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('billing_devices', 'deleted_at')) {
                $table->softDeletes()->comment('Waktu data dihapus soft delete');
            }
        });

        Schema::table('billing_simcards', function (Blueprint $table) {
            if (!Schema::hasColumn('billing_simcards', 'deleted_at')) {
                $table->softDeletes()->comment('Waktu data dihapus soft delete');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_devices', function (Blueprint $table) {
            if (Schema::hasColumn('billing_devices', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('billing_simcards', function (Blueprint $table) {
            if (Schema::hasColumn('billing_simcards', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
