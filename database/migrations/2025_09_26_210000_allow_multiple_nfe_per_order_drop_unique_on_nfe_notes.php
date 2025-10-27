<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $dbName = DB::getDatabaseName();
        $idxs = DB::select("SELECT index_name FROM information_schema.statistics WHERE table_schema = ? AND table_name = 'nfe_notes'", [$dbName]);
        $existing = array_map(function($r){ return (string) ($r->index_name ?? $r->INDEX_NAME ?? ''); }, $idxs);

        $candidates = [
            'nfe_notes_numero_pedido_unique',
            'nfe_notes_tenant_id_numero_pedido_unique',
        ];
        foreach ($candidates as $idx) {
            if (in_array($idx, $existing, true)) {
                DB::statement("ALTER TABLE `nfe_notes` DROP INDEX `{$idx}`");
            }
        }

        // Garantir índice não-único
        if (!in_array('nfe_notes_tenant_pedido_index', $existing, true)) {
            try { DB::statement("CREATE INDEX `nfe_notes_tenant_pedido_index` ON `nfe_notes` (`tenant_id`, `numero_pedido`)"); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        $dbName = DB::getDatabaseName();
        $idxs = DB::select("SELECT index_name FROM information_schema.statistics WHERE table_schema = ? AND table_name = 'nfe_notes'", [$dbName]);
        $existing = array_map(function($r){ return (string) ($r->index_name ?? $r->INDEX_NAME ?? ''); }, $idxs);

        if (in_array('nfe_notes_tenant_pedido_index', $existing, true)) {
            try { DB::statement("ALTER TABLE `nfe_notes` DROP INDEX `nfe_notes_tenant_pedido_index`"); } catch (\Throwable $e) {}
        }

        if (!in_array('nfe_notes_tenant_id_numero_pedido_unique', $existing, true)) {
            try { DB::statement("ALTER TABLE `nfe_notes` ADD UNIQUE `nfe_notes_tenant_id_numero_pedido_unique` (`tenant_id`, `numero_pedido`)"); } catch (\Throwable $e) {}
        }
    }
};


