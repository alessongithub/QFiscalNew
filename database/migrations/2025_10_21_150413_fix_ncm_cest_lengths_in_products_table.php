<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajustar tamanhos dos campos NCM e CEST para corresponder às validações do controller
        // NCM: máximo 8 caracteres (apenas números)
        // CEST: máximo 7 caracteres (apenas números)
        try {
            DB::statement('ALTER TABLE `products` MODIFY `ncm` VARCHAR(8) NULL');
            DB::statement('ALTER TABLE `products` MODIFY `cest` VARCHAR(7) NULL');
        } catch (\Throwable $e) {
            \Log::warning('Erro ao ajustar tamanhos NCM/CEST: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para tamanhos maiores
        try {
            DB::statement('ALTER TABLE `products` MODIFY `ncm` VARCHAR(20) NULL');
            DB::statement('ALTER TABLE `products` MODIFY `cest` VARCHAR(20) NULL');
        } catch (\Throwable $e) {
            \Log::warning('Erro ao reverter tamanhos NCM/CEST: ' . $e->getMessage());
        }
    }
};
