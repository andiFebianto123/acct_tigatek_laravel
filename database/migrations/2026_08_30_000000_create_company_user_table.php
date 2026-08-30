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
        Schema::create('company_user', function (Blueprint $table) {
            $table->id()
                  ->comment('Primary key tabel pivot company_user');

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment('ID relasi ke tabel users');

            $table->foreignId('company_id')
                  ->constrained('companies')
                  ->onDelete('cascade')
                  ->comment('ID relasi ke tabel companies');

            $table->timestamps();

            // Mencegah duplikasi user_id & company_id
            $table->unique(['user_id', 'company_id'], 'user_company_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
