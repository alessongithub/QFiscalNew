<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('nfe_notes')) { return; }

        // Converter enum para VARCHAR(20) para evitar truncamentos futuros (e permitir com_cc)
        try {
            // Detecta tipo atual
            $columnType = null;
            try {
                $columnType = DB::selectOne("SHOW COLUMNS FROM `nfe_notes` LIKE 'status'");
            } catch (\Throwable $e) {}
            // Executa ALTER TABLE direto (MySQL)
            DB::statement("ALTER TABLE `nfe_notes` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'pending'");
        } catch (\Throwable $e) {
            // ignora se não suportado
        }

        // Adiciona colunas de CC-e caso não existam
        Schema::table('nfe_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('nfe_notes', 'cc_sequencia')) {
                $table->unsignedInteger('cc_sequencia')->default(0)->after('cancelamento_data');
            }
            if (!Schema::hasColumn('nfe_notes', 'cc_ultima_correcao')) {
                $table->text('cc_ultima_correcao')->nullable()->after('cc_sequencia');
            }
            if (!Schema::hasColumn('nfe_notes', 'cc_data')) {
                $table->timestamp('cc_data')->nullable()->after('cc_ultima_correcao');
            }
        });
    }

    public function down(): void
    {
        // Sem downgrade do tipo de coluna para enum por segurança
        // As colunas de CC-e podem ser deixadas, não causam impacto
    }
};























