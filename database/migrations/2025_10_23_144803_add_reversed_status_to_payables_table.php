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
        Schema::table('payables', function (Blueprint $table) {
            // Alterar o enum para incluir 'reversed'
            $table->enum('status', ['open', 'partial', 'paid', 'canceled', 'reversed'])->default('open')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            // Reverter para o enum original
            $table->enum('status', ['open', 'partial', 'paid', 'canceled'])->default('open')->change();
        });
    }
};
