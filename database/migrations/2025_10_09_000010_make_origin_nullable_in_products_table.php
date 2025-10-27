<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tornar a coluna origin da tabela products aceitável como NULL
        // Usamos SQL bruto para evitar dependência do doctrine/dbal
        try {
            DB::statement('ALTER TABLE `products` MODIFY `origin` TINYINT NULL');
        } catch (\Throwable $e) {
            // alguns ambientes podem usar outro tipo; tenta alternativa UNSIGNED
            try {
                DB::statement('ALTER TABLE `products` MODIFY `origin` TINYINT UNSIGNED NULL');
            } catch (\Throwable $e2) {
                // como fallback, tenta SMALLINT
                try {
                    DB::statement('ALTER TABLE `products` MODIFY `origin` SMALLINT NULL');
                } catch (\Throwable $e3) {
                    // Mantém silencioso para não travar migrations em ambientes divergentes
                }
            }
        }
    }

    public function down(): void
    {
        // Opcionalmente reverte para NOT NULL (sem default). Evite se causar inconsistência.
        try {
            DB::statement('ALTER TABLE `products` MODIFY `origin` TINYINT NOT NULL');
        } catch (\Throwable $e) {
            try {
                DB::statement('ALTER TABLE `products` MODIFY `origin` TINYINT UNSIGNED NOT NULL');
            } catch (\Throwable $e2) {
                try {
                    DB::statement('ALTER TABLE `products` MODIFY `origin` SMALLINT NOT NULL');
                } catch (\Throwable $e3) {
                    // noop
                }
            }
        }
    }
};

?>


