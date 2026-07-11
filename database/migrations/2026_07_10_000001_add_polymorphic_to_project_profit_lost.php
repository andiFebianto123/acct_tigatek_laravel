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
        Schema::table('project_profit_lost', function (Blueprint $table) {
            $table->unsignedBigInteger('orderable_id')->nullable()->after('client_po_id')->comment('ID PO Klien atau Purchase Order');
            $table->string('orderable_type')->nullable()->after('orderable_id')->comment('Namespace Model PO Klien atau Purchase Order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_profit_lost', function (Blueprint $table) {
            $table->dropColumn(['orderable_id', 'orderable_type']);
        });
    }
};
