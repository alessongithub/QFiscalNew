<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajustar tamanhos dos campos na tabela products
        Schema::table('products', function (Blueprint $table) {
            $table->string('ncm', 20)->change();  // Aumentar de 8 para 20
            $table->string('cest', 20)->change(); // Aumentar de 7 para 20
            // Não alterar o campo origin por enquanto - pode ser decimal
            // Não alterar o campo zip_code por enquanto - tem valores muito longos
        });
    }

    public function down(): void
    {
        // Reverter mudanças na tabela products
        Schema::table('products', function (Blueprint $table) {
            $table->string('ncm', 8)->change();
            $table->string('cest', 7)->change();
            // Não reverter origin e zip_code
        });
    }
};
