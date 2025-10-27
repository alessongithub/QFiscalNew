<?php

namespace App\Http\Controllers;

use App\Models\NfeNote;
use App\Models\Client;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\Receivable;
use App\Models\Order;
use App\Models\ReturnModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\TaxCreditService;
use Illuminate\Support\Facades\Mail;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NfeController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('nfe.view'), 403);
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $query = NfeNote::with('client')
            ->where('tenant_id', $tenantId);

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_pedido', 'like', "%{$search}%")
                  ->orWhere('numero_nfe', 'like', "%{$search}%")
                  ->orWhere('chave_acesso', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $notes = $query->orderByDesc('created_at')->paginate(15);
        // Compat: algumas views esperam $nfes
        return view('nfe.index', ['notes' => $notes, 'nfes' => $notes]);
    }

    public function show(NfeNote $nfeNote)
    {
        abort_unless(auth()->user()->hasPermission('nfe.view'), 403);
        abort_unless($nfeNote->tenant_id === auth()->user()->tenant_id, 403);
        
        return view('nfe.show', compact('nfeNote'));
    }

    public function generateDanfeErp(NfeNote $nfeNote)
    {
        abort_unless(auth()->user()->hasPermission('nfe.view'), 403);
        abort_unless($nfeNote->tenant_id === auth()->user()->tenant_id, 403);
        $xmlPath = (string) ($nfeNote->xml_resolved_path ?: $nfeNote->xml_path ?: $nfeNote->arquivo_xml);
        if (!$xmlPath || !file_exists($xmlPath)) {
            return back()->with('error','XML não encontrado para gerar o DANFE.');
        }
        try {
            $xml = file_get_contents($xmlPath);
            // Render simples de DANFE (layout básico) como fallback via Dompdf
            $dom = new \DOMDocument();
            @$dom->loadXML($xml);
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
            $chNFe = $xpath->evaluate('string(//nfe:infProt/nfe:chNFe)') ?: $xpath->evaluate('string(//nfe:infNFe/@Id)');
            $emit = [
                'xNome' => $xpath->evaluate('string(//nfe:emit/nfe:xNome)'),
                'CNPJ'  => $xpath->evaluate('string(//nfe:emit/nfe:CNPJ)'),
            ];
            $dest = [
                'xNome' => $xpath->evaluate('string(//nfe:dest/nfe:xNome)'),
                'CNPJ'  => $xpath->evaluate('string(//nfe:dest/nfe:CNPJ)') ?: $xpath->evaluate('string(//nfe:dest/nfe:CPF)'),
            ];
            $ide = [
                'nNF'   => $xpath->evaluate('string(//nfe:ide/nfe:nNF)'),
                'serie' => $xpath->evaluate('string(//nfe:ide/nfe:serie)'),
                'dhEmi' => $xpath->evaluate('string(//nfe:ide/nfe:dhEmi)'),
            ];
            $total = [
                'vNF' => $xpath->evaluate('string(//nfe:ICMSTot/nfe:vNF)')
            ];
            $itens = $xpath->query('//nfe:det');
            $rows = '';
            foreach ($itens as $det) {
                $xProd = $xpath->evaluate('string(.//nfe:prod/nfe:xProd)', $det);
                $qCom = $xpath->evaluate('string(.//nfe:prod/nfe:qCom)', $det);
                $vUn  = $xpath->evaluate('string(.//nfe:prod/nfe:vUnCom)', $det);
                $vProd= $xpath->evaluate('string(.//nfe:prod/nfe:vProd)', $det);
                $rows .= '<tr><td>'.htmlspecialchars($xProd).'</td><td style="text-align:right">'.htmlspecialchars($qCom).'</td><td style="text-align:right">'.htmlspecialchars($vUn).'</td><td style="text-align:right">'.htmlspecialchars($vProd).'</td></tr>';
            }
            $isCancelled = in_array(strtolower((string)$nfeNote->status), ['cancelled','cancelada']);
            $html = '<html><head><meta charset="utf-8"><style>body{font-family:sans-serif;font-size:12px} h1{font-size:16px;margin:0 0 8px} table{width:100%;border-collapse:collapse} th,td{border:1px solid #ddd;padding:6px} .muted{color:#555} .b{font-weight:600} .tarja{background:#b91c1c;color:#fff;text-align:center;padding:8px 0;margin-bottom:8px;font-weight:700;letter-spacing:2px}</style></head><body>'
                .($isCancelled?'<div class="tarja">CANCELADA</div>':'')
                .'<h1>DANFE (fallback ERP)</h1>'
                .'<div class="muted">Este PDF foi gerado pelo ERP a partir do XML autorizado.</div>'
                .'<p class="b">Chave de Acesso: '.htmlspecialchars($chNFe).'</p>'
                .'<p><span class="b">Emitente:</span> '.htmlspecialchars($emit['xNome']).' ('.htmlspecialchars($emit['CNPJ']).')</p>'
                .'<p><span class="b">Destinatário:</span> '.htmlspecialchars($dest['xNome']).' ('.htmlspecialchars($dest['CNPJ']).')</p>'
                .'<p><span class="b">NF:</span> '.htmlspecialchars($ide['nNF']).' Série '.htmlspecialchars($ide['serie']).' — Emissão: '.htmlspecialchars($ide['dhEmi']).'</p>'
                .'<table><thead><tr><th>Produto</th><th>Qtd</th><th>V.Unit</th><th>V.Total</th></tr></thead><tbody>'.$rows.'</tbody></table>'
                .'<p style="text-align:right" class="b">Valor da NF-e: R$ '.htmlspecialchars($total['vNF']).'</p>'
                .'</body></html>';
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4');
                return $pdf->download('DANFE_'.($ide['nNF'] ?: 'NFe').'.pdf');
            }
            if (class_exists(\Barryvdh\DomPDF\Facades\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facades\Pdf::loadHTML($html)->setPaper('a4');
                return $pdf->download('DANFE_'.($ide['nNF'] ?: 'NFe').'.pdf');
            }
            return back()->with('error','Biblioteca PDF não instalada. Rode: composer require barryvdh/laravel-dompdf');
        } catch (\Throwable $e) {
            \Log::error('Falha ao gerar DANFE ERP', ['id'=>$nfeNote->id, 'error'=>$e->getMessage()]);
            return back()->with('error','Falha ao gerar DANFE no ERP: '.$e->getMessage());
        }
    }

    public function generateDanfe(NfeNote $nfeNote)
    {
        abort_unless(auth()->user()->hasPermission('nfe.view'), 403);
        abort_unless($nfeNote->tenant_id === auth()->user()->tenant_id, 403);
        $xmlPath = (string) ($nfeNote->xml_resolved_path ?: $nfeNote->xml_path ?: $nfeNote->arquivo_xml);
        if (!$xmlPath || !file_exists($xmlPath)) {
            return back()->with('error','XML não encontrado para gerar o DANFE.');
        }
        try {
            $delphiUrl = \App\Models\Setting::getGlobal('services.delphi.url', config('services.delphi.url'));
            $delphiTimeout = (int) \App\Models\Setting::getGlobal('services.delphi.timeout', (int) config('services.delphi.timeout', 30));
            $delphiToken = (string) \App\Models\Setting::getGlobal('services.delphi.token', (string) config('services.delphi.token', ''));
            // x-token|bearer|query|none
            $authPref = (string) \App\Models\Setting::getGlobal('services.delphi.auth', (string) config('services.delphi.auth', 'x-token'));

            if (!$delphiUrl) {
                return redirect()->route('nfe.danfe_erp', $nfeNote)->with('warning', 'Emissor não configurado; gerando DANFE pelo ERP.');
            }

            $isCancelled = in_array(strtolower((string)$nfeNote->status), ['cancelled','cancelada']);
            $payload = [
                'xml_path' => $xmlPath,
                // Sinaliza que queremos apenas gerar o PDF com base no XML já autorizado
                'gerar_pdf' => true,
                'logo_path' => base_path('logo/logo.bmp'),
                // Envia bloco de configuracoes para máxima compatibilidade
                'configuracoes' => [
                    'gerar_pdf' => true,
                    'logo_path' => base_path('logo/logo.bmp'),
                    // Pede tarja CANCELADA quando status cancelado
                    'tarja_cancelada' => $isCancelled,
                    'cancelada' => $isCancelled ? '1' : '0',
                    'status' => $isCancelled ? 'CANCELADA' : (string) $nfeNote->status,
                ],
            ];

            // Ordem de tentativa de autenticação
            switch ($authPref) {
                case 'bearer': $candidates = ['bearer','x-token','query','none']; break;
                case 'query': $candidates = ['query','x-token','bearer','none']; break;
                case 'none': $candidates = ['none','x-token','bearer','query']; break;
                default: $candidates = ['x-token','bearer','query','none'];
            }

            $finalResponse = null; $lastError = null; $usedScheme = null; $urlUsed = null;
            foreach ($candidates as $scheme) {
                try {
                    $url = rtrim((string)$delphiUrl, '/') . '/api/gerar-danfe';
                    $http = \Illuminate\Support\Facades\Http::timeout($delphiTimeout);
                    if ($delphiToken !== '') {
                        if ($scheme === 'bearer') {
                            $http = $http->withHeaders(['Authorization' => 'Bearer ' . $delphiToken]);
                        } elseif ($scheme === 'x-token') {
                            $http = $http->withHeaders([
                                'X-Token' => $delphiToken,
                                'X-Authorization' => $delphiToken,
                                'X-Api-Token' => $delphiToken,
                            ]);
                        } elseif ($scheme === 'query') {
                            $url .= (str_contains($url, '?') ? '&' : '?') . 'token=' . urlencode($delphiToken);
                        }
                    }
                    $resp = $http->post($url, $payload);
                    if ($resp->successful()) { $finalResponse = $resp; $usedScheme = $scheme; $urlUsed = $url; break; }
                    $lastError = ['status' => $resp->status(), 'body' => $resp->body(), 'scheme' => $scheme];
                } catch (\Throwable $e) {
                    $lastError = ['exception' => $e->getMessage(), 'scheme' => $scheme];
                }
            }

            if (!$finalResponse) {
                return redirect()->route('nfe.danfe_erp', $nfeNote)
                    ->with('warning', 'Falha de autenticação com o emissor ('.(($lastError['scheme'] ?? 'n/d')).'). Gerando DANFE pelo ERP.');
            }

            $data = $finalResponse->json();
            $pdf = $data['pdf_path'] ?? null;
            if ($pdf && file_exists($pdf)) {
                $nfeNote->update(['pdf_path' => $pdf]);
                $filename = basename($pdf);
                return response()->file($pdf, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $filename . '"'
                ]);
            }
            return redirect()->route('nfe.danfe_erp', $nfeNote)->with('warning', 'Emissor não retornou PDF; gerando pelo ERP.');
        } catch (\Throwable $e) {
            \Log::error('Falha ao gerar DANFE via emissor', ['id'=>$nfeNote->id, 'error'=>$e->getMessage()]);
            return redirect()->route('nfe.danfe_erp', $nfeNote)->with('warning', 'Erro ao contatar o emissor; gerando DANFE pelo ERP.');
        }
    }

    public function emitir(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('nfe.emit'), 403);
        
        // Debug para verificar se client_id está chegando
        Log::info('NFe emitir request', $request->all());
        
        $request->validate([
            'numero_pedido' => 'required|string|max:50',
            'client_id' => 'nullable|exists:clients,id', // Tornar opcional para buscar do pedido
            // Produtos serão obrigatórios somente se não houver pedido salvo
            'produtos' => 'nullable|array',
            'produtos.*.product_id' => 'required_with:produtos|exists:products,id',
            'produtos.*.quantity' => 'required_with:produtos|numeric|min:0.001',
            'produtos.*.unit_price' => 'required_with:produtos|numeric|min:0',
            // Adicionar validação para descontos por item
            'item_discounts' => 'nullable|array',
            'item_discounts.*' => 'nullable|numeric|min:0',
            'discount_total_override' => 'nullable|numeric|min:0',
        ]);

        $user = auth()->user();
        $tenantId = $user->tenant_id;
        
        // Buscar o pedido primeiro para obter o cliente
        $order = \App\Models\Order::where('number', $request->numero_pedido)
            ->where('tenant_id', $tenantId)
            ->first();
            
        if (!$order) {
            return response()->json(['error' => 'Pedido não encontrado'], 400);
        }
        
        // Usar client_id da requisição ou do pedido; se não houver, aceitar CPF do modal e vincular a um Consumidor Final
        $clientId = $request->client_id ?: $order->client_id;
        if (!$clientId) {
            $cpfModal = preg_replace('/\D/', '', (string) $request->input('cpf_modal'));
            if (!$cpfModal || strlen($cpfModal) !== 11) {
                return response()->json(['error' => 'Informe um CPF válido para o destinatário.'], 400);
            }
            // Localiza ou cria cliente Consumidor Final
            $cliente = Client::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'cpf_cnpj' => $cpfModal,
                ],
                [
                    'name' => 'Consumidor Final',
                    'type' => 'pf',
                    'address' => optional($order->tenant)->address ?? '',
                    'number' => optional($order->tenant)->number ?? '',
                    'neighborhood' => optional($order->tenant)->neighborhood ?? '',
                    'city' => optional($order->tenant)->city ?? '',
                    'state' => optional($order->tenant)->state ?? '',
                    'zip_code' => optional($order->tenant)->zip_code ?? '',
                ]
            );
            // vincula ao pedido
            $order->client_id = $cliente->id;
            $order->save();
            $clientId = $cliente->id;
        }
        
        $cliente = Client::findOrFail($clientId);

        // Bloqueio: validar dados obrigatórios do cliente antes de emitir
        $requiredMap = [
            'name' => 'Nome',
            'cpf_cnpj' => 'CPF/CNPJ',
            'address' => 'Endereço',
            'number' => 'Número',
            'neighborhood' => 'Bairro',
            'city' => 'Cidade',
            'state' => 'UF',
            'zip_code' => 'CEP',
        ];
        $missing = [];
        foreach ($requiredMap as $field => $label) {
            if (empty($cliente->{$field})) { $missing[] = $label; }
        }
        if ((int)($cliente->codigo_municipio ?: $cliente->codigo_ibge ?: 0) === 0) {
            $missing[] = 'Código do Município (IBGE)';
        }
        if (count($missing) > 0) {
            return response()->json([
                'error' => 'Complete os dados do cliente para emitir a NF-e: ' . implode(', ', $missing),
                'code' => 'CLIENT_DATA_INCOMPLETE'
            ], 422);
        }

        // Não criar registro antes do sucesso de emissão
        $nfeNote = null;

        try {
            // Pré-validações antes do envio
            $order = \App\Models\Order::where('number', $request->numero_pedido)
                ->where('tenant_id', auth()->user()->tenant_id)
                ->first();

            // Validar produtos (NCM/CFOP/CST básicos)
            $reqProdutos = $request->input('produtos');
            if (is_array($reqProdutos)) {
                foreach ($reqProdutos as $p) {
                    $prod = Product::findOrFail($p['product_id']);
                    if (empty($prod->ncm)) { return response()->json(['error' => 'Produto '.$prod->name.' sem NCM.'], 400); }
                    if (empty($prod->cfop)) { return response()->json(['error' => 'Produto '.$prod->name.' sem CFOP.'], 400); }
                    if (empty($prod->cst_icms) && empty($prod->csosn)) { return response()->json(['error' => 'Produto '.$prod->name.' sem CST/CSOSN.'], 400); }
                }
            } elseif ($order) {
                // Validar pelos itens do pedido
                $orderItems = \App\Models\OrderItem::where('order_id', $order->id)->get();
                foreach ($orderItems as $it) {
                    if (!$it->product_id) { continue; }
                    $prod = Product::find($it->product_id);
                    if (!$prod) { continue; }
                    if (empty($prod->ncm)) { return response()->json(['error' => 'Produto '.$prod->name.' sem NCM.'], 400); }
                    if (empty($prod->cfop)) { return response()->json(['error' => 'Produto '.$prod->name.' sem CFOP.'], 400); }
                    if (empty($prod->cst_icms) && empty($prod->csosn)) { return response()->json(['error' => 'Produto '.$prod->name.' sem CST/CSOSN.'], 400); }
                }
            }
            // Validar frete quando houver produtos físicos e modalidade exigir (usa overrides do modal se informados)
            if ($order) {
                $hasPhysicalProducts = \App\Models\OrderItem::where('order_id', $order->id)
                    ->whereNotNull('product_id')
                    ->whereIn('product_id', function($q){ $q->select('id')->from('products')->where('type','product'); })
                    ->exists();
                $fm = (int) ($request->input('freight_mode', $order->freight_mode ?? 9));
                $carrierId = $request->input('carrier_id', $order->carrier_id);
                $freightCost = $request->input('freight_cost', $order->freight_cost);
                if ($hasPhysicalProducts && $fm !== 9) {
                    if (in_array($fm, [0,2], true)) {
                        if (empty($carrierId)) { return response()->json(['error'=>'Frete exige transportadora.'], 400); }
                        if ($freightCost === null) { return response()->json(['error'=>'Informe o valor do frete.'], 400); }
                    }
                    $volumeQtd = (int) $request->input('volume_qtd', $order->volume_qtd ?? 0);
                    $pesoBruto = (float) $request->input('peso_bruto', $order->peso_bruto ?? 0);
                    if ($volumeQtd <= 0 || $pesoBruto <= 0) {
                        return response()->json(['error'=>'Informe volumes e peso para o frete.'], 400);
                    }
                }
            }
            // (Removido bloqueio duro) Compat: receivíveis podem divergir do cálculo fiscal.
            // A reconciliação será feita na montagem dos pagamentos abaixo com base em vNF.
            // Validar natureza/finalidade
            $nat = $request->input('natOp');
            $fin = $request->input('finNFe');
            if (empty($nat)) { return response()->json(['error'=>'Informe a Natureza da Operação (natOp).'], 400); }
            if (empty($fin)) { return response()->json(['error'=>'Informe a Finalidade da NF-e (finNFe).'], 400); }

            // Regras adicionais para devolução
            $operationType = (string) $request->input('operation_type', 'venda');
            if ((int)$fin === 4 || in_array($operationType, ['devolucao_venda','devolucao_compra'], true)) {
                $refKey = trim((string)$request->input('reference_key', ''));
                if (strlen($refKey) !== 44) {
                    return response()->json(['error' => 'Informe a chave da NF-e referenciada (44 dígitos) para a devolução.'], 400);
                }
                if ($operationType === 'devolucao_venda') {
                    if (!$order) {
                        return response()->json(['error'=>'Pedido não localizado para validar devolução.'], 400);
                    }
                    $hasReturn = ReturnModel::where('tenant_id', $tenantId)
                        ->where('order_id', $order->id)
                        ->exists();
                    if (!$hasReturn) {
                        return response()->json(['error'=>'Registre a devolução do pedido antes de emitir a NF-e de devolução.'], 400);
                    }
                }
            }

            // Definir candidato de numeração sem reservar globalmente; commit apenas após sucesso
            $serieConfigured = (string) (Setting::get('nfe.series', '1'));
            $nextCandidate = null;
            try {
                $configuredNext = (int) ((string) Setting::get('nfe.next_number.series.' . $serieConfigured, '0'));
                $emitter = \App\Models\TenantEmitter::where('tenant_id', $tenantId)->first();
                $emitterCurrent = (int) ($emitter?->numero_atual_nfe ?: 0);
                $maxNumero = (int) (\App\Models\NfeNote::where('tenant_id', $tenantId)
                    ->when(\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes','serie_nfe'), function($q) use ($serieConfigured){
                        $q->where(function($qq) use ($serieConfigured){ $qq->where('serie_nfe',$serieConfigured)->orWhereNull('serie_nfe'); });
                    })
                    ->whereNotNull('numero_nfe')->where('numero_nfe','!=','')
                    ->orderByRaw('CAST(numero_nfe AS UNSIGNED) DESC')
                    ->value(\Illuminate\Support\Facades\DB::raw('CAST(numero_nfe AS UNSIGNED)')) ?? 0);
                $calcNext = max(1, $maxNumero + 1);
                $nextCandidate = max($calcNext, $configuredNext, ($emitterCurrent > 0 ? $emitterCurrent + 1 : 0));
            } catch (\Throwable $e) {
                $nextCandidate = null; // emissor definirá
            }

            // Se usuário não tiver permissão de desconto, bloquear override e limpa item_discounts
            $canDiscount = auth()->user()->hasPermission('orders.discount');
            if (!$canDiscount) {
                $request->merge(['discount_total_override' => null, 'item_discounts' => []]);
            }

            // Não persistir alterações de desconto vindas do modal aqui.
            // O pedido deve ser salvo em /orders/edit; a emissão usa os valores do banco e faz rateio apenas no payload.

            // Formatar payload para o Delphi e injetar série/número reservados
            $payload = $this->formatPayload($request, $cliente);
            // Ajustar indIEDest automaticamente conforme tipo do cliente e IE informada
            try {
                $isPJ = (strtolower((string)$cliente->type) !== 'pf');
                $ieVal = trim((string) ($cliente->ie_rg ?? ''));
                // indIEDest: 1=Contribuinte ICMS; 2=Contribuinte Isento; 9=Não contribuinte
                $indIEDest = 9;
                if ($isPJ) {
                    if ($ieVal !== '' && !preg_match('/^(ISENTO|ISENTA)$/i', $ieVal)) { $indIEDest = 1; }
                    else { $indIEDest = 2; }
                }
                if (!isset($payload['configuracoes']) || !is_array($payload['configuracoes'])) { $payload['configuracoes'] = []; }
                $payload['configuracoes']['indIEDest'] = (int) $indIEDest;
                // Também envia IE no bloco cliente quando contribuinte
                if (!isset($payload['cliente']) || !is_array($payload['cliente'])) { $payload['cliente'] = []; }
                if ($indIEDest === 1 || $indIEDest === 2) { $payload['cliente']['ie'] = $ieVal !== '' ? $ieVal : 'ISENTO'; }
            } catch (\Throwable $e) {}
            $payload['serie'] = (int) $serieConfigured;
            if ($nextCandidate) {
                $payload['numero'] = (int) $nextCandidate;
                $payload['numero_nfe'] = (int) $nextCandidate;
            }
            if (is_array($payload['configuracoes'] ?? null)) { $payload['configuracoes']['serie'] = (string) $serieConfigured; }
            
            // Integração: cálculo de ICMS com créditos fiscais e sugestões
            $taxCreditService = app(TaxCreditService::class);
            $icmsSuggestions = [];
            $creditsUsed = [];
            $icmsTotal = 0.0;
            $icmsCreditUsedTotal = 0.0;

            if (!empty($payload['produtos']) && is_array($payload['produtos'])) {
                foreach ($payload['produtos'] as $idx => $p) {
                    try {
                        $prodModel = Product::find($p['id']);
                        if (!$prodModel) { continue; }

                        // Base de cálculo por item: (vProd - vDesc) + vFrete + vSeg + vOutro
                        $vProd = (float)($p['valor_total'] ?? 0);
                        $vDesc = (float)($p['vDesc'] ?? 0);
                        $vFrete = (float)($p['vFrete'] ?? 0);
                        $vSeg = (float)($p['vSeg'] ?? 0);
                        $vOutro = (float)($p['vOutro'] ?? 0);
                        $baseIcms = max($vProd - $vDesc, 0.0) + $vFrete + $vSeg + $vOutro;

                        // Alíquota: do produto ou da regra por NCM/CFOP
                        $rate = TaxRate::where('tenant_id', $tenantId)
                            ->where('tipo_nota', 'produto')
                            ->where(function($q) use ($prodModel) {
                                $q->where('ncm', $prodModel->ncm)->orWhere('cfop', $prodModel->cfop);
                            })
                            ->where('ativo', 1)
                            ->orderByRaw("CASE WHEN ncm = ? AND cfop = ? THEN 0 WHEN ncm = ? THEN 1 WHEN cfop = ? THEN 2 ELSE 3 END", [$prodModel->ncm, $prodModel->cfop, $prodModel->ncm, $prodModel->cfop])
                            ->first();
                        $aliquota = (float)($prodModel->aliquota_icms ?? ($rate->icms_aliquota ?? 0));

                        $calc = $taxCreditService->calculateIcmsWithCredits(
                            $prodModel,
                            (float) $baseIcms,
                            $aliquota,
                            (float)($p['quantidade'] ?? 0),
                            $tenantId
                        );

                        // Atualiza produto no payload com base e valor de ICMS final (após créditos)
                        $payload['produtos'][$idx]['base_icms'] = round($baseIcms, 2);
                        $icmsDue = (float)($calc['icms_due'] ?? 0.0);
                        // Normaliza para reais quando vier em centavos
                        if ($icmsDue > $baseIcms) { $icmsDue = $icmsDue / 100.0; }
                        $payload['produtos'][$idx]['valor_icms'] = round($icmsDue, 2);
                        $payload['produtos'][$idx]['aliquota_icms'] = $aliquota; // garante consistência

                        $icmsTotal += (float)$payload['produtos'][$idx]['valor_icms'];
                        $icmsCreditUsedTotal += (float)($calc['icms_credit_used'] ?? 0.0);

                        if (!empty($calc['suggestion'])) {
                            $icmsSuggestions[] = $prodModel->name . ': ' . $calc['suggestion'];
                        }

                        // Acumula créditos usados
                        foreach ($calc['credits_used'] as $cu) {
                            $creditsUsed[] = $cu;
                        }
                    } catch (\Throwable $e) {
                        // Em caso de erro no cálculo de um item, segue para os demais
                        Log::warning('Falha no cálculo de ICMS com créditos para item', [
                            'product_id' => $p['id'] ?? null,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Override manual (se permitido)
            $overrideIcms = $request->input('icms_override_total');
            if ($overrideIcms !== null && auth()->user()->hasPermission('nfe.override_icms')) {
                $overrideVal = max(0.0, (float)$overrideIcms);
                if (!isset($payload['totais'])) { $payload['totais'] = []; }
                $payload['totais']['icms_override_total'] = (float) $overrideVal;
            }

            // Normaliza impostos por item (reais, 2 casas) evitando centavos inteiros
            if (!empty($payload['produtos']) && is_array($payload['produtos'])) {
                foreach ($payload['produtos'] as $ii => $pp) {
                    foreach (['valor_icms','valor_pis','valor_cofins'] as $k) {
                        if (isset($pp[$k])) {
                            $val = (float) $pp[$k];
                            if ($val > 100) { $val = $val / 100.0; }
                            $payload['produtos'][$ii][$k] = round($val, 2);
                        }
                    }
                }
            }

            // Recalcula totais após normalização (em reais com 2 casas)
            try {
                $totalVProd = 0.0; $totalDesc = 0.0; $totalFrete = 0.0; $totalSeg = 0.0; $totalOutro = 0.0;
                $totalICMS = 0.0; $totalPIS = 0.0; $totalCOFINS = 0.0; $totalCBS = 0.0;
                foreach (($payload['produtos'] ?? []) as $pSum) {
                    $totalVProd += (float)($pSum['valor_total'] ?? 0);
                    // Limita desconto de item ao seu valor bruto p/ evitar rejeição "Total do Desconto difere do somatório dos itens"
                    $lineDesc = (float)($pSum['vDesc'] ?? 0);
                    $lineTotal = (float)($pSum['valor_total'] ?? 0);
                    if ($lineDesc > $lineTotal) { $lineDesc = $lineTotal; }
                    $totalDesc += $lineDesc;
                    $totalFrete += (float)($pSum['vFrete'] ?? 0);
                    $totalSeg += (float)($pSum['vSeg'] ?? 0);
                    $totalOutro += (float)($pSum['vOutro'] ?? 0);
                    $totalICMS += (float)($pSum['valor_icms'] ?? 0);
                    $totalPIS += (float)($pSum['valor_pis'] ?? 0);
                    $totalCOFINS += (float)($pSum['valor_cofins'] ?? 0);
                    $totalCBS += (float)($pSum['cbs_valor'] ?? 0);
                }
                $vNF = max(0.0, ($totalVProd - $totalDesc) + $totalFrete + $totalSeg + $totalOutro);
                if (!isset($payload['totais'])) { $payload['totais'] = []; }
                $payload['totais']['vProd'] = number_format((float)$totalVProd, 2, '.', '');
                // Reforça o desconto total em dois níveis para máxima compatibilidade com diversos emissores Delphi
                $payload['totais']['vDesc'] = number_format((float)$totalDesc, 2, '.', '');
                if (!isset($payload['ICMSTot'])) { $payload['ICMSTot'] = []; }
                $payload['ICMSTot']['vDesc'] = number_format((float)$totalDesc, 2, '.', '');
                $payload['totais']['vFrete'] = number_format((float)$totalFrete, 2, '.', '');
                $payload['totais']['vSeg'] = number_format((float)$totalSeg, 2, '.', '');
                $payload['totais']['vOutro'] = number_format((float)$totalOutro, 2, '.', '');
                $payload['totais']['vNF'] = number_format((float)$vNF, 2, '.', '');
                $payload['totais']['ICMSTot'] = number_format((float)$totalICMS, 2, '.', '');
                $payload['totais']['PIS'] = number_format((float)$totalPIS, 2, '.', '');
                $payload['totais']['COFINS'] = number_format((float)$totalCOFINS, 2, '.', '');
                $payload['totais']['CBS'] = number_format((float)$totalCBS, 2, '.', '');
            } catch (\Throwable $e) {}

            // Sanitização final de totais/pagamentos para casar com layout NFe
            try {
                // Força ICMSTot como objeto completo; remove PIS/COFINS de nível de totais
                if (isset($payload['totais'])) {
                    if (!is_array($payload['totais']['ICMSTot'] ?? null)) {
                        $payload['totais']['ICMSTot'] = [
                            'vBC' => number_format((float)($totalVProd - $totalDesc + $totalFrete + $totalSeg + $totalOutro), 2, '.', ''),
                            'vICMS' => number_format((float)$totalICMS, 2, '.', ''),
                            'vICMSDeson' => '0.00',
                            'vFCP' => '0.00',
                            'vBCST' => '0.00',
                            'vST' => '0.00',
                            'vFCPST' => '0.00',
                            'vFCPSTRet' => '0.00',
                            'vProd' => number_format((float)$totalVProd, 2, '.', ''),
                            'vFrete' => number_format((float)$totalFrete, 2, '.', ''),
                            'vSeg' => number_format((float)$totalSeg, 2, '.', ''),
                            'vDesc' => number_format((float)$totalDesc, 2, '.', ''),
                            'vII' => '0.00',
                            'vIPI' => '0.00',
                            'vIPIDevol' => '0.00',
                            'vPIS' => number_format((float)$totalPIS, 2, '.', ''),
                            'vCOFINS' => number_format((float)$totalCOFINS, 2, '.', ''),
                            'vCBS' => number_format((float)$totalCBS, 2, '.', ''),
                            'vOutro' => number_format((float)$totalOutro, 2, '.', ''),
                            'vNF' => number_format((float)$vNF, 2, '.', ''),
                        ];
                    }
                    unset($payload['totais']['PIS'], $payload['totais']['COFINS']);
                }
                // Remove vencimento quando tPag != '15'
                if (!empty($payload['pagamentos']) && is_array($payload['pagamentos'])) {
                    foreach ($payload['pagamentos'] as $pi => $p) {
                        if (($p['tPag'] ?? '') !== '15') {
                            unset($payload['pagamentos'][$pi]['vencimento']);
                        }
                    }
                }
            } catch (\Throwable $e) {}

            // Anexa sugestões (para exibição no frontend antes de enviar/confirmar)
            if (!empty($icmsSuggestions)) {
                $payload['icms_suggestions'] = $icmsSuggestions;
            }
            
            // Não persistir nota antes do sucesso de emissão; manter somente log
            $payloadUpdate = ['payload_sent' => $payload];
            if (!empty($nextCandidate)) { $payloadUpdate['numero_nfe'] = (string) $nextCandidate; }
            if (Schema::hasColumn('nfe_notes', 'serie_nfe')) { $payloadUpdate['serie_nfe'] = (string)$serieConfigured; }

            // Aviso e log quando houver divergência entre descontos do pedido e do modal
            try {
                if ($order) {
                    // Força uso dos valores salvos no pedido para consistência do ERP
                    $modalHeader = (float) ($order->discount_total ?? 0);
                    $modalItems = (array) $request->input('item_discounts', []);
                    $orderHeader = (float) ($order->discount_total ?? 0);
                    $orderItems = \App\Models\OrderItem::where('order_id', $order->id)->get(['id','discount_value']);
                    $diverge = abs($modalHeader - $orderHeader) > 0.001;
                    if (!$diverge && !empty($modalItems)) {
                        foreach ($modalItems as $iid => $val) {
                            $it = $orderItems->firstWhere('id', (int)$iid);
                            if ($it && abs(((float)$val) - (float)$it->discount_value) > 0.001) { $diverge = true; break; }
                        }
                    }
                    if ($diverge) {
                        \Log::warning('Divergência de desconto entre pedido e modal NFe', [
                            'order' => $order->id,
                            'numero_pedido' => $order->number,
                            'order_discount_total' => $orderHeader,
                            'modal_discount_total' => $modalHeader,
                            'user_id' => auth()->id(),
                        ]);
                        // Sinaliza no payload para exibição (frontend pode ler em response se aplicável)
                        $payload['warnings'][] = 'Os descontos informados no modal diferem dos descontos salvos no pedido.';
                    }
                }
            } catch (\Throwable $e) { }

            // Enviar para o Delphi
            $delphiUrl = \App\Models\Setting::getGlobal('services.delphi.url', config('services.delphi.url'));
            $delphiTimeout = (int) \App\Models\Setting::getGlobal('services.delphi.timeout', 30);
            $delphiToken = (string) \App\Models\Setting::getGlobal('services.delphi.token', '');

            Log::info('NFe emission: sending payload to Delphi', [
                'url' => rtrim((string)$delphiUrl, '/') . '/api/emitir-nfe',
                'tenant_id' => $tenantId,
                'pedido' => $request->numero_pedido,
                'cert' => [
                    'serial' => $payload['cert']['serial'] ?? null,
                    'has_pfx' => !empty($payload['cert']['path'] ?? null) && file_exists($payload['cert']['path']),
                ],
                'has_token' => $delphiToken !== '',
            ]);

            // Estratégias de autenticação compatíveis com Delphi (configurável em admin)
            $authPref = (string) \App\Models\Setting::getGlobal('services.delphi.auth', 'bearer'); // x-token|bearer|query|none
            $candidates = [];
            switch ($authPref) {
                case 'bearer': $candidates = ['bearer','x-token','query','none']; break;
                case 'query': $candidates = ['query','x-token','bearer','none']; break;
                case 'none': $candidates = ['none','x-token','bearer','query']; break;
                default: $candidates = ['x-token','bearer','query','none'];
            }

            $finalResponse = null; $lastError = null; $usedScheme = null;
            foreach ($candidates as $scheme) {
                try {
                    $url = rtrim((string)$delphiUrl, '/') . '/api/emitir-nfe';
                    $http = Http::timeout($delphiTimeout);
                    if ($delphiToken !== '') {
                        if ($scheme === 'bearer') {
                            $http = $http->withHeaders(['Authorization' => 'Bearer ' . $delphiToken]);
                        } elseif ($scheme === 'x-token') {
                            $http = $http->withHeaders(['X-Token' => $delphiToken]);
                            // Também envia cabeçalhos alternativos comuns
                            $http = $http->withHeaders([
                                'X-Authorization' => $delphiToken,
                                'X-Api-Token' => $delphiToken,
                            ]);
                        } elseif ($scheme === 'query') {
                            $url .= (str_contains($url, '?') ? '&' : '?') . 'token=' . urlencode($delphiToken);
                        } // none => sem cabeçalho
                    }
                    Log::info('NFe emission try', ['scheme' => $scheme, 'url' => $url]);
                    $resp = $http->post($url, $payload);
                    // Loga status e trecho do corpo para diagnóstico
                    try {
                        $bodySnippet = mb_substr($resp->body(), 0, 1000);
                    } catch (\Throwable $e) {
                        $bodySnippet = null;
                    }
                    Log::info('NFe emission response', [
                        'status' => $resp->status(),
                        'body_snippet' => $bodySnippet,
                    ]);
                    if ($resp->successful()) {
                        $finalResponse = $resp; $usedScheme = $scheme; break;
                    }
                    $status = $resp->status();
                    $lastError = ['status' => $status, 'body' => $resp->body()];
                    // Em 401/403 tente próximo esquema; em outros status também tentamos próximo esquema
                    continue;
                } catch (\Throwable $e) {
                    $lastError = ['exception' => $e->getMessage()];
                }
            }
            $response = $finalResponse ?? (isset($resp) ? $resp : null);
            Log::info('NFe emission auth result', ['used_scheme' => $usedScheme, 'last_error' => $lastError]);

            // Quando não houve resposta (ex.: conexão recusada/timeout em todas as tentativas)
            if ($response === null) {
                $msg = 'Sistema de emissão indisponível no momento. Tente novamente em alguns minutos.';
                try {
                    $dumpDir = storage_path('logs');
                    if (!is_dir($dumpDir)) { @mkdir($dumpDir, 0777, true); }
                    $dumpPath = $dumpDir . DIRECTORY_SEPARATOR . 'nfe_emit_no_response_' . now()->format('Ymd_His_u') . '.json';
                    $dump = [
                        'when' => now()->toDateTimeString(),
                        'tenant_id' => $tenantId,
                        'pedido' => $request->numero_pedido,
                        'serie' => $serieConfigured,
                        'numero_candidate' => $nextCandidate,
                        'last_error' => $lastError,
                    ];
                    @file_put_contents($dumpPath, json_encode($dump, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                    Log::info('NFe emission no-response dump saved', ['path' => $dumpPath]);
                } catch (\Throwable $e) { /* ignore */ }
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => $msg,
                        'code' => 'EMISSOR_OFFLINE',
                    ], 503);
                }
                return back()->with('error', $msg);
            }

            if ($response->successful()) {
                $responseData = $response->json();
                
                if ($responseData['ok'] ?? false) {
                    // Mapeia campos do emissor para colunas do nosso modelo (compatível com schema atual)
                    $cStat = (int) ($responseData['cStat'] ?? 0);
                    $statusMapped = $cStat === 100 ? 'emitted' : 'pending';
                    $dataNode = is_array($responseData['data'] ?? null) ? $responseData['data'] : [];
                    $numeroNfe = $responseData['numero'] ?? ($responseData['nNF'] ?? ($dataNode['numero'] ?? $dataNode['nNF'] ?? null));
                    $chave = $responseData['chave_acesso'] ?? ($responseData['chNFe'] ?? ($responseData['chave'] ?? ($dataNode['chave_acesso'] ?? $dataNode['chNFe'] ?? $dataNode['chave'] ?? null)));
                    $prot = $responseData['protocolo'] ?? ($responseData['nProt'] ?? ($dataNode['protocolo'] ?? $dataNode['nProt'] ?? null));
                    $xml = $responseData['xml_path'] ?? ($responseData['xml'] ?? ($dataNode['xml_path'] ?? $dataNode['xml'] ?? null));
                    $pdf = $responseData['pdf_path'] ?? ($responseData['danfe_pdf'] ?? ($dataNode['pdf_path'] ?? $dataNode['danfe_pdf'] ?? null));

                    // Fallback: se status não veio 100 mas o XML existe com protNFe cStat=100, marca como emitida
                    if ($statusMapped !== 'emitted' && $xml && @file_exists($xml)) {
                        try {
                            $sx = @simplexml_load_file($xml);
                            if ($sx !== false) {
                                $sx->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
                                $n = $sx->xpath('//nfe:protNFe/nfe:infProt/nfe:cStat');
                                if (is_array($n) && isset($n[0]) && (string)$n[0] === '100') {
                                    $statusMapped = 'emitted';
                                    // Extrair protocolo e chave se possível
                                    $np = $sx->xpath('//nfe:protNFe/nfe:infProt/nfe:nProt');
                                    if (is_array($np) && isset($np[0])) { $prot = (string)$np[0]; }
                                    $ch = $sx->xpath('//nfe:protNFe/nfe:infProt/nfe:chNFe');
                                    if (is_array($ch) && isset($ch[0])) { $chave = (string)$ch[0]; }
                                }
                            }
                        } catch (\Throwable $e) { /* ignore parse errors */ }
                    }

                    $numeroNfeFinal = (string) ($numeroNfe ?? $nextCandidate);
                    // Criar registro somente após sucesso
                    $nfeNote = \App\Models\NfeNote::create([
                        'tenant_id' => $tenantId,
                        'client_id' => $cliente->id,
                        'numero_pedido' => $request->numero_pedido,
                        'status' => $statusMapped,
                        'numero_nfe' => $numeroNfeFinal,
                        'protocolo' => $prot,
                        'chave_acesso' => $chave,
                        'xml_path' => $xml,
                        'pdf_path' => $pdf,
                        'response_received' => $this->sanitizeForJson($responseData),
                        'emitted_at' => $statusMapped === 'emitted' ? now() : null,
                        'serie_nfe' => (string) $serieConfigured,
                    ]);

                    // Commit da sequência somente após sucesso
                    try {
                        if (!empty($numeroNfeFinal)) {
                            $keyNext = 'nfe.next_number.series.' . $serieConfigured;
                            $currentPtr = (int) ((string) Setting::get($keyNext, '0'));
                            $target = (int) $numeroNfeFinal + 1;
                            if ($target > $currentPtr) { Setting::set($keyNext, (string) $target); }
                        }
                        // Atualiza também no emissor
                        $emitter = \App\Models\TenantEmitter::where('tenant_id', $tenantId)->first();
                        if ($emitter) { $emitter->numero_atual_nfe = (int) $numeroNfeFinal; $emitter->save(); }
                    } catch (\Throwable $e) { \Log::warning('Falha ao commitar ponteiro de numeração', ['error'=>$e->getMessage()]); }

                    // Consome créditos fiscais utilizados nesta emissão
                    try {
                        if (!empty($creditsUsed)) {
                            $taxCreditService->useCredits($creditsUsed);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Falha ao consumir créditos fiscais após emissão', [
                            'note_id' => $nfeNote->id,
                            'error' => $e->getMessage()
                        ]);
                    }

                    if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'NFe emitida com sucesso!',
                            'note' => $nfeNote->fresh(),
                            'icms_suggestions' => $icmsSuggestions,
                            'icms_totais' => $payload['totais'] ?? null
                    ]);
                    }
                    return redirect()->route('nfe.show', $nfeNote)
                        ->with('success', 'NFe emitida com sucesso!')
                        ->with('icms_suggestions', $icmsSuggestions)
                        ->with('icms_totais', $payload['totais'] ?? null);
                } else {
                    Log::error('NFe emission: Delphi returned ok=false', [
                        'response' => $responseData
                    ]);
                    $errMsg = 'Não foi possível emitir a nota. Revise os dados e tente novamente.';
                    try {
                        $dumpDir = storage_path('logs');
                        if (!is_dir($dumpDir)) { @mkdir($dumpDir, 0777, true); }
                        $dumpPath = $dumpDir . DIRECTORY_SEPARATOR . 'nfe_emit_fail_' . now()->format('Ymd_His_u') . '.json';
                        $dump = [
                            'when' => now()->toDateTimeString(),
                            'tenant_id' => $tenantId,
                            'pedido' => $request->numero_pedido,
                            'serie' => $serieConfigured,
                            'numero_candidate' => $nextCandidate,
                            'response' => $this->sanitizeForJson($responseData),
                        ];
                        @file_put_contents($dumpPath, json_encode($dump, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                        Log::info('NFe emission fail dump saved', ['path' => $dumpPath]);
                    } catch (\Throwable $e) { /* ignore */ }
                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => $errMsg,
                        ], 500);
                    }
                    return back()->with('error', $errMsg);
                }
            } else {
                $status = $response->status();
                // tenta json, se não, pega corpo texto
                $data = null;
                try { $data = $response->json(); } catch (\Throwable $e) { $data = null; }
                $raw = null;
                if ($data === null) { $raw = $response->body(); }
                $msg = 'Não foi possível emitir a nota. Revise os dados e tente novamente.';
                $code = $data['code'] ?? null;
                // não grava nota em erro

                Log::error('NFe emission failed', [
                    'http_status' => $status,
                    'response_json' => $data,
                    'response_raw' => $raw,
                ]);
                try {
                    $dumpDir = storage_path('logs');
                    if (!is_dir($dumpDir)) { @mkdir($dumpDir, 0777, true); }
                    $dumpPath = $dumpDir . DIRECTORY_SEPARATOR . 'nfe_emit_http_fail_' . now()->format('Ymd_His_u') . '.json';
                    $dump = [
                        'when' => now()->toDateTimeString(),
                        'tenant_id' => $tenantId,
                        'pedido' => $request->numero_pedido,
                        'serie' => $serieConfigured,
                        'numero_candidate' => $nextCandidate,
                        'http_status' => $status,
                        'response_json' => $this->sanitizeForJson($data),
                        'response_raw_b64' => $raw !== null ? base64_encode($raw) : null,
                    ];
                    @file_put_contents($dumpPath, json_encode($dump, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                    Log::info('NFe emission http fail dump saved', ['path' => $dumpPath]);
                } catch (\Throwable $e) { /* ignore */ }
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => $msg,
                        'code' => $code,
                        'http_status' => $status,
                ], $status >= 400 ? $status : 500);
                }
                return back()->with('error', $msg);
            }

        } catch (\Exception $e) {
            Log::error('Erro na emissão de NFe', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Erro interno na emissão da NFe',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }

    private function sanitizeForJson($value)
    {
        if (is_array($value)) {
            $san = [];
            foreach ($value as $k => $v) { $san[$k] = $this->sanitizeForJson($v); }
            return $san;
        }
        if (is_object($value)) {
            $san = [];
            foreach ((array) $value as $k => $v) { $san[$k] = $this->sanitizeForJson($v); }
            return $san;
        }
        if (is_string($value)) {
            // Remove/ignora bytes inválidos
            $s = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if ($s === false) { $s = mb_convert_encoding($value, 'UTF-8', 'UTF-8'); }
            // Limita tamanho para evitar payloads gigantes em coluna
            return mb_strimwidth($s, 0, 4000, '');
        }
        return $value;
    }

    private function sanitizeString(string $value): string
    {
        $s = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        if ($s === false) { $s = mb_convert_encoding($value, 'UTF-8', 'UTF-8'); }
        return mb_strimwidth($s, 0, 1000, '');
    }

    public function retry(NfeNote $nfeNote)
    {
        abort_unless(auth()->user()->hasPermission('nfe.retry'), 403);
        abort_unless($nfeNote->tenant_id === auth()->user()->tenant_id, 403);
        abort_unless($nfeNote->status === 'error', 400);

        try {
            $payload = $nfeNote->payload_sent;
            
            $response = Http::timeout(30)
                ->post(config('services.delphi.url') . '/api/emitir-nfe', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if ($responseData['ok'] ?? false) {
                    $cStat = (int) ($responseData['cStat'] ?? 0);
                    $statusMapped = $cStat === 100 ? 'emitted' : 'pending';
                    $numeroNfe = $responseData['numero'] ?? ($responseData['nNF'] ?? null);
                    $chave = $responseData['chave_acesso'] ?? ($responseData['chNFe'] ?? null);
                    $prot = $responseData['protocolo'] ?? ($responseData['nProt'] ?? null);
                    $xml = $responseData['xml_path'] ?? ($responseData['xml'] ?? null);
                    $pdf = $responseData['pdf_path'] ?? ($responseData['danfe_pdf'] ?? null);
                    $dhEmi = $responseData['dhEmi'] ?? null;
                    $dhRecbto = $responseData['dhRecbto'] ?? null;

                    $nfeNote->update([
                        'status' => $statusMapped,
                        'numero_nfe' => $numeroNfe,
                        'protocolo' => $prot,
                        'chave_acesso' => $chave,
                        'xml_path' => $xml,
                        'pdf_path' => $pdf,
                        'response_received' => $responseData,
                        'emitted_at' => now(),
                        'error_message' => null
                    ]);

                    return redirect()->route('nfe.index')->with('success', 'NFe reemitida com sucesso!');
                } else {
                    $nfeNote->update([
                        'error_message' => $responseData['erro'] ?? 'Erro desconhecido na reemissão',
                        'response_received' => $responseData
                    ]);

                    return redirect()->route('nfe.index')->with('error', 'Erro na reemissão da NFe');
                }
            } else {
                $nfeNote->update([
                    'error_message' => 'Erro de comunicação com o emissor Delphi',
                    'response_received' => ['http_status' => $response->status()]
                ]);

                return redirect()->route('nfe.index')->with('error', 'Erro de comunicação com o emissor Delphi');
            }

        } catch (\Exception $e) {
            Log::error('Erro na reemissão de NFe', [
                'note_id' => $nfeNote->id,
                'error' => $e->getMessage()
            ]);

            $nfeNote->update([
                'error_message' => 'Erro interno: ' . $e->getMessage()
            ]);

            return redirect()->route('nfe.index')->with('error', 'Erro interno na reemissão da NFe');
        }
    }

    public function cancel(NfeNote $nfeNote)
    {
        abort_unless(auth()->user()->hasPermission('nfe.cancel'), 403);
        abort_unless($nfeNote->tenant_id === auth()->user()->tenant_id, 403);
        abort_unless(in_array($nfeNote->status, ['emitted']), 400);

        $nfeNote->update(['status' => 'cancelled']);

        return redirect()->route('nfe.index')->with('success', 'NFe cancelada com sucesso!');
    }

    public function downloadXml(NfeNote $nfeNote)
    {
        abort_unless(auth()->user()->hasPermission('nfe.view'), 403);
        abort_unless($nfeNote->tenant_id === auth()->user()->tenant_id, 403);
        $path = (string) ($nfeNote->xml_resolved_path ?: $nfeNote->xml_path ?: $nfeNote->arquivo_xml);
        if (!$path || !file_exists($path)) {
            return back()->with('error','XML não encontrado.');
        }
        return response()->download($path, basename($path), [
            'Content-Type' => 'application/xml'
        ]);
    }

    public function downloadPdf(NfeNote $nfeNote)
    {
        abort_unless(auth()->user()->hasPermission('nfe.view'), 403);
        abort_unless($nfeNote->tenant_id === auth()->user()->tenant_id, 403);
        $path = (string) ($nfeNote->pdf_resolved_path ?: $nfeNote->pdf_path ?: $nfeNote->arquivo_danfe);
        if (!$path || !file_exists($path)) {
            return back()->with('error','PDF não encontrado.');
        }
        // Visualização inline quando solicitado (?view=1)
        if (request()->boolean('view')) {
            $filename = basename($path);
            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);
        }
        return response()->download($path, basename($path), [
            'Content-Type' => 'application/pdf'
        ]);
    }

    public function downloadCancelXml(NfeNote $nfeNote)
    {
        abort_unless(auth()->user()->hasPermission('nfe.view'), 403);
        abort_unless($nfeNote->tenant_id === auth()->user()->tenant_id, 403);
        // Caminhos possíveis do XML de cancelamento: campo dedicado, response_received, e pastas padrão
        $candidates = [];
        if (!empty($nfeNote->cancel_xml_path ?? null)) { $candidates[] = (string)$nfeNote->cancel_xml_path; }
        $resp = $nfeNote->response_received;
        if (is_array($resp)) {
            if (!empty($resp['cancel_response']['xml_retorno'] ?? null)) { $candidates[] = (string)$resp['cancel_response']['xml_retorno']; }
            if (!empty($resp['xml_retorno'] ?? null)) { $candidates[] = (string)$resp['xml_retorno']; }
            if (!empty($resp['cancel_xml_path'] ?? null)) { $candidates[] = (string)$resp['cancel_xml_path']; }
        }
        // Fallback pelo padrão do Emissor Delphi: {chave}-procEventoNFe.xml ou -canc.xml
        $chave = (string) ($nfeNote->chave_acesso ?? $nfeNote->chave_nfe ?? '');
        if ($chave === '' && is_array($resp)) {
            $chave = (string)($resp['chave_acesso'] ?? $resp['chNFe'] ?? $resp['chave'] ?? '');
            if (stripos($chave, 'NFe') === 0) { $chave = substr($chave, 3); }
        }
        if ($chave !== '') {
            $digits = preg_replace('/\D+/', '', $chave);
            if ($digits !== '') {
                $candidates[] = base_path('DelphiEmissor/Win32/Debug/nfe/' . $digits . '-procEventoNFe.xml');
                $candidates[] = base_path('DelphiEmissor/Win32/Debug/nfe/' . $digits . '-canc.xml');
            }
        }

        // Se vier XML inline (string), baixa diretamente sem arquivo temporário
        foreach ($candidates as $cand) {
            $s = (string) $cand;
            if ($s !== '' && str_contains($s, '<') && str_contains($s, '>')) {
                $filename = ($chave !== '' ? preg_replace('/\D+/', '', $chave) : ('cancel_event_' . $nfeNote->id)) . '.xml';
                return response($s, 200, [
                    'Content-Type' => 'application/xml',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                ]);
            }
        }
        // Caso contrário, tenta baixar por caminho de arquivo
        foreach ($candidates as $p) {
            if ($p && is_string($p) && file_exists($p)) {
                return response()->download($p, basename($p), ['Content-Type' => 'application/xml']);
            }
        }
        return back()->with('error','XML de cancelamento não encontrado.');
    }

    public function downloadCceXml(NfeNote $nfeNote, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('nfe.view'), 403);
        abort_unless($nfeNote->tenant_id === auth()->user()->tenant_id, 403);
        
        $seq = $request->query('seq');
        $candidates = [];
        $resp = $nfeNote->response_received;
        
        if (is_array($resp)) {
            // Se tem sequência específica, procura nos eventos
            if ($seq && !empty($resp['cce_events'])) {
                $events = (array)$resp['cce_events'];
                foreach ($events as $event) {
                    if (($event['seq'] ?? null) == $seq && !empty($event['xml_path'])) {
                        $candidates[] = $event['xml_path'];
                        break;
                    }
                }
            }
            
            // Fallback: resposta geral de CC-e (compatibilidade)
            if (empty($candidates)) {
                $cce = $resp['cce_response'] ?? [];
                if (!empty($cce['xml_retorno'] ?? null)) {
                    // Salvo inline; vamos materializar em arquivo temporário
                    $tmp = storage_path('app/tmp/'.($nfeNote->id).'_cce'.($seq ? '_seq'.$seq : '').'.xml');
                    try { 
                        @mkdir(dirname($tmp), 0777, true); 
                        @file_put_contents($tmp, (string)$cce['xml_retorno']); 
                        $candidates[] = $tmp; 
                    } catch (\Throwable $e) {}
                }
            }
        }
        
        // Padrão do emissor Delphi: {chave}-procEventoNFe.xml na pasta Debug/nfe
        $keyDigits = preg_replace('/\D+/', '', (string)($nfeNote->chave_acesso ?: $nfeNote->chave_nfe ?: ''));
        if ($keyDigits) {
            // Prioriza arquivos com sequência específica
            if ($request->query('seq')) {
                $candidates[] = base_path('DelphiEmissor/Win32/Debug/nfe/'.$keyDigits.'-procEventoNFe-seq'.$request->query('seq').'.xml');
            }
            // Depois o padrão sem seq (compatibilidade)
            $candidates[] = base_path('DelphiEmissor/Win32/Debug/nfe/'.$keyDigits.'-procEventoNFe.xml');
        }
        
        foreach ($candidates as $path) {
            if ($path && file_exists($path)) {
                $filename = $seq ? "CCe_seq{$seq}_{$keyDigits}.xml" : basename($path);
                return response()->download($path, $filename, [ 'Content-Type' => 'application/xml' ]);
            }
        }
        
        $seqText = $seq ? " (sequência {$seq})" : '';
        return back()->with('error', "XML da Carta de Correção{$seqText} não encontrado.");
    }

    public function downloadInutilizacaoXml(NfeNote $nfeNote, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('nfe.view'), 403);
        abort_unless($nfeNote->tenant_id === auth()->user()->tenant_id, 403);
        $path = (string) $request->query('path', '');
        if ($path && file_exists($path)) {
            return response()->download($path, basename($path), [ 'Content-Type' => 'application/xml' ]);
        }
        // Fallback: tenta localizar por padrão de nome se emit_cnpj/ano/serie/faixa vierem
        $cnpj = preg_replace('/\D+/', '', (string) $request->query('emit_cnpj', ''));
        $ano = (string) $request->query('ano', '');
        $modelo = (string) $request->query('modelo', '55');
        $serie = (string) $request->query('serie', '1');
        $ini = (string) $request->query('numero_inicial', '');
        $fim = (string) $request->query('numero_final', '');
        if ($cnpj !== '' && $ano !== '' && $serie !== '' && $ini !== '') {
            $dir = base_path('DelphiEmissor/Win32/Debug/nfe/');
            $fname = 'inut_' . $cnpj . '_' . sprintf('%02d', (int)$ano) . '_' . (int)$modelo . '_' . (int)$serie . '_' . (int)$ini . '-' . (int)($fim ?: $ini) . '.xml';
            $guess = $dir . $fname;
            if (file_exists($guess)) {
                return response()->download($guess, basename($guess), [ 'Content-Type' => 'application/xml' ]);
            }
        }
        return back()->with('error', 'XML de inutilização não encontrado.');
    }

    public function sendEmail(NfeNote $nfeNote)
    {
        abort_unless(auth()->user()->hasPermission('nfe.email'), 403);
        abort_unless($nfeNote->tenant_id === auth()->user()->tenant_id, 403);
        $to = $nfeNote->client?->email;
        if (!$to) {
            return back()->with('error','Cliente sem e-mail cadastrado.');
        }
        $subject = 'NFe do pedido ' . $nfeNote->numero_pedido;
        $xml = (string) $nfeNote->xml_path;
        $pdf = (string) $nfeNote->pdf_path;
        try {
            Mail::raw('Segue em anexo sua NFe.', function($m) use ($to, $subject, $xml, $pdf) {
                $m->to($to)->subject($subject);
                if ($xml && file_exists($xml)) { $m->attach($xml); }
                if ($pdf && file_exists($pdf)) { $m->attach($pdf); }
            });
            return back()->with('success','E-mail enviado ao cliente.');
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar e-mail da NFe', ['id'=>$nfeNote->id, 'error'=>$e->getMessage()]);
            return back()->with('error','Falha ao enviar e-mail: '.$e->getMessage());
        }
    }

    private function formatPayload(Request $request, Client $cliente)
    {
        // Contexto do tenant
        $tenantId = auth()->user()->tenant_id;

        // Tenta localizar o pedido para enriquecer itens, transporte e pagamentos
        $order = Order::where('number', $request->numero_pedido)
            ->where('tenant_id', $tenantId)
            ->with(['items'])
            ->first();

        // Função utilitária para rateio proporcional com acerto de arredondamento
        $allocateProportionally = function (float $total, array $weights, int $scale = 2): array {
            $count = count($weights);
            if ($count === 0 || abs($total) < 1e-9) { return array_fill(0, $count, 0.0); }
            $sumWeights = array_sum($weights);
            if ($sumWeights <= 0) { // fallback: pesos iguais
                $base = round($total / $count, $scale);
                $vals = array_fill(0, $count, $base);
                // Ajuste de centavos restantes
                $diff = round($total - array_sum($vals), $scale);
                for ($i = 0; abs($diff) >= pow(10, -$scale) && $i < $count; $i++) {
                    $vals[$i] = round($vals[$i] + ($diff > 0 ? pow(10, -$scale) : -pow(10, -$scale)), $scale);
                    $diff = round($total - array_sum($vals), $scale);
                }
                return $vals;
            }
            // Primeiro passa: cálculo bruto e coleta das frações
            $alloc = [];
            $fractions = [];
            $factor = pow(10, $scale);
            $sumFloor = 0;
            for ($i = 0; $i < $count; $i++) {
                $raw = ($weights[$i] / $sumWeights) * $total;
                $floored = floor($raw * $factor) / $factor;
                $alloc[$i] = $floored;
                $fractions[$i] = $raw - $floored;
                $sumFloor += $floored;
            }
            $remainder = round($total - $sumFloor, $scale);
            // Distribui o resto para os maiores restos fracionários
            if (abs($remainder) >= pow(10, -$scale)) {
                $indices = array_keys($fractions);
                usort($indices, function ($a, $b) use ($fractions, $remainder) {
                    // Para total positivo, ordenar desc; para negativo, asc
                    if ($remainder >= 0) {
                        return $fractions[$b] <=> $fractions[$a];
                    }
                    return $fractions[$a] <=> $fractions[$b];
                });
                $step = ($remainder >= 0) ? (1 / $factor) : (-1 / $factor);
                $units = (int) round(abs($remainder) * $factor);
                for ($k = 0; $k < $units && $k < count($indices); $k++) {
                    $idx = $indices[$k];
                    $alloc[$idx] = round($alloc[$idx] + $step, $scale);
                }
            }
            return $alloc;
        };

        // Montagem dos itens com rateio quando existir pedido; fallback para request->produtos
        if ($order && $order->items && $order->items->count() > 0) {
            $items = $order->items;
            // Pesos de rateio baseados no valor líquido da linha (qtd*unit - desc + acréscimos)
            $weights = [];
            $grosses = [];
            $itemDescValues = [];
            $itemAddValues = [];
            foreach ($items as $it) {
                // Preferir vProd = line_total + desconto_item (evita vProd=0 quando unit_price estiver zerado)
                $line = (float) ($it->line_total ?? 0);
                $itemDisc = (float)($it->discount_value ?? 0.0);
                $itemAdd = (float)($it->addition_value ?? 0.0);
                $gross = round(max(0.0, $line + $itemDisc), 2);
                if ($gross <= 0.0) {
                    // Fallback para qtd * preço quando não houver line_total
                    $gross = round(((float)$it->quantity) * ((float)$it->unit_price), 2);
                }
                $net = max($gross - $itemDisc + $itemAdd, 0.0);
                $weights[] = $net;
                $grosses[] = $gross;
                $itemDescValues[] = $itemDisc;
                $itemAddValues[] = $itemAdd;
            }

            $freteTotal = (float)($order->freight_cost ?? 0.0);
            $segTotal = (float)($order->valor_seguro ?? 0.0);
            $outrosTotal = (float)($order->outras_despesas ?? 0.0);
            // Sempre usar desconto total gravado no pedido (consistência do ERP)
            $descontoHeader = (float)($order->discount_total ?? 0.0);
            $acrescimoHeader = (float)($order->addition_total ?? 0.0);

            // Sempre considerar rateio proporcional com base no líquido parcial por item
            $hasModalItemDiscounts = false;

            $alocFrete = $allocateProportionally($freteTotal, $weights, 2);
            $alocSeg = $allocateProportionally($segTotal, $weights, 2);
            $alocOutros = $allocateProportionally($outrosTotal, $weights, 2);
            // Base de rateio do desconto de cabeçalho: líquido parcial por item
            // Desconto total do pedido vai para vDesc (positivo reduz o total)
            $alocDescHeader = $allocateProportionally($descontoHeader, $weights, 2);
            // Acréscimo total vai para vOutro
            $alocOutroHeader = $allocateProportionally($acrescimoHeader, $weights, 2);

            $produtos = [];
            foreach ($items as $index => $it) {
                $produto = $it->product ?? Product::find($it->product_id);
                if (!$produto) { continue; }
                $vProd = (float) ($grosses[$index] ?? 0.0);
                $vDesc = round($itemDescValues[$index] + ($alocDescHeader[$index] ?? 0.0), 2);
                $vOutro = round($itemAddValues[$index] + ($alocOutros[$index] ?? 0.0) + ($alocOutroHeader[$index] ?? 0.0), 2);
                $vFrete = round($alocFrete[$index] ?? 0.0, 2);
                $vSeg = round($alocSeg[$index] ?? 0.0, 2);

                // Buscar regra tributária para o produto
                $taxRate = \App\Models\TaxRate::where('tenant_id', $tenantId)
                    ->where('tipo_nota', 'produto')
                    ->where(function($q) use ($produto) {
                        $q->where('ncm', $produto->ncm)->orWhere('cfop', $produto->cfop);
                    })
                    ->where('ativo', 1)
                    ->orderByRaw("CASE WHEN ncm = ? AND cfop = ? THEN 0 WHEN ncm = ? THEN 1 WHEN cfop = ? THEN 2 ELSE 3 END", [$produto->ncm, $produto->cfop, $produto->ncm, $produto->cfop])
                    ->first();
                
                // Alíquotas (produto > regra tributária > padrão)
                $aliqIcms = (float) ($produto->aliquota_icms ?? $taxRate?->icms_aliquota ?? 0);
                $aliqPis = (float) ($produto->aliquota_pis ?? $taxRate?->pis_aliquota ?? 0);
                $aliqCofins = (float) ($produto->aliquota_cofins ?? $taxRate?->cofins_aliquota ?? 0);
                
                // Base de cálculo para impostos (valor líquido)
                $baseImposto = max($vProd - $vDesc, 0.0) + $vFrete + $vSeg + $vOutro;
                
                // Cláusula de segurança: não permitir vDesc > vProd
                if ($vDesc > $vProd) { $vDesc = $vProd; }
                $baseImposto = max($vProd - $vDesc, 0.0) + $vFrete + $vSeg + $vOutro;
                
                // Calcular impostos
                // Garante CST ICMS preenchido (fallback de segurança)
                if (empty($produto->cst_icms) && empty($produto->csosn)) {
                    $produto->cst_icms = '00';
                }
                $valorIcms = $baseImposto * ($aliqIcms / 100);
                $valorPis = $baseImposto * ($aliqPis / 100);
                $valorCofins = $baseImposto * ($aliqCofins / 100);

                // Alguns ambientes armazenam valores em centavos; normaliza para reais
                if ($valorIcms > $baseImposto) { $valorIcms = $valorIcms / 100; }
                if ($valorPis > $baseImposto) { $valorPis = $valorPis / 100; }
                if ($valorCofins > $baseImposto) { $valorCofins = $valorCofins / 100; }

                // Valor unitário bruto calculado a partir de vProd / quantidade (evita vUnCom=0.00)
                $qty = max(0.0001, (float) $it->quantity);
                $vUnCom = round($vProd / $qty, 10);

                $produtos[] = [
                    'id' => $produto->id,
                    'nome' => $produto->name,
                    'codigo' => $produto->sku ?: (string)$produto->id,
                    'codigo_interno' => $produto->sku,
                    'codigo_barras' => $produto->ean,
                    'ean' => $produto->ean ?: 'SEM GTIN',
                    'ncm' => $produto->ncm,
                    'cest' => $produto->cest,
                    'origem' => (int) $produto->origin,
                    'unidade' => $produto->unit,
                    'quantidade' => number_format((float) $it->quantity, 4, ',', ''),
                    // Valor unitário bruto (sem desconto), calculado para evitar zero
                    'valor_unitario' => number_format($vUnCom, 10, ',', ''),
                    // vProd (bruto) = qtd * unit
                    'valor_total' => number_format($vProd, 2, ',', ''),
                    'vDesc' => number_format($vDesc, 2, ',', ''),
                    'vFrete' => number_format($vFrete, 2, ',', ''),
                    'vSeg' => number_format($vSeg, 2, ',', ''),
                    'vOutro' => number_format($vOutro, 2, ',', ''),
                    'cfop' => $produto->cfop ?: '5102',
                    'cst_icms' => $produto->cst_icms ?: '00',
                    'aliquota_icms' => $aliqIcms,
                    'aliquota_pis' => $aliqPis,
                    'aliquota_cofins' => $aliqCofins,
                    'base_icms' => round($baseImposto, 2),
                    'valor_icms' => round($valorIcms, 2),
                    'valor_pis' => round($valorPis, 2),
                    'valor_cofins' => round($valorCofins, 2),
                    'cst_pis' => $produto->cst_pis,
                    'cst_cofins' => $produto->cst_cofins,
                    // IBS/CBS (padrão: informar estrutura com valores zerados para evitar rejeição quando exigido)
                    'cbs_base' => round($baseImposto, 2),
                    'cbs_aliquota' => 0.00,
                    'cbs_valor' => 0.00,
                    'cbs_cst' => '01',
                ];
            }
        } else {
            // Fallback: usa itens enviados na requisição com rateio de desconto total override quando informado
            $itemsReq = collect($request->produtos)->map(function ($item) {
                $produto = Product::findOrFail($item['product_id']);
                $vProd = round($item['quantity'] * $item['unit_price'], 2);
                return [
                    'model' => $produto,
                    'quantity' => (float)$item['quantity'],
                    'unit_price' => (float)$item['unit_price'],
                    'vProd' => $vProd,
                    'vDescItem' => (float)($item['discount_value'] ?? 0.0),
                    'vFrete' => (float)($item['vFrete'] ?? 0.0),
                    'vSeg' => (float)($item['vSeg'] ?? 0.0),
                    'vOutro' => (float)($item['vOutro'] ?? 0.0),
                ];
            })->toArray();

            $descontoHeaderFallback = (float) $request->input('discount_total_override', 0.0);
            $weights = array_map(function($it){ return max(0.0, $it['vProd'] - $it['vDescItem'] + $it['vOutro']); }, $itemsReq);
            $alocDescHeader = $descontoHeaderFallback > 0 ? $allocateProportionally($descontoHeaderFallback, $weights, 2) : array_fill(0, count($itemsReq), 0.0);

            $produtos = [];
            foreach ($itemsReq as $index => $itRow) {
                /** @var Product $produto */
                $produto = $itRow['model'];
                $vProd = $itRow['vProd'];
                $vDesc = round($itRow['vDescItem'] + ($alocDescHeader[$index] ?? 0.0), 2);
                $vFrete = $itRow['vFrete'];
                $vSeg = $itRow['vSeg'];
                $vOutro = $itRow['vOutro'];
                // Buscar regra tributária para o produto (fallback)
                $taxRate = \App\Models\TaxRate::where('tenant_id', $tenantId)
                    ->where('tipo_nota', 'produto')
                    ->where(function($q) use ($produto) {
                        $q->where('ncm', $produto->ncm)->orWhere('cfop', $produto->cfop);
                    })
                    ->where('ativo', 1)
                    ->orderByRaw("CASE WHEN ncm = ? AND cfop = ? THEN 0 WHEN ncm = ? THEN 1 WHEN cfop = ? THEN 2 ELSE 3 END", [$produto->ncm, $produto->cfop, $produto->ncm, $produto->cfop])
                    ->first();
                
                // Alíquotas (produto > regra tributária > padrão)
                $aliqIcms = (float) ($produto->aliquota_icms ?? $taxRate?->icms_aliquota ?? 0);
                $aliqPis = (float) ($produto->aliquota_pis ?? $taxRate?->pis_aliquota ?? 0);
                $aliqCofins = (float) ($produto->aliquota_cofins ?? $taxRate?->cofins_aliquota ?? 0);
                
                // Base de cálculo para impostos (valor líquido)
                $baseImposto = max($vProd - $vDesc, 0.0) + $vFrete + $vSeg + $vOutro;
                
                // Calcular impostos
                $valorIcms = $baseImposto * ($aliqIcms / 100);
                $valorPis = $baseImposto * ($aliqPis / 100);
                $valorCofins = $baseImposto * ($aliqCofins / 100);

                $produtos[] = [
                    'id' => $produto->id,
                    'nome' => $produto->name,
                    'codigo' => $produto->sku ?: (string)$produto->id,
                    'codigo_interno' => $produto->sku,
                    'codigo_barras' => $produto->ean,
                    'ean' => $produto->ean ?: 'SEM GTIN',
                    'ncm' => $produto->ncm,
                    'cest' => $produto->cest,
                    'origem' => (int) $produto->origin,
                    'unidade' => $produto->unit,
                    'quantidade' => number_format((float)$itRow['quantity'], 4, ',', ''),
                    'valor_unitario' => number_format((float)$itRow['unit_price'], 10, ',', ''),
                    'valor_total' => number_format((float)$vProd, 2, ',', ''),
                    'vDesc' => number_format($vDesc, 2, ',', ''),
                    'vFrete' => number_format((float)$vFrete, 2, ',', ''),
                    'vSeg' => number_format((float)$vSeg, 2, ',', ''),
                    'vOutro' => number_format((float)$vOutro, 2, ',', ''),
                    'cfop' => $produto->cfop ?: '5102',
                    'cst_icms' => $produto->cst_icms,
                    'aliquota_icms' => $aliqIcms,
                    'aliquota_pis' => $aliqPis,
                    'aliquota_cofins' => $aliqCofins,
                    'base_icms' => round($baseImposto, 2),
                    'valor_icms' => round($valorIcms, 2),
                    'valor_pis' => round($valorPis, 2),
                    'valor_cofins' => round($valorCofins, 2),
                    'cst_pis' => $produto->cst_pis,
                    'cst_cofins' => $produto->cst_cofins,
                    // IBS/CBS (padrão)
                    'cbs_base' => round($baseImposto, 2),
                    'cbs_aliquota' => 0.00,
                    'cbs_valor' => 0.00,
                    'cbs_cst' => '01',
                ];
            }
        }
        // $order já foi carregado acima (com itens)

        // Totais da nota (somas por item) para facilitar conferência/depuração no emissor
        try {
            $totalVProd = 0.0; $totalDesc = 0.0; $totalFrete = 0.0; $totalSeg = 0.0; $totalOutro = 0.0;
            $totalICMS = 0.0; $totalPIS = 0.0; $totalCOFINS = 0.0; $totalCBS = 0.0;
            foreach (($produtos ?? []) as $pSum) {
                $totalVProd += (float)($pSum['valor_total'] ?? 0);
                $totalDesc += (float)($pSum['vDesc'] ?? 0);
                $totalFrete += (float)($pSum['vFrete'] ?? 0);
                $totalSeg += (float)($pSum['vSeg'] ?? 0);
                $totalOutro += (float)($pSum['vOutro'] ?? 0);
                $totalICMS += (float)($pSum['valor_icms'] ?? 0);
                $totalPIS += (float)($pSum['valor_pis'] ?? 0);
                $totalCOFINS += (float)($pSum['valor_cofins'] ?? 0);
                $totalCBS += (float)($pSum['cbs_valor'] ?? 0);
            }
            $vNF = max(0.0, ($totalVProd - $totalDesc) + $totalFrete + $totalSeg + $totalOutro);
        } catch (\Throwable $e) {
            $totalVProd = $totalVProd ?? 0.0; $totalDesc = $totalDesc ?? 0.0; $totalFrete = $totalFrete ?? 0.0; $totalSeg = $totalSeg ?? 0.0; $totalOutro = $totalOutro ?? 0.0; $vNF = $vNF ?? 0.0;
            $totalICMS = $totalICMS ?? 0.0; $totalPIS = $totalPIS ?? 0.0; $totalCOFINS = $totalCOFINS ?? 0.0; $totalCBS = $totalCBS ?? 0.0;
        }

        $observacoes = [
            'inf_complementar' => $order?->additional_info,
            'inf_fisco' => $order?->fiscal_info,
        ];

        // Pagamentos a partir dos receivíveis do pedido (multi-métodos) com mapeamento tPag
        $tPagMap = [
            'DINHEIRO' => '01',    // Dinheiro
            'CHEQUE' => '02',      // Cheque
            'CARTAO' => '03',      // Cartão de crédito (genérico)
            'CARTAO_CREDITO' => '03',
            'CARTAO_DEBITO' => '04',
            'CREDITO_LOJA' => '05',
            'BOLETO' => '15',
            'PIX' => '17',
            'DEPOSITO' => '16',
            'OUTROS' => '99',
            'CASH' => '01',
            'CARD' => '03',
        ];
        $pagamentos = [];
        if ($order) {
            $receivables = Receivable::where('tenant_id', auth()->user()->tenant_id)
                ->where('order_id', $order->id)
                ->get();
            if ($receivables->count() > 0) {
                foreach ($receivables as $rec) {
                    $tipo = strtoupper((string)($rec->payment_method ?? 'OUTROS'));
                    if ($tipo === 'CREDIT' || $tipo === 'CARTAOCREDITO') { $tipo = 'CARTAO_CREDITO'; }
                    if ($tipo === 'DEBIT' || $tipo === 'CARTAODEBITO') { $tipo = 'CARTAO_DEBITO'; }
                    $tPag = $rec->tpag_override ?: ($tPagMap[$tipo] ?? '99');
                    $pay = [
                        'tipo' => $tipo,
                        'tPag' => $tPag,
                        'valor' => (float) $rec->amount,
                        'hint' => $rec->tpag_hint,
                    ];
                    if ($tPag === '15') {
                        $pay['vencimento'] = optional($rec->due_date)->toDateString();
                    }
                    $pagamentos[] = $pay;
                }
            }
            // Reconcilia pagamentos com vNF (sempre)
            $sumPag = array_sum(array_map(function($p){ return (float)($p['valor'] ?? 0); }, $pagamentos));
            if (abs($sumPag - (float)$vNF) > 0.01 || $sumPag <= 0.0 || count($pagamentos) > 1) {
                $tipoSel = strtoupper((string) $request->input('payment_method', 'DINHEIRO'));
                if ($tipoSel === 'CREDIT' || $tipoSel === 'CARTAOCREDITO') { $tipoSel = 'CARTAO_CREDITO'; }
                if ($tipoSel === 'DEBIT' || $tipoSel === 'CARTAODEBITO') { $tipoSel = 'CARTAO_DEBITO'; }
                $tPagSel = $tPagMap[$tipoSel] ?? '01';
                $pagamentos = [[
                    'tipo' => $tipoSel,
                    'tPag' => $tPagSel,
                    'valor' => round((float) $vNF, 2),
                    'hint' => 'reconciled-vNF'
                ]];
            }
        }

        // Obter configurações do emitter
        $tenantId = auth()->user()->tenant_id;
        $emitter = \App\Models\TenantEmitter::where('tenant_id', $tenantId)->first();
        
        // Obter certificado selecionado (número de série) e/ou PFX do emissor
        $certificateSerial = \App\Models\Setting::get('nfe.certificate_serial');
        $certBlock = [];
        if (!empty($certificateSerial)) {
            $certBlock['serial'] = $certificateSerial;
        }
        if (!empty($emitter?->certificate_path)) {
            // Resolve caminho absoluto usando o disk configurado (local/private/s3 etc.)
            $p = (string) $emitter->certificate_path;
            $disk = $emitter->base_storage_disk ?: config('filesystems.default', 'local');
            try {
                $abs = Storage::disk($disk)->path($p);
            } catch (\Throwable $e) {
                // Fallback para storage/app
                $abs = storage_path('app/' . ltrim($p, '/'));
            }
            $certBlock['path'] = $abs;
            $certBlock['password'] = $emitter->certificate_password_encrypted ? decrypt((string)$emitter->certificate_password_encrypted) : null;
            // Marca
            $certBlock['has_pfx'] = file_exists($abs);
            Log::info('NFe cert path resolved', ['disk' => $disk, 'relative' => $p, 'absolute' => $abs, 'exists' => $certBlock['has_pfx']]);
        }
        
        $clienteCodigoMunicipio = (int) ($cliente->codigo_municipio ?: $cliente->codigo_ibge ?: 0);
        // Fallback agressivo: se cliente não tem código, usa do emitente
        if ($clienteCodigoMunicipio === 0) {
            $clienteCodigoMunicipio = (int) ($emitter?->codigo_ibge ?: 0);
        }

        $payload = [
            'tipo' => 'nfe',
            'numero_pedido' => $request->numero_pedido,
            'tenant_id' => $tenantId,
            // Campos no root esperados pelo Delphi
            'natOp' => (string) $request->input('natOp', 'Venda de mercadoria'),
            'serie' => (int) $request->input('serie', (int) $request->input('serie_nfe', 1)),
            'cliente' => [
                'id' => $cliente->id,
                'nome' => $cliente->name,
                'cpf_cnpj' => $cliente->cpf_cnpj,
                'tipo' => $cliente->type === 'pf' ? 'PESSOA FÍSICA' : 'JURÍDICA',
                'endereco' => $cliente->address,
                'numero' => $cliente->number,
                'complemento' => $cliente->complement,
                'bairro' => $cliente->neighborhood,
                'cidade' => $cliente->city,
                'uf' => $cliente->state,
                'cep' => $cliente->zip_code,
                'telefone' => $cliente->phone,
                'email' => $cliente->email,
                // 1 = consumidor final, 0 = não consumidor final
                'consumidor_final' => ($cliente->consumidor_final === 'S' ? 1 : 0),
                'codigo_municipio' => $clienteCodigoMunicipio,
            ],
            'produtos' => $produtos,
            // Compat: enviar bloco de certificado permitindo serial e/ou PFX
            'cert' => $certBlock,
            // Removido bloco 'emitter' para evitar duplicidade; usar apenas 'emitente'
            // Bloco esperado pelo Delphi (sinônimo de emitter)
            'emitente' => [
                'cnpj' => $emitter?->cnpj,
                'ie' => $emitter?->ie,
                'razao_social' => $emitter?->razao_social,
                'nome_fantasia' => $emitter?->nome_fantasia,
                'endereco' => $emitter?->address,
                'numero' => $emitter?->number,
                'complemento' => $emitter?->complement,
                'bairro' => $emitter?->neighborhood,
                'codigo_municipio' => (int) $emitter?->codigo_ibge,
                'cidade' => $emitter?->city,
                'uf' => $emitter?->state,
                'cep' => $emitter?->zip_code,
            ],
            'configuracoes' => [
                'cfop' => (string) $request->input('cfop', '5102'),
                'ambiente' => (string) (\App\Models\Setting::get('nfe.environment', \App\Models\Setting::getGlobal('services.delphi.environment', (config('app.env') === 'production' ? 'producao' : 'homologacao')))),
                'serie' => '1',
                // Força modelo 55 para NF-e
                'tipo_nota' => '55',
                // Campos para máxima compatibilidade com o emissor
                'natOp' => (string) $request->input('natOp', 'Venda de mercadoria'),
                'uf' => (string) ($emitter?->state ?: $cliente->state ?: 'SP'),
                // Código do Município do Fato Gerador (IBGE 7 dígitos) — usar do emitente; fallback: cliente
                'cMunFG' => (int) ($emitter?->codigo_ibge ?: $clienteCodigoMunicipio ?: 0),
                'tpNF' => (int) $request->input('tpNF', 1),
                'finNFe' => (int) $request->input('finNFe', 1),
                'finalidade_nfe' => (string) $request->input('finNFe', '1'),
                'idDest' => (int) $request->input('idDest', 1),
                'operation_type' => (string) $request->input('operation_type', 'venda'),
                'reference_key' => ($rk = trim((string)$request->input('reference_key', ''))) !== '' ? $rk : null,
                // Overrides de frete/pagamento vindos do modal
                'freight_mode' => $request->input('freight_mode', $order?->freight_mode),
                'payment_form' => $request->input('payment_form'),
                'payment_method' => $request->input('payment_method'),
                // DANFE/PDF options for emissor
                'gerar_pdf' => (bool) $request->input('gerar_pdf', true),
                'logo_path' => (function(){
                    try {
                        $p = (string) \App\Models\Setting::get('ui.logo_path');
                        if ($p && file_exists(public_path($p))) { return public_path($p); }
                        $fallback = public_path('logo/logo.png');
                        return file_exists($fallback) ? $fallback : null;
                    } catch (\Throwable $e) { return null; }
                })(),
            ],
            // Transporte (frete, volumes, pesos, seguro e outras despesas)
            'transporte' => (function() use ($request, $order) {
                $fm = (int) $request->input('freight_mode', $order?->freight_mode);
                $qtd = $request->input('volume_qtd', $order->volume_qtd);
                $esp = $request->input('volume_especie', $order->volume_especie);
                $pb  = $request->input('peso_bruto', $order->peso_bruto);
                $pl  = $request->input('peso_liquido', $order->peso_liquido);
                $transp = [
                    'modalidade' => $fm,
                    'responsavel' => $order->freight_payer,
                    'transportadora_id' => $request->input('carrier_id', $order->carrier_id),
                    'valor_frete' => $request->input('freight_cost', $order->freight_cost),
                    'observacoes' => $order->freight_obs,
                    'despesas' => [
                        'seguro' => (float) ($request->input('valor_seguro', $order->valor_seguro) ?? 0),
                        'outras' => (float) ($request->input('outras_despesas', $order->outras_despesas) ?? 0),
                    ],
                ];
                // Só incluir volumes quando necessário (fm != 9) ou quando usuário informou explicitamente
                $explicit = ($qtd !== null) || ($esp !== null) || ($pb !== null) || ($pl !== null);
                if ($fm !== 9 || $explicit) {
                    // Sanitiza mínimos
                    $qtd = (int) max(1, (int) ($qtd ?? 1));
                    $pb = (float) ($pb ?? 0.1);
                    $pl = (float) ($pl ?? 0.1);
                    if ($pb <= 0) { $pb = 0.1; }
                    if ($pl <= 0) { $pl = 0.1; }
                    $transp['volumes'] = [
                        'quantidade' => $qtd,
                        'especie' => $esp ?? 'VOL',
                        'peso_bruto' => $pb,
                        'peso_liquido' => $pl,
                    ];
                }
                return $transp;
            })(),
            'observacoes' => [
                'inf_complementar' => (string) $request->input('additional_info', $observacoes['inf_complementar'] ?? null),
                'inf_fisco' => (string) $request->input('fiscal_info', $observacoes['inf_fisco'] ?? null),
            ],
            'pagamentos' => $pagamentos,
        ];

        // Totais informativos (override de desconto total sem persistir no pedido)
        if (!isset($payload['totais'])) { $payload['totais'] = []; }
        // Totais calculados (compatibilidade com emissor Delphi/ACBr) no padrão esperado
        $payload['totais']['vProd'] = number_format((float)$totalVProd, 2, '.', '');
        $payload['totais']['vDesc'] = number_format((float)$totalDesc, 2, '.', '');
        $payload['totais']['vFrete'] = number_format((float)$totalFrete, 2, '.', '');
        $payload['totais']['vSeg'] = number_format((float)$totalSeg, 2, '.', '');
        $payload['totais']['vOutro'] = number_format((float)$totalOutro, 2, '.', '');
        $payload['totais']['vNF'] = number_format((float)$vNF, 2, '.', '');
        $payload['totais']['ICMSTot'] = [
            'vBC' => number_format((float)($totalVProd - $totalDesc + $totalFrete + $totalSeg + $totalOutro), 2, '.', ''),
            'vICMS' => number_format((float)$totalICMS, 2, '.', ''),
            'vICMSDeson' => '0.00',
            'vFCP' => '0.00',
            'vBCST' => '0.00',
            'vST' => '0.00',
            'vFCPST' => '0.00',
            'vFCPSTRet' => '0.00',
            'vProd' => number_format((float)$totalVProd, 2, '.', ''),
            'vFrete' => number_format((float)$totalFrete, 2, '.', ''),
            'vSeg' => number_format((float)$totalSeg, 2, '.', ''),
            'vDesc' => number_format((float)$totalDesc, 2, '.', ''),
            'vII' => '0.00',
            'vIPI' => '0.00',
            'vIPIDevol' => '0.00',
            'vPIS' => number_format((float)$totalPIS, 2, '.', ''),
            'vCOFINS' => number_format((float)$totalCOFINS, 2, '.', ''),
            'vCBS' => number_format((float)$totalCBS, 2, '.', ''),
            'vOutro' => number_format((float)$totalOutro, 2, '.', ''),
            'vNF' => number_format((float)$vNF, 2, '.', ''),
        ];

        // Log diagnóstico: amostra dos itens com números enviados ao emissor
        try {
            if (!empty($produtos) && is_array($produtos)) {
                $sample = [];
                foreach ($produtos as $pp) {
                    $sample[] = [
                        'id' => $pp['id'] ?? null,
                        'quantidade' => $pp['quantidade'] ?? null,
                        'valor_unitario' => $pp['valor_unitario'] ?? null,
                        'valor_total' => $pp['valor_total'] ?? null,
                        'vDesc' => $pp['vDesc'] ?? null,
                    ];
                    if (count($sample) >= 5) break;
                }
                \Log::info('NFe payload itens preview', ['pedido' => $request->numero_pedido, 'itens' => $sample]);
            }
        } catch (\Throwable $e) { }

        return $payload;
    }

    // Sugestão de prefill para inutilização com base no último emitido e próximo configurado
    private function computeInutilizacaoSuggestion(int $tenantId, string $serie): array
    {
        try {
            $keyNext = 'nfe.next_number.series.' . $serie;
            $configuredNext = (int) ((string) Setting::get($keyNext, ''));
            $lastEmitted = NfeNote::where('tenant_id', $tenantId)
                ->where(function($q) use ($serie){ $q->where('serie_nfe', $serie)->orWhereNull('serie_nfe'); })
                ->whereIn('status', ['emitted','transmitida','cancelled','cancelada'])
                ->whereNotNull('numero_nfe')->where('numero_nfe','!=','')
                ->orderByRaw('CAST(numero_nfe AS UNSIGNED) DESC')
                ->value('numero_nfe');
            $lastNum = is_numeric($lastEmitted) ? (int) $lastEmitted : 0;
            $suggestStart = $lastNum + 1;
            $suggestEnd = $configuredNext > $suggestStart ? $configuredNext - 1 : null;
            $emit = \App\Models\TenantEmitter::where('tenant_id', $tenantId)->first();
            $cnpj = $emit ? preg_replace('/\D+/','', (string)$emit->cnpj) : null;
            return [
                'emit_cnpj' => $cnpj,
                'ano' => (int) now()->format('y'),
                'modelo' => 55,
                'serie' => (int) $serie,
                'numero_inicial' => $suggestStart,
                'numero_final' => $suggestEnd,
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    // Reserva o próximo número sequencial de NF-e por tenant e série, de forma transacional
    private function reserveNextNfeNumber(int $tenantId, string $serie): int
    {
        return DB::transaction(function() use ($tenantId, $serie) {
            $keyNext = 'nfe.next_number.series.' . $serie;
            $keyLock = $keyNext . ':lock';

            // Lock otimista: atualiza um carimbo para forçar serialização
            Setting::set($keyLock, (string) microtime(true));

            // Calcula SEMPRE o próximo após o MAIOR número já registrado (independente do status)
            $maxUsed = NfeNote::where('tenant_id', $tenantId)
                ->where(function($q) use ($serie){ $q->where('serie_nfe', $serie)->orWhereNull('serie_nfe'); })
                ->whereNotNull('numero_nfe')
                ->where('numero_nfe', '!=', '')
                ->orderByRaw('CAST(numero_nfe AS UNSIGNED) DESC')
                ->value(DB::raw('CAST(numero_nfe AS UNSIGNED)'));
            $maxNum = is_numeric($maxUsed) ? (int) $maxUsed : 0;
            $reserved = max(1, $maxNum + 1);

            // Respeita avanço manual já configurado (se houver), mas nunca retrocede
            $configuredNext = (int) ((string) Setting::get($keyNext, (string) $reserved));
            if ($configuredNext > $reserved) { $reserved = $configuredNext; }

            // Atualiza ponteiro global para o próximo esperado após reservar
            Setting::set($keyNext, (string)($reserved + 1));
            return (int) $reserved;
        });
    }
}
