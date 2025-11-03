<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NcmRule;
use Illuminate\Http\Request;

class NcmRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = NcmRule::query();
        
        // Filtro por código NCM
        if ($request->filled('ncm')) {
            $query->where('ncm', 'like', '%' . $request->ncm . '%');
        }
        
        // Filtro por observação/descrição
        if ($request->filled('note')) {
            $query->where('note', 'like', '%' . $request->note . '%');
        }
        
        // Filtro por GTIN obrigatório
        if ($request->has('requires_gtin') && $request->requires_gtin !== '') {
            $query->where('requires_gtin', $request->requires_gtin);
        }
        
        // Ordenação
        $sortBy = $request->get('sort_by', 'ncm');
        $sortDirection = $request->get('sort_direction', 'asc');
        
        if (in_array($sortBy, ['ncm', 'note', 'requires_gtin', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('ncm', 'asc');
        }
        
        // Registros por página
        $perPage = $request->get('per_page', 50);
        if (!in_array($perPage, [10, 25, 50, 100, 200, 500])) {
            $perPage = 50;
        }
        
        $rules = $query->paginate($perPage)->withQueryString();
        
        return view('admin.ncm_rules.index', compact('rules'));
    }

    public function exportRules()
    {
        $filename = 'regras_ncm_exportadas_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // BOM para UTF-8 (necessário para Excel)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Cabeçalhos
            fputcsv($file, ['NCM', 'Requer GTIN (1 ou 0)', 'Observação'], ';');
            
            // Buscar todas as regras ordenadas por NCM (sem pontos, apenas dígitos)
            $rules = NcmRule::orderBy('ncm', 'asc')->get();
            
            // Exportar cada regra (garantir NCM sem pontos)
            foreach ($rules as $rule) {
                // Normalizar NCM removendo pontos e outros caracteres não numéricos
                $ncmNormalizado = preg_replace('/\D+/', '', $rule->ncm);
                
                fputcsv($file, [
                    $ncmNormalizado, // NCM sem pontos garantido
                    $rule->requires_gtin ? '1' : '0',
                    $rule->note ?? ''
                ], ';');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    public function importForm()
    {
        return view('admin.ncm_rules.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'], // 5MB max
        ]);
        
        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        
        // Verificar e pular BOM UTF-8 se existir
        $firstBytes = fread($handle, 3);
        if ($firstBytes !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            // Não é BOM, voltar ao início
            rewind($handle);
        }
        
        $stats = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
            'skipped' => 0,
        ];
        
        $lineNumber = 0;
        $isFirstLine = true;
        
        while (($row = fgetcsv($handle, 1000, ';')) !== false) {
            $lineNumber++;
            
            // Pular cabeçalho
            if ($isFirstLine) {
                $isFirstLine = false;
                // Verificar se é cabeçalho
                if (isset($row[0]) && (stripos($row[0], 'ncm') !== false || stripos($row[0], 'NCM') !== false)) {
                    continue;
                }
            }
            
            // Validar se tem pelo menos NCM
            if (empty($row[0])) {
                $stats['skipped']++;
                continue;
            }
            
            // Normalizar NCM (remover pontos e outros caracteres, deixar apenas dígitos)
            $ncm = preg_replace('/\D+/', '', $row[0] ?? '');
            
            if (strlen($ncm) < 4 || strlen($ncm) > 20) {
                $stats['errors'][] = "Linha {$lineNumber}: NCM inválido '{$row[0]}'";
                continue;
            }
            
            $requiresGtin = isset($row[1]) ? (in_array(strtolower(trim($row[1])), ['1', 'true', 'sim', 'yes', 'obrigatorio', 'obrigatório']) ? true : false) : false;
            $note = isset($row[2]) ? trim($row[2]) : null;
            
            if ($note && strlen($note) > 255) {
                $note = substr($note, 0, 255);
            }
            
            $stats['processed']++;
            
            // Buscar ou criar regra (global, sem tenant_id)
            $existing = NcmRule::where('ncm', $ncm)->first();
            
            if ($existing) {
                // Atualizar existente
                $before = $existing->getOriginal();
                $existing->update([
                    'requires_gtin' => $requiresGtin,
                    'note' => $note,
                ]);
                $after = $existing->fresh();
                
                $changes = [];
                foreach (['requires_gtin', 'note'] as $f) {
                    if (($before[$f] ?? null) != ($after->$f ?? null)) {
                        $changes[$f] = ['old' => $before[$f] ?? null, 'new' => $after->$f ?? null];
                    }
                }
                
                if (!empty($changes)) {
                    $stats['updated']++;
                    \App\Models\NcmRuleAudit::create([
                        'ncm_rule_id' => $existing->id,
                        'user_id' => auth()->id(),
                        'action' => 'updated',
                        'notes' => 'Regra NCM atualizada via importação CSV pelo admin (linha ' . $lineNumber . ')',
                        'changes' => $changes,
                    ]);
                } else {
                    $stats['skipped']++;
                }
            } else {
                // Criar nova
                $rule = NcmRule::create([
                    'ncm' => $ncm,
                    'requires_gtin' => $requiresGtin,
                    'note' => $note,
                ]);
                
                $stats['created']++;
                
                \App\Models\NcmRuleAudit::create([
                    'ncm_rule_id' => $rule->id,
                    'user_id' => auth()->id(),
                    'action' => 'created',
                    'notes' => 'Regra NCM criada via importação CSV pelo admin (linha ' . $lineNumber . ')',
                    'changes' => [
                        'ncm' => $rule->ncm,
                        'requires_gtin' => $rule->requires_gtin,
                        'note' => $rule->note,
                    ],
                ]);
            }
        }
        
        fclose($handle);
        
        // Preparar mensagem de resultado
        $message = "Importação concluída! ";
        $message .= "Total processado: {$stats['processed']}. ";
        $message .= "Novas: {$stats['created']}. ";
        $message .= "Atualizadas: {$stats['updated']}. ";
        
        if ($stats['skipped'] > 0) {
            $message .= "Ignoradas (sem alterações): {$stats['skipped']}. ";
        }
        
        if (!empty($stats['errors'])) {
            $message .= "Erros: " . count($stats['errors']) . ". ";
        }
        
        $sessionData = [
            'success' => $message,
            'import_stats' => $stats,
        ];
        
        return redirect()->route('admin.ncm_rules.index')->with($sessionData);
    }

    public function destroy(NcmRule $ncm_rule)
    {
        $id = $ncm_rule->id;
        $snapshot = ['ncm' => $ncm_rule->ncm, 'requires_gtin' => $ncm_rule->requires_gtin, 'note' => $ncm_rule->note];
        $ncm_rule->delete();
        \App\Models\NcmRuleAudit::create([
            'ncm_rule_id' => $id,
            'user_id' => auth()->id(),
            'action' => 'deleted',
            'notes' => 'Regra NCM excluída pelo admin',
            'changes' => $snapshot,
        ]);
        return redirect()->route('admin.ncm_rules.index')->with('success','Regra excluída.');
    }
}
