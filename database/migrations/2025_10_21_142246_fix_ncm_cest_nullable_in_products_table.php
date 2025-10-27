<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Corrigir campos ncm e cest para serem nullable
        // Usamos SQL bruto para evitar dependência do doctrine/dbal
        try {
            DB::statement('ALTER TABLE `products` MODIFY `ncm` VARCHAR(20) NULL');
            DB::statement('ALTER TABLE `products` MODIFY `cest` VARCHAR(20) NULL');
        } catch (\Throwable $e) {
            // Log do erro para debug se necessário
            \Log::warning('Erro ao tornar campos ncm/cest nullable: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para NOT NULL (cuidado: pode causar problemas se houver registros com NULL)
        try {
            DB::statement('ALTER TABLE `products` MODIFY `ncm` VARCHAR(20) NOT NULL');
            DB::statement('ALTER TABLE `products` MODIFY `cest` VARCHAR(20) NOT NULL');
        } catch (\Throwable $e) {
            \Log::warning('Erro ao reverter campos ncm/cest para NOT NULL: ' . $e->getMessage());
        }
    }
};
