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
        Schema::table('service_order_occurrences', function (Blueprint $table) {
            // Tornar created_by nullable para permitir ocorrências públicas (sem usuário específico)
            $table->foreignId('created_by')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_order_occurrences', function (Blueprint $table) {
            // Reverter para not null (pode causar problemas se houver registros com null)
            $table->foreignId('created_by')->nullable(false)->change();
        });
    }
};
