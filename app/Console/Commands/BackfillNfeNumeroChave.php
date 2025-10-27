<?php

namespace App\Console\Commands;

use App\Models\NfeNote;
use Illuminate\Console\Command;

class BackfillNfeNumeroChave extends Command
{
    protected $signature = 'nfe:backfill-numero-chave {--tenant_id=} {--limit=0} {--dry-run}';
    protected $description = 'Preenche numero_nfe e chave_acesso em massa a partir de response_received e XML disponível';

    public function handle(): int
    {
        $tenantId = $this->option('tenant_id');
        $limit = (int) $this->option('limit');
        $dry = (bool) $this->option('dry-run');

        $q = NfeNote::query();
        if (!empty($tenantId)) { $q->where('tenant_id', (int) $tenantId); }
        $q->where(function($qq){
            $qq->whereNull('numero_nfe')->orWhere('numero_nfe','');
        })->orWhere(function($qq){
            $qq->whereNull('chave_acesso')->orWhere('chave_acesso','');
        });

        $total = (int) $q->count();
        if ($limit > 0 && $total > $limit) { $total = $limit; }
        $this->info("Notas para analisar: {$total}");

        $processed = 0; $updated = 0; $skipped = 0;

        $q->orderBy('id')->chunkById(500, function($chunk) use (&$processed, &$updated, &$skipped, $limit, $dry){
            foreach ($chunk as $note) {
                if ($limit > 0 && $processed >= $limit) { return false; }
                $processed++;

                $numero = $note->numero_nfe;
                $chave = $note->chave_acesso ?: $note->chave_nfe;
                $prot  = $note->protocolo ?: $note->protocolo_autorizacao;

                $resp = $note->response_received;
                if (is_array($resp)) {
                    $data = is_array($resp['data'] ?? null) ? $resp['data'] : [];
                    $numero = $numero ?: ($resp['numero'] ?? $resp['nNF'] ?? ($data['numero'] ?? $data['nNF'] ?? null));
                    $chave  = $chave  ?: ($resp['chave_acesso'] ?? $resp['chNFe'] ?? $resp['chave'] ?? ($data['chave_acesso'] ?? $data['chNFe'] ?? $data['chave'] ?? null));
                    $prot   = $prot   ?: ($resp['protocolo'] ?? $resp['nProt'] ?? ($data['protocolo'] ?? $data['nProt'] ?? null));
                }

                // Se ainda faltar, tenta extrair do XML
                if ((!$numero || !$chave) && ($note->xml_resolved_path || $note->xml_path || $note->arquivo_xml)) {
                    $xmlPath = (string) ($note->xml_resolved_path ?: $note->xml_path ?: $note->arquivo_xml);
                    if ($xmlPath && @file_exists($xmlPath)) {
                        try {
                            $sx = @simplexml_load_file($xmlPath);
                            if ($sx !== false) {
                                $sx->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
                                if (!$numero) {
                                    $n = $sx->xpath('//nfe:ide/nfe:nNF');
                                    if (is_array($n) && isset($n[0])) { $numero = (string) $n[0]; }
                                }
                                if (!$chave) {
                                    $ch = $sx->xpath('//nfe:protNFe/nfe:infProt/nfe:chNFe');
                                    if (is_array($ch) && isset($ch[0])) { $chave = (string) $ch[0]; }
                                    if (!$chave) {
                                        $id = $sx->xpath('string(//nfe:infNFe/@Id)');
                                        $idStr = is_array($id) ? (string)($id[0] ?? '') : (string) $id;
                                        if ($idStr && stripos($idStr, 'NFe') === 0) { $chave = substr($idStr, 3); }
                                    }
                                }
                            }
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }
                }

                $updates = [];
                if ($numero && (string)$note->numero_nfe !== (string)$numero) { $updates['numero_nfe'] = (string)$numero; }
                if ($chave && (string)($note->chave_acesso ?: '') !== (string)$chave) { $updates['chave_acesso'] = (string)$chave; }
                if ($prot && (string)($note->protocolo ?: '') !== (string)$prot) { $updates['protocolo'] = (string)$prot; }

                if (!empty($updates)) {
                    if ($dry) {
                        $this->line("DRY id={$note->id} updates=".json_encode($updates));
                    } else {
                        $note->update($updates);
                        $updated++;
                        $this->line("Atualizado id={$note->id} numero=".($updates['numero_nfe'] ?? '-') . " chave=". (isset($updates['chave_acesso']) ? substr($updates['chave_acesso'],0,8).'...' : '-'));
                    }
                } else {
                    $skipped++;
                }
            }
        });

        $this->info("Processadas: {$processed} | Atualizadas: {$updated} | Sem mudanças: {$skipped}");
        if ($dry) { $this->warn('Execução em modo DRY-RUN, nada foi salvo.'); }
        return 0;
    }
}


