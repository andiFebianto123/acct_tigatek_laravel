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
        Schema::table('client_po', function (Blueprint $table) {
            $table->string('po_type')->default('subkon')->comment('subkon, supplier')->after('company_id');
            $table->unsignedBigInteger('purchase_order_id')->nullable()->after('po_type');

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_po', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropColumn(['po_type', 'purchase_order_id']);
        });
    }
};
