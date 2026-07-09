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
        Schema::table('basts', function (Blueprint $table) {
            $table->string('referenceable_type')->nullable()->after('client_po_id')->comment('Class name of referenceable model (ClientPo or ProformaInvoiceClient)');
            $table->unsignedBigInteger('referenceable_id')->nullable()->after('referenceable_type')->comment('ID of referenceable model');
            $table->index(['referenceable_type', 'referenceable_id']);
        });

        // Copy existing client_po_id to referenceable
        \Illuminate\Support\Facades\DB::table('basts')->whereNotNull('client_po_id')->update([
            'referenceable_type' => 'App\Models\ClientPo',
            'referenceable_id' => \Illuminate\Support\Facades\DB::raw('client_po_id'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('basts', function (Blueprint $table) {
            $table->dropIndex(['referenceable_type', 'referenceable_id']);
            $table->dropColumn(['referenceable_type', 'referenceable_id']);
        });
    }
};
