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
        Schema::table('billing_notifications', function (Blueprint $table) {
            // Drop indexes first if existing
            $table->dropIndex(['billable_type', 'billable_id']);

            // Drop polymorphic columns
            $table->dropColumn(['billable_type', 'billable_id']);

            // Add code_billing column
            $table->string('code_billing', 100)->nullable()->after('company_id')->comment('Kode Billing');
            $table->index(['company_id', 'code_billing']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_notifications', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'code_billing']);
            $table->dropColumn('code_billing');

            $table->string('billable_type')->comment('Nama class model (Polymorphism)');
            $table->unsignedBigInteger('billable_id')->comment('ID dari model (Polymorphism)');
            $table->index(['billable_type', 'billable_id']);
        });
    }
};
