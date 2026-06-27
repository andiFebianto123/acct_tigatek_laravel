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
        Schema::table('billing_simcards', function (Blueprint $table) {
            $table->date('reminder_date')->nullable()->after('expired_date')->comment('Tanggal Reminder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_simcards', function (Blueprint $table) {
            $table->dropColumn('reminder_date');
        });
    }
};
