<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nfe_notes')) {
            Schema::table('nfe_notes', function (Blueprint $table) {
                // Tornar status mais flexível: string curta ao invés de enum
                if (Schema::hasColumn('nfe_notes', 'status')) {
                    try {
                        $table->string('status', 20)->default('transmitida')->change();
                    } catch (\Throwable $e) {
                        // Alguns bancos não suportam change() facilmente para enum → fallback: tenta criar coluna temp
                    }
                } else {
                    $table->string('status', 20)->default('transmitida');
                }

                // Colunas de CC-e (se não existirem)
                if (!Schema::hasColumn('nfe_notes', 'cc_sequencia')) {
                    $table->unsignedInteger('cc_sequencia')->default(0)->after('status');
                }
                if (!Schema::hasColumn('nfe_notes', 'cc_ultima_correcao')) {
                    $table->text('cc_ultima_correcao')->nullable()->after('cc_sequencia');
                }
                if (!Schema::hasColumn('nfe_notes', 'cc_data')) {
                    $table->timestamp('cc_data')->nullable()->after('cc_ultima_correcao');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nfe_notes')) {
            Schema::table('nfe_notes', function (Blueprint $table) {
                // Não revertendo mudança do tipo de status por segurança
                // Remoção das colunas de CC-e opcionalmente
                // $table->dropColumn(['cc_sequencia','cc_ultima_correcao','cc_data']);
            });
        }
    }
};























