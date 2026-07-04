<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cast_accounts', function (Blueprint $table) {
            $table->string('bank_branch', 100)->nullable()->after('no_account')->comment('Cabang bank');
            $table->string('address', 255)->nullable()->after('bank_branch')->comment('Alamat kantor cabang bank');
            $table->string('swift_code', 20)->nullable()->after('address')->comment('Kode swift bank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cast_accounts', function (Blueprint $table) {
            $table->dropColumn(['bank_branch', 'address', 'swift_code']);
        });
    }
};
