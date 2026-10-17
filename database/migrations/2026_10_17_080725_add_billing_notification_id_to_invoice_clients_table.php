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
            $table->unsignedBigInteger('billing_notification_id')
                ->nullable()
                ->after('is_recurring')
                ->comment('ID Notifikasi Billing referensi untuk recurring invoice');

            $table->foreign('billing_notification_id')
                ->references('id')
                ->on('billing_notifications')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_clients', function (Blueprint $table) {
            $table->dropForeign(['billing_notification_id']);
            $table->dropColumn('billing_notification_id');
        });
    }
};
