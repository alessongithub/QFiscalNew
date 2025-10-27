<?php

namespace App\Http\Controllers;

use App\Models\NfeNote;
use Illuminate\Http\Request;

class NfeManagementController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('nfe.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $q = NfeNote::where('tenant_id', $tenantId)->with(['order']);
        if ($s = trim((string)$request->get('s'))) {
            $q->where(function($qq) use ($s){
                $qq->where('chave_nfe','like',"%$s%")
                   ->orWhere('numero_nfe','like',"%$s%")
                   ->orWhereHas('order', fn($qo)=>$qo->where('number','like',"%$s%"));
            });
        }
        if ($st = $request->get('status')) { $q->where('status', $st); }
        $nfes = $q->orderByDesc('id')->paginate(20)->appends($request->query());

		// Prefill de inutilização: CNPJ do emissor e sugestão de faixa
		try {
			$serie = (string) (\App\Models\Setting::get('nfe.series', '1'));
			$lastEmitted = \App\Models\NfeNote::where('tenant_id', $tenantId)
				->where(function($qq) use ($serie){ $qq->where('serie_nfe', $serie)->orWhereNull('serie_nfe'); })
				->whereNotNull('numero_nfe')->where('numero_nfe','!=','')
				->orderByRaw('CAST(numero_nfe AS UNSIGNED) DESC')
				->value('numero_nfe');
			$lastNum = is_numeric($lastEmitted) ? (int) $lastEmitted : 0;
			$suggestStart = max(1, $lastNum + 1);
			$configuredNext = (int) ((string) \App\Models\Setting::get('nfe.next_number.series.' . $serie, ''));
			$suggestEnd = $configuredNext > $suggestStart ? $configuredNext - 1 : null;
			$emit = \App\Models\TenantEmitter::where('tenant_id', $tenantId)->first();
			$emitterCnpj = $emit?->cnpj ?: (auth()->user()->tenant->cnpj ?? null);
			$inutPrefill = [
				'emit_cnpj' => $emitterCnpj ? preg_replace('/\D+/', '', (string)$emitterCnpj) : null,
				'ano' => (int) now()->format('y'),
				'modelo' => 55,
				'serie' => (int) $serie,
				'numero_inicial' => $suggestStart,
				'numero_final' => $suggestEnd,
			];
			// Disponibiliza via sessão e também via variável de view
			session()->flash('inutilizar_prefill', $inutPrefill);
		} catch (\Throwable $e) {
			$emitterCnpj = null; $inutPrefill = [];
		}

		// Carregar eventos de inutilização com paginação simples
		$storedInut = \App\Models\Setting::get('nfe.inutilizacoes', '[]');
		$inutListAll = is_string($storedInut) ? (json_decode($storedInut, true) ?: []) : (is_array($storedInut) ? $storedInut : []);
		// Ordem: mais recentes primeiro (baseado em 'at' ou inserção)
		$inutListAll = array_values($inutListAll);
		$inutListAll = array_reverse($inutListAll);
		$inutPerPage = 10;
		$inutPage = max(1, (int) $request->query('inut_page', 1));
		$inutTotal = count($inutListAll);
		$inutTotalPages = (int) max(1, ceil($inutTotal / $inutPerPage));
		if ($inutPage > $inutTotalPages) { $inutPage = $inutTotalPages; }
		$inutOffset = ($inutPage - 1) * $inutPerPage;
		$inutPageItems = array_slice($inutListAll, $inutOffset, $inutPerPage);

		return view('nfe.index', [
			'nfes' => $nfes,
			'notes' => $nfes,
			'emitterCnpj' => $emitterCnpj ?? null,
			'inutPrefill' => $inutPrefill ?? [],
			'inutPageItems' => $inutPageItems,
			'inutPage' => $inutPage,
			'inutTotalPages' => $inutTotalPages,
			'inutTotal' => $inutTotal,
		]);
    }

    public function cancel(NfeNote $nfe, Request $request)
    {
        abort_unless(auth()->user()->tenant_id === $nfe->tenant_id, 403);
        abort_unless(auth()->user()->hasPermission('nfe.cancel'), 403);
        $data = $request->validate(['justificativa' => 'required|string|min:15|max:1000']);

        // Bloqueio: não permitir cancelar após 24h da autorização
        try {
            $emitAt = $nfe->emitted_at ?: $nfe->data_emissao ?: null;
            if ($emitAt && now()->diffInHours($emitAt) > 24) {
                $msg = 'Cancelamento não permitido após 24 horas da autorização.';
                if ($request->wantsJson()) { return response()->json(['ok'=>false,'error'=>$msg], 400); }
                return redirect()->route('nfe.show', $nfe)->with('error', $msg);
            }
        } catch (\Throwable $e) {}

        // Bloqueio: não permitir cancelar se já houver CC-e registrada
        $hasCce = false;
        try { $hasCce = is_array($nfe->response_received ?? null) && !empty(($nfe->response_received)['cce_response'] ?? null); } catch (\Throwable $e) {}
        if ($hasCce || (string)$nfe->status === 'com_cc') {
            $msg = 'Esta NF-e possui Carta de Correção registrada. Cancelamento não permitido.';
            if ($request->wantsJson()) { return response()->json(['ok'=>false,'error'=>$msg], 400); }
            return redirect()->route('nfe.show', $nfe)->with('error', $msg);
        }

        // Só permitir cancelamento de NF-e autorizada
        $statusNorm = strtolower((string) $nfe->status);
        $hasProt = !empty($nfe->protocolo_autorizacao ?? $nfe->protocolo ?? null);
        if (!in_array($statusNorm, ['emitted','transmitida'], true) && !$hasProt) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'error' => 'NF-e ainda não autorizada (sem protocolo). Não é possível cancelar.'], 400);
            }
            return redirect()->route('nfe.show', $nfe)->with('error', 'NF-e ainda não autorizada (sem protocolo). Não é possível cancelar.');
        }
        $svc = app(\App\Services\NFeService::class);
        // Envia UF/cUF a partir do emissor do tenant para ajudar o Delphi/ACBr
        try {
            $emit = \App\Models\TenantEmitter::where('tenant_id', auth()->user()->tenant_id)->first();
            if ($emit) {
                $uf = (string) ($emit->state ?? '');
                $cfg = [
                    'path_schemas' => base_path('DelphiEmissor/Win32/Debug/Schemas/'),
                    'path_xml' => base_path('DelphiEmissor/Win32/Debug/nfe/'),
                    'ambiente' => \App\Models\Setting::get('nfe.environment', \App\Models\Setting::getGlobal('services.delphi.environment', (config('app.env')==='production'?'producao':'homologacao'))),
                ];
                // Passo a UF no extras consolidando no NFeService
                // NFeService já envia payload puro; aqui apenas coloco no Request bag para NFeService aproveitar em camada inferior se necessário
                request()->merge(['__inut_cfg'=>$cfg,'__inut_uf'=>$uf]);
            }
        } catch (\Throwable $e) {}
        // Monta extras conforme payload do Postman
        $emit = \App\Models\TenantEmitter::where('tenant_id', auth()->user()->tenant_id)->first();
        $extras = [];
        $cnpj = preg_replace('/\D/', '', (string)($emit->cnpj ?? ''));
        if ($cnpj) { $extras['cnpj'] = $cnpj; $extras['emit_cnpj'] = $cnpj; }
        $extras['ambiente'] = \App\Models\Setting::get('nfe.environment', \App\Models\Setting::getGlobal('services.delphi.environment', (config('app.env')==='production'?'producao':'homologacao')));
        // Certificado: tenta PFX do emitente quando existir; senão usa serial configurado
        $certBlock = [];
        if (!empty($emit?->certificate_path)) {
            try {
                $disk = $emit->base_storage_disk ?: config('filesystems.default', 'local');
                $abs = \Illuminate\Support\Facades\Storage::disk($disk)->path((string)$emit->certificate_path);
                if (file_exists($abs)) {
                    $certBlock['path'] = $abs;
                    $certBlock['password'] = $emit->certificate_password_encrypted ? decrypt((string)$emit->certificate_password_encrypted) : null;
                }
            } catch (\Throwable $e) { /* ignore */ }
        }
        if (!empty($certBlock)) { $extras['cert'] = $certBlock; }
        $uf = (string) ($emit->state ?? '');
        if ($uf === '' && method_exists($emit, 'uf')) { try { $uf = (string) $emit->uf; } catch (\Throwable $e) {} }
        if ($uf !== '') { $extras['uf'] = $uf; }
        $extras['configuracoes'] = [
            'path_schemas' => base_path('DelphiEmissor/Win32/Debug/Schemas/'),
            'path_xml' => base_path('DelphiEmissor/Win32/Debug/nfe/'),
            'uf' => $uf,
            'ambiente' => $extras['ambiente'] ?? null,
        ];

        // Resolve XML e chave da NFe com múltiplos fallbacks
        $xmlPath = (string)($nfe->xml_resolved_path ?: $nfe->arquivo_xml ?: $nfe->xml_path ?: '');
        $chave = (string)($nfe->chave_nfe ?: $nfe->chave_acesso ?: '');
        if ($chave === '' && is_array($nfe->response_received ?? null)) {
            $rr = (array)$nfe->response_received;
            $chave = (string)($rr['chave_acesso'] ?? $rr['chNFe'] ?? $rr['chave'] ?? '');
        }
        // Extrai do nome do arquivo, se necessário
        if ($chave === '' && $xmlPath !== '') {
            try {
                $bn = basename($xmlPath);
                if (preg_match('/(\d{44})/u', $bn, $m)) {
                    $chave = $m[1] ?? '';
                }
            } catch (\Throwable $e) {}
        }
        // Extrai de dentro do XML (infNFe Id ou chNFe do protNFe)
        if ($chave === '' && $xmlPath !== '' && file_exists($xmlPath)) {
            try {
                $xmlStr = @file_get_contents($xmlPath);
                if ($xmlStr !== false) {
                    if (preg_match('/Id=\"NFe(\d{44})\"/u', $xmlStr, $m)) {
                        $chave = $m[1] ?? '';
                    }
                    if ($chave === '' && preg_match('/<chNFe>(\d{44})<\\/chNFe>/u', $xmlStr, $m2)) {
                        $chave = $m2[1] ?? '';
                    }
                }
            } catch (\Throwable $e) {}
        }
        // Sanitiza para apenas dígitos
        $chave = preg_replace('/\D+/', '', (string)$chave);
        if (strlen($chave) !== 44) {
            \Log::warning('Cancelamento NFe: chave ausente/ inválida ao montar payload', ['nfe_id'=>$nfe->id, 'xmlPath'=>$xmlPath, 'chave'=>$chave]);
        }
        $res = $svc->cancelarNFe($chave, $data['justificativa'], $xmlPath, $extras);
        if ($res['success'] ?? false) {
            $payloadResp = (array)($res['data'] ?? []);

            // Extrai cStat/xMotivo do JSON/retorno XML
            $cStat = (string)($payloadResp['cStat'] ?? '');
            $xMotivo = (string)($payloadResp['xMotivo'] ?? '');
            $xmlRet = (string)($payloadResp['xml_retorno'] ?? '');
            if ($cStat === '' && $xmlRet !== '') {
                try {
                    $sx = @simplexml_load_string($xmlRet);
                    if ($sx !== false) {
                        $sx->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
                        $c = $sx->xpath('//nfe:retEvento/nfe:infEvento/nfe:cStat');
                        if (is_array($c) && isset($c[0])) { $cStat = (string)$c[0]; }
                        $m = $sx->xpath('//nfe:retEvento/nfe:infEvento/nfe:xMotivo');
                        if (is_array($m) && isset($m[0])) { $xMotivo = (string)$m[0]; }
                    }
                } catch (\Throwable $e) {}
            }

            // Apenas homologado (135) ou duplicidade (573) pode marcar como cancelada
            if (in_array($cStat, ['135','573'], true)) {
                // Salvar XML de cancelamento se presente
                try {
                    if ($xmlRet !== '') {
                        $digits = preg_replace('/\D+/', '', (string)($chave ?: ($payloadResp['chave'] ?? '')));
                        if ($digits !== '') {
                            $dir = base_path('DelphiEmissor/Win32/Debug/nfe/');
                            if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
                            $file = $dir . $digits . '-procEventoNFe-seq' . $seq2 . '.xml';
                            @file_put_contents($file, $xmlRet);
                            if (\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes', 'cancel_xml_path')) { $nfe->cancel_xml_path = $file; }
                        }
                    }
                } catch (\Throwable $e) {}

                // Atualiza status e histórico
                $nfe->status = 'cancelled';
                $nfe->cancelamento_justificativa = $data['justificativa'];
                $nfe->cancelamento_data = now();
                try {
                    $prev = (array)($nfe->response_received ?? []);
                    $prev['cancel_response'] = $payloadResp;
                    $nfe->response_received = $prev;
                } catch (\Throwable $e) {}
                $nfe->save();

                // Reabrir pedido automaticamente somente se usuário tiver permissão explícita e pedido estiver finalizado
                try {
                    if ($nfe->order && $nfe->order->status === 'fulfilled') {
                        $canReopen = (auth()->user()->hasPermission('orders.reopen') || auth()->user()->hasPermission('admin'));
                        if ($canReopen) {
                            $nfe->order->status = 'open';
                            $nfe->order->save();
                            try {
                                if (class_exists(\Spatie\Activitylog\ActivitylogServiceProvider::class)) {
                                    activity()->performedOn($nfe->order)->causedBy(auth()->user())->withProperties([
                                        'nfe_id' => $nfe->id,
                                    ])->log('Pedido reaberto automaticamente após cancelamento de NF-e');
                                }
                            } catch (\Throwable $e) {}
                        }
                    }
                } catch (\Throwable $e) {}

                if ($request->wantsJson()) {
                    return response()->json(['ok' => true, 'message' => 'NF-e cancelada com sucesso', 'cStat' => $cStat, 'cancel_xml_path' => $nfe->cancel_xml_path ?? null]);
                }
                return redirect()->route('nfe.show', $nfe)->with('success','NF-e cancelada com sucesso (cStat '.$cStat.').');
            }

            // Caso não homologado, mantém status, persiste resposta para auditoria e informa motivo
            try {
                $prev = (array)($nfe->response_received ?? []);
                $prev['cancel_response'] = $payloadResp;
                $nfe->response_received = $prev;
                $nfe->save();
            } catch (\Throwable $e) {}
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'error' => 'SEFAZ não confirmou cancelamento', 'cStat' => ($cStat ?: '—'), 'xMotivo' => $xMotivo], 400);
            }
            return redirect()->route('nfe.show', $nfe)->with('error', 'SEFAZ não confirmou cancelamento (cStat '.($cStat ?: '—').')'.($xMotivo? ': '.$xMotivo : ''));
        }
        // Falha de comunicação: persiste resposta para auditoria quando possível
        try {
            $prev = (array)($nfe->response_received ?? []);
            $prev['cancel_response'] = (array)($res ?? []);
            $nfe->response_received = $prev;
            $nfe->save();
        } catch (\Throwable $e) {}
        if ($request->wantsJson()) {
            return response()->json(['ok' => false, 'error' => ($res['error'] ?? 'erro')], 500);
        }
        return redirect()->route('nfe.show', $nfe)->with('error','Falha ao cancelar: '.($res['error'] ?? 'erro'));
    }

    public function cce(NfeNote $nfe, Request $request)
    {
        abort_unless(auth()->user()->tenant_id === $nfe->tenant_id, 403);
        abort_unless(auth()->user()->hasPermission('nfe.cce'), 403);
        $data = $request->validate(['correcao' => 'required|string|min:15|max:2000']);
        $svc = app(\App\Services\NFeService::class);
        // Determina próxima sequência considerando DB e eventos já salvos
        $seqBase = (int)($nfe->cc_sequencia ?? 0);
        try {
            $rr = $nfe->response_received ?? null;
            $respArr = is_array($rr) ? $rr : (is_string($rr) ? (json_decode($rr, true) ?: []) : []);
            $evs = is_array($respArr['cce_events'] ?? null) ? (array)$respArr['cce_events'] : [];
            foreach ($evs as $ev) {
                $s = (int)($ev['seq'] ?? 0);
                if ($s > $seqBase) { $seqBase = $s; }
            }
        } catch (\Throwable $e) {}
        $seq = $seqBase + 1;
        // Monta extras com certificado e paths (semelhante ao cancelamento)
        $emit = \App\Models\TenantEmitter::where('tenant_id', auth()->user()->tenant_id)->first();
        $extras = [];
        $cnpj = preg_replace('/\D/', '', (string)($emit->cnpj ?? ''));
        if ($cnpj) { $extras['emit_cnpj'] = $cnpj; }
        $extras['ambiente'] = \App\Models\Setting::get('nfe.environment', \App\Models\Setting::getGlobal('services.delphi.environment', (config('app.env')==='production'?'producao':'homologacao')));
        $certBlock = [];
        if (!empty($emit?->certificate_path)) {
            try {
                $disk = $emit->base_storage_disk ?: config('filesystems.default', 'local');
                $abs = \Illuminate\Support\Facades\Storage::disk($disk)->path((string)$emit->certificate_path);
                if (file_exists($abs)) {
                    $certBlock['path'] = $abs;
                    $certBlock['password'] = $emit->certificate_password_encrypted ? decrypt((string)$emit->certificate_password_encrypted) : null;
                }
            } catch (\Throwable $e) { /* ignore */ }
        }
        if (!empty($certBlock)) { $extras['cert'] = $certBlock; }
        $uf = (string) ($emit->state ?? '');
        if ($uf === '' && method_exists($emit, 'uf')) { try { $uf = (string) $emit->uf; } catch (\Throwable $e) {} }
        $extras['configuracoes'] = [
            'path_schemas' => base_path('DelphiEmissor/Win32/Debug/Schemas/'),
            'path_xml' => base_path('DelphiEmissor/Win32/Debug/nfe/'),
            'uf' => $uf,
            'ambiente' => $extras['ambiente'] ?? null,
        ];

        $xmlPath = (string)($nfe->xml_resolved_path ?: $nfe->arquivo_xml ?: $nfe->xml_path ?: '');
        $chave = (string)($nfe->chave_nfe ?: $nfe->chave_acesso ?: '');
        if ($chave === '' && is_array($nfe->response_received ?? null)) {
            $rr = (array)$nfe->response_received;
            $chave = (string)($rr['chave_acesso'] ?? $rr['chNFe'] ?? $rr['chave'] ?? '');
        }
        if ($chave === '' && $xmlPath !== '') {
            try { $bn = basename($xmlPath); if (preg_match('/(\d{44})/u', $bn, $m)) { $chave = $m[1] ?? ''; } } catch (\Throwable $e) {}
        }
        $chave = preg_replace('/\D+/', '', (string)$chave);

        // Limite recomendado: até 20 CC-e por NF-e (prática comum na SEFAZ)
        try {
            $prev = (array)($nfe->response_received ?? []);
            $existing = is_array($prev['cce_events'] ?? null) ? (array)$prev['cce_events'] : [];
            if (count($existing) >= 20) {
                $msg = 'Limite de 20 Cartas de Correção atingido para esta NF-e.';
                if ($request->wantsJson()) { return response()->json(['ok'=>false,'error'=>$msg], 400); }
                return back()->with('error', $msg);
            }
        } catch (\Throwable $e) {}

        // Log de envio e histórico
        try { \Log::info('CC-e: enviando', ['nfe_id'=>$nfe->id, 'seq'=>$seq, 'chave'=>$chave, 'has_xml'=>($xmlPath!==''), 'ambiente'=>($extras['ambiente']??null)]); } catch (\Throwable $e) {}
        try {
            $prev = (array)($nfe->response_received ?? []);
            $hist = is_array($prev['cce_history'] ?? null) ? (array)$prev['cce_history'] : [];
            $hist[] = [ 'at' => now()->toDateTimeString(), 'user_id'=>auth()->id(), 'user'=>auth()->user()->name ?? null, 'seq' => $seq, 'request' => [ 'chave' => $chave, 'correcao' => $data['correcao'] ] ];
            $prev['cce_history'] = $hist;
            $nfe->response_received = $prev;
            $nfe->save();
        } catch (\Throwable $e) {}

        $res = $svc->cartaCorrecao($chave, $data['correcao'], $seq, $xmlPath, $cnpj ?: null, $extras);
        if ($res['success'] ?? false) {
            $payloadResp = (array)($res['data'] ?? []);
            // Extrai cStat/xMotivo do retorno para feedback amigável
            $cStat = (string)($payloadResp['cStat'] ?? '');
            $xMotivo = (string)($payloadResp['xMotivo'] ?? '');
            $xmlRet = (string)($payloadResp['xml_retorno'] ?? '');
            if ($cStat === '' && $xmlRet !== '') {
                try {
                    $sx = @simplexml_load_string($xmlRet);
                    if ($sx !== false) {
                        $sx->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
                        $c = $sx->xpath('//nfe:retEvento/nfe:infEvento/nfe:cStat');
                        if (is_array($c) && isset($c[0])) { $cStat = (string)$c[0]; }
                        $m = $sx->xpath('//nfe:retEvento/nfe:infEvento/nfe:xMotivo');
                        if (is_array($m) && isset($m[0])) { $xMotivo = (string)$m[0]; }
                    }
                } catch (\Throwable $e) {}
            }

            // Duplicidade de evento: tenta automaticamente com próximas sequências até 20
            if ($cStat === '573') {
                $lastError = null;
                for ($probe = $seq + 1; $probe <= 20; $probe++) {
                    $res2 = $svc->cartaCorrecao($chave, $data['correcao'], $probe, $xmlPath, $cnpj ?: null, $extras);
                    if (!($res2['success'] ?? false)) { $lastError = ($res2['error'] ?? 'erro'); continue; }
                    $payload2 = (array)($res2['data'] ?? []);
                    $cStat2 = (string)($payload2['cStat'] ?? '');
                    $xMotivo2 = (string)($payload2['xMotivo'] ?? '');
                    $xmlRet2 = (string)($payload2['xml_retorno'] ?? '');
                    if ($cStat2 === '' && $xmlRet2 !== '') {
                        try {
                            $sx2 = @simplexml_load_string($xmlRet2);
                            if ($sx2 !== false) {
                                $sx2->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
                                $c2 = $sx2->xpath('//nfe:retEvento/nfe:infEvento/nfe:cStat');
                                if (is_array($c2) && isset($c2[0])) { $cStat2 = (string)$c2[0]; }
                                $m2 = $sx2->xpath('//nfe:retEvento/nfe:infEvento/nfe:xMotivo');
                                if (is_array($m2) && isset($m2[0])) { $xMotivo2 = (string)$m2[0]; }
                            }
                        } catch (\Throwable $e) {}
                    }
                    if (in_array($cStat2, ['135','136'], true)) {
                $nfe->status = 'com_cc';
                try {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes','cc_sequencia')) { $nfe->cc_sequencia = $probe; }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes','cc_ultima_correcao')) { $nfe->cc_ultima_correcao = $data['correcao']; }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes','cc_data')) { $nfe->cc_data = now(); }
                } catch (\Throwable $e) {}
                // Salva XML de evento CC-e no disco
                try {
                    $xmlRet2 = (string)($payload2['xml_retorno'] ?? '');
                    if ($xmlRet2 !== '') {
                        $digits = preg_replace('/\D+/', '', (string)($chave ?: ($payload2['chave'] ?? '')));
                        if ($digits !== '') {
                            $dir = base_path('DelphiEmissor/Win32/Debug/nfe/');
                            if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
                            $file = $dir . $digits . '-procEventoNFe-seq' . $seq . '.xml';
                            @file_put_contents($file, $xmlRet2);
                            // Lista acumulada de eventos
                            try {
                                // Tenta extrair xCorrecao do XML salvo (procEventoNFe)
                                $corr2 = $data['correcao'];
                                try {
                                    if (file_exists($file)) {
                                        $xmlContent2 = @file_get_contents($file);
                                        if ($xmlContent2 !== false) {
                                            $sxCorr2 = @simplexml_load_string($xmlContent2);
                                            if ($sxCorr2 !== false) {
                                                $sxCorr2->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
                                                $x2 = $sxCorr2->xpath('//nfe:evento/nfe:infEvento/nfe:detEvento/nfe:xCorrecao');
                                                if (is_array($x2) && isset($x2[0])) { $corr2 = (string)$x2[0]; }
                                            }
                                        }
                                    }
                                } catch (\Throwable $e) {}
                                $prev = (array)($nfe->response_received ?? []);
                                $events = is_array($prev['cce_events'] ?? null) ? (array)$prev['cce_events'] : [];
                                $events[] = [ 'seq' => $probe, 'cStat' => $cStat2, 'xMotivo' => $xMotivo2, 'correcao' => $corr2, 'xml_path' => $file ];
                                $prev['cce_events'] = $events;
                                $prev['cce_response'] = $payload2;
                                        // Histórico
                                        $hist = is_array($prev['cce_history'] ?? null) ? (array)$prev['cce_history'] : [];
                                        $hist[] = [ 'at' => now()->toDateTimeString(), 'user_id'=>auth()->id(), 'user'=>auth()->user()->name ?? null, 'seq' => $probe, 'response' => [ 'cStat'=>$cStat2, 'xMotivo'=>$xMotivo2 ], 'xml_path' => $file ];
                                        $prev['cce_history'] = $hist;
                                $nfe->response_received = $prev;
                            } catch (\Throwable $e) {}
                        }
                    }
                } catch (\Throwable $e) {}
                        try { \Log::info('CC-e: sucesso duplicidade', ['nfe_id'=>$nfe->id, 'seq'=>$probe, 'cStat'=>$cStat2, 'xMotivo'=>$xMotivo2]); } catch (\Throwable $e) {}
                $nfe->save();
                        if ($request->wantsJson()) {
                            return response()->json(['ok' => true, 'message' => 'Carta de Correção registrada', 'cStat' => ($cStat2 ?: null), 'xMotivo' => ($xMotivo2 ?: null)]);
                        }
                        return back()->with('success','Carta de Correção registrada (cStat '.($cStat2?:'—').')'.($xMotivo2? ': '.$xMotivo2 : ''));
                    }
                    if ($cStat2 === '573') { try { \Log::warning('CC-e: rejeitada duplicidade', ['nfe_id'=>$nfe->id, 'seq'=>$probe, 'cStat'=>$cStat2, 'xMotivo'=>$xMotivo2]); } catch (\Throwable $e) {} continue; }
                    // Outro erro: guarda e termina
                    $lastError = $xMotivo2 ?: ($res2['error'] ?? 'erro');
                    break;
                }
                if ($lastError) {
                    if ($request->wantsJson()) { return response()->json(['ok'=>false,'error'=>'SEFAZ não confirmou CC-e: '.$lastError], 400); }
                    return back()->with('error','SEFAZ não confirmou CC-e: '.$lastError);
                }
            }

            // 135 = Evento registrado e vinculado à NFe; 136 = Vinculado, mas sem alteração no resultado
            if (in_array($cStat, ['135','136'], true)) {
                $nfe->status = 'com_cc';
                try {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes','cc_sequencia')) { $nfe->cc_sequencia = $seq; }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes','cc_ultima_correcao')) { $nfe->cc_ultima_correcao = $data['correcao']; }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes','cc_data')) { $nfe->cc_data = now(); }
                } catch (\Throwable $e) {}
                // Salva XML do evento CC-e
                try {
                    $xmlRet = (string)($payloadResp['xml_retorno'] ?? '');
                    if ($xmlRet !== '') {
                        $digits = preg_replace('/\D+/', '', (string)($chave ?: ($payloadResp['chave'] ?? '')));
                        if ($digits !== '') {
                            $dir = base_path('DelphiEmissor/Win32/Debug/nfe/');
                            if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
                            $file = $dir . $digits . '-procEventoNFe.xml';
                            @file_put_contents($file, $xmlRet);
                            // Lista acumulada de eventos
                            try {
                                // Tenta extrair xCorrecao do XML salvo (procEventoNFe)
                                $corr = $data['correcao'];
                                try {
                                    if (file_exists($file)) {
                                        $xmlContent = @file_get_contents($file);
                                        if ($xmlContent !== false) {
                                            $sxCorr = @simplexml_load_string($xmlContent);
                                            if ($sxCorr !== false) {
                                                $sxCorr->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
                                                $x = $sxCorr->xpath('//nfe:evento/nfe:infEvento/nfe:detEvento/nfe:xCorrecao');
                                                if (is_array($x) && isset($x[0])) { $corr = (string)$x[0]; }
                                            }
                                        }
                                    }
                                } catch (\Throwable $e) {}
                                $prev = (array)($nfe->response_received ?? []);
                                $events = is_array($prev['cce_events'] ?? null) ? (array)$prev['cce_events'] : [];
                                $events[] = [ 'seq' => $seq, 'cStat' => $cStat, 'xMotivo' => $xMotivo, 'correcao' => $corr, 'xml_path' => $file ];
                                $prev['cce_events'] = $events;
                                $prev['cce_response'] = $payloadResp;
                                // Histórico
                                $hist = is_array($prev['cce_history'] ?? null) ? (array)$prev['cce_history'] : [];
                                $hist[] = [ 'at' => now()->toDateTimeString(), 'seq' => $seq, 'response' => [ 'cStat'=>$cStat, 'xMotivo'=>$xMotivo ], 'xml_path' => $file ];
                                $prev['cce_history'] = $hist;
                                $nfe->response_received = $prev;
                            } catch (\Throwable $e) {}
                        }
                    }
                } catch (\Throwable $e) {}
                try { \Log::info('CC-e: sucesso', ['nfe_id'=>$nfe->id, 'seq'=>$seq, 'cStat'=>$cStat, 'xMotivo'=>$xMotivo]); } catch (\Throwable $e) {}
                $nfe->save();
                if ($request->wantsJson()) {
                    return response()->json(['ok' => true, 'message' => 'Carta de Correção registrada', 'cStat' => ($cStat ?: null), 'xMotivo' => ($xMotivo ?: null)]);
                }
                return back()->with('success','Carta de Correção registrada (cStat '.($cStat?:'—').')'.($xMotivo? ': '.$xMotivo : ''));
            }
            try { $prev = (array)($nfe->response_received ?? []); $prev['cce_response'] = $payloadResp; $nfe->response_received = $prev; $nfe->save(); } catch (\Throwable $e) {}
            try { \Log::warning('CC-e: rejeitada', ['nfe_id'=>$nfe->id, 'seq'=>$seq, 'cStat'=>$cStat, 'xMotivo'=>$xMotivo]); } catch (\Throwable $e) {}
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'error' => 'SEFAZ não confirmou CC-e', 'cStat' => ($cStat ?: null), 'xMotivo' => ($xMotivo ?: null)], 400);
            }
            return back()->with('error','SEFAZ não confirmou CC-e (cStat '.($cStat?:'—').')'.($xMotivo? ': '.$xMotivo : ''));
        }
        // Fallback: mesmo quando success=false, tenta extrair cStat 135/136 do XML e tratar como sucesso
        try {
            $payloadErr = (array)($res['data'] ?? []);
            $xmlErr = (string)($payloadErr['xml_retorno'] ?? '');
            $cStatErr = '';
            $xMotivoErr = '';
            if ($xmlErr !== '') {
                $sxE = @simplexml_load_string($xmlErr);
                if ($sxE !== false) {
                    $sxE->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
                    $cE = $sxE->xpath('//nfe:retEvento/nfe:infEvento/nfe:cStat');
                    if (is_array($cE) && isset($cE[0])) { $cStatErr = (string)$cE[0]; }
                    $mE = $sxE->xpath('//nfe:retEvento/nfe:infEvento/nfe:xMotivo');
                    if (is_array($mE) && isset($mE[0])) { $xMotivoErr = (string)$mE[0]; }
                }
            }
            if (in_array($cStatErr, ['135','136'], true)) {
                $nfe->status = 'com_cc';
                try {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes','cc_sequencia')) { $nfe->cc_sequencia = $seq; }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes','cc_ultima_correcao')) { $nfe->cc_ultima_correcao = $data['correcao']; }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes','cc_data')) { $nfe->cc_data = now(); }
                } catch (\Throwable $e) {}
                try {
                    if ($xmlErr !== '') {
                        $digits = preg_replace('/\D+/', '', (string)($chave ?: ($payloadErr['chave'] ?? '')));
                        if ($digits !== '') {
                            $dir = base_path('DelphiEmissor/Win32/Debug/nfe/');
                            if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
                            $file = $dir . $digits . '-procEventoNFe.xml';
                            @file_put_contents($file, $xmlErr);
                            $prev = (array)($nfe->response_received ?? []);
                            $events = is_array($prev['cce_events'] ?? null) ? (array)$prev['cce_events'] : [];
                            $events[] = [ 'seq'=>$seq, 'cStat'=>$cStatErr, 'xMotivo'=>$xMotivoErr, 'correcao'=>$data['correcao'], 'xml_path'=>$file ];
                            $prev['cce_events'] = $events;
                            $prev['cce_response'] = $payloadErr;
                            $hist = is_array($prev['cce_history'] ?? null) ? (array)$prev['cce_history'] : [];
                            $hist[] = [ 'at' => now()->toDateTimeString(), 'seq' => $seq, 'response' => [ 'cStat'=>$cStatErr, 'xMotivo'=>$xMotivoErr ], 'xml_path' => $file ];
                            $prev['cce_history'] = $hist;
                            $nfe->response_received = $prev;
                        }
                    }
                } catch (\Throwable $e) {}
                try { \Log::info('CC-e: sucesso via fallback XML', ['nfe_id'=>$nfe->id, 'seq'=>$seq, 'cStat'=>$cStatErr, 'xMotivo'=>$xMotivoErr]); } catch (\Throwable $e) {}
                $nfe->save();
                if ($request->wantsJson()) { return response()->json(['ok'=>true,'message'=>'Carta de Correção registrada','cStat'=>$cStatErr,'xMotivo'=>$xMotivoErr]); }
                return back()->with('success','Carta de Correção registrada (cStat '.($cStatErr?:'—').')'.($xMotivoErr? ': '.$xMotivoErr : ''));
            }
        } catch (\Throwable $e) {}
        try { \Log::error('CC-e: falha envio', ['nfe_id'=>$nfe->id, 'seq'=>$seq, 'error'=>($res['error']??'erro')]); } catch (\Throwable $e) {}
        try { $prev = (array)($nfe->response_received ?? []); $prev['cce_response'] = (array)($res ?? []); $nfe->response_received = $prev; $nfe->save(); } catch (\Throwable $e) {}
        if ($request->wantsJson()) { return response()->json(['ok' => false, 'error' => ($res['error'] ?? 'erro')], 500); }
        return back()->with('error','Falha ao enviar CC-e: '.($res['error'] ?? 'erro'));
    }

    public function inutilizar(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('nfe.inutilizar'), 403);
        // Sanitização leve antes de validar
        $emitCnpjDigits = preg_replace('/\D+/', '', (string) $request->input('emit_cnpj', ''));
        $just = trim((string) $request->input('justificativa', ''));
        $ano = (int) $request->input('ano', 0);
        $modelo = (int) $request->input('modelo', 55);
        $serie = (int) $request->input('serie', 1);
        $nIni = (int) $request->input('numero_inicial', 0);
        $nFimIn = $request->input('numero_final', null);
        $nFim = $nFimIn === null || $nFimIn === '' ? $nIni : (int) $nFimIn;
        $request->merge([
            'emit_cnpj' => $emitCnpjDigits,
            'justificativa' => $just,
            'ano' => $ano,
            'modelo' => $modelo,
            'serie' => $serie,
            'numero_inicial' => $nIni,
            'numero_final' => $nFimIn,
        ]);

        $validator = \Validator::make($request->all(), [
            'emit_cnpj' => ['required','digits:14'],
            'justificativa' => ['required','string','min:15','max:1000'],
            'ano' => ['required','integer','between:0,99'],
            'modelo' => ['required','in:55'],
            'serie' => ['required','integer','between:0,999'],
            'numero_inicial' => ['required','integer','min:1'],
            'numero_final' => ['nullable','integer','min:1'],
        ], [
            'emit_cnpj.required' => 'Informe o CNPJ do emitente.',
            'emit_cnpj.digits' => 'CNPJ deve conter exatamente 14 dígitos.',
            'justificativa.required' => 'Informe a justificativa.',
            'justificativa.min' => 'Justificativa deve ter no mínimo 15 caracteres.',
            'ano.between' => 'Ano deve estar entre 0 e 99 (dois dígitos).',
            'modelo.in' => 'Modelo inválido. Use 55 para NF-e.',
            'serie.between' => 'Série deve estar entre 0 e 999.',
            'numero_inicial.min' => 'Número inicial deve ser maior ou igual a 1.',
            'numero_final.min' => 'Número final deve ser maior ou igual a 1.',
        ]);
        if ($validator->fails()) {
            if ($request->wantsJson()) { return response()->json(['ok'=>false,'errors'=>$validator->errors()], 422); }
            return back()->withErrors($validator)->withInput();
        }
        $data = $validator->validated();
        try {
            \Log::info('NFe inutilizacao: recebido pedido', [
                'tenant_id' => auth()->user()->tenant_id,
                'emit_cnpj' => preg_replace('/\\D+/', '', (string) $data['emit_cnpj']),
                'ano' => (int) $data['ano'], 'modelo' => (int) $data['modelo'], 'serie' => (int) $data['serie'],
                'numero_inicial' => (int) $data['numero_inicial'], 'numero_final' => (int) ($data['numero_final'] ?? $data['numero_inicial']),
                'just_len' => strlen((string) $data['justificativa']),
            ]);
        } catch (\Throwable $e) {}
		// Validação adicional de faixa numérica
		$ini = (int) $data['numero_inicial'];
		$fim = (int) ($data['numero_final'] ?? $ini);
		if ($fim < $ini) {
			if ($request->wantsJson()) { return response()->json(['ok'=>false,'error'=>'Número final não pode ser menor que o inicial.'], 422); }
			return back()->with('error', 'Número final não pode ser menor que o inicial.');
		}

        $svc = app(\App\Services\NFeService::class);
		$res = $svc->inutilizar($data['emit_cnpj'], $data['justificativa'], (int)$data['ano'], (int)$data['modelo'], (int)$data['serie'], $ini, $fim);
        if ($res['success'] ?? false) {
			$msg = 'Inutilização enviada com sucesso.';
			$dataResp = (array)($res['data'] ?? []);
			$cStat = (string) ($dataResp['cStat'] ?? ($dataResp['status'] ?? ($dataResp['code'] ?? '')));
			$xMotivo = (string) ($dataResp['xMotivo'] ?? ($dataResp['motivo'] ?? ($dataResp['mensagem'] ?? ($dataResp['message'] ?? ''))));

			// Se não existir cStat/xMotivo, tenta extrair do XML
			try {
				$xmlInlineTry = (string) ($dataResp['xml_retorno'] ?? ($dataResp['xml'] ?? ($dataResp['xml_evento'] ?? '')));
				$xmlPathTry = (string) ($dataResp['xml_path'] ?? ($dataResp['xmlPath'] ?? ''));
				if (($cStat === '' || $xMotivo === '') && ($xmlInlineTry !== '' || ($xmlPathTry !== '' && file_exists($xmlPathTry)))) {
					$xmlStr = $xmlInlineTry;
					if ($xmlStr === '' && file_exists($xmlPathTry)) { $xmlStr = @file_get_contents($xmlPathTry) ?: ''; }
					if ($xmlStr !== '') {
						$sx = @simplexml_load_string($xmlStr);
						if ($sx !== false) {
							try {
								$sx->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
								$cx = $sx->xpath('//nfe:retInutNFe/nfe:infInut/nfe:cStat');
								$mx = $sx->xpath('//nfe:retInutNFe/nfe:infInut/nfe:xMotivo');
								if ($cStat === '' && is_array($cx) && isset($cx[0])) { $cStat = (string)$cx[0]; }
								if ($xMotivo === '' && is_array($mx) && isset($mx[0])) { $xMotivo = (string)$mx[0]; }
							} catch (\Throwable $e) {}
                        // Fallback sem namespace
                        if ($cStat === '' || $xMotivo === '') {
                            if ($cStat === '' && preg_match('/<cStat>(\d+)<\/cStat>/u', $xmlStr, $mm1)) { $cStat = (string)($mm1[1] ?? ''); }
                            if ($xMotivo === '' && preg_match('/<xMotivo>([^<]+)<\/xMotivo>/u', $xmlStr, $mm2)) { $xMotivo = (string)($mm2[1] ?? ''); }
                        }
                    }
                    }
                }
            } catch (\Throwable $e) {}

			if ($cStat !== '' || $xMotivo !== '') { $msg .= ' ('.trim('cStat '.$cStat.' '.$xMotivo).')'; }
            try { \Log::info('NFe inutilizacao: sucesso', ['tenant_id'=>auth()->user()->tenant_id,'cStat'=>$cStat,'xMotivo'=>$xMotivo]); } catch (\Throwable $e) {}

			// Persistir histórico de inutilização por tenant (em Settings)
			try {
				$tenantId = auth()->user()->tenant_id;
				$ini = (int) $ini; $fim = (int) $fim;
				$xmlPath = null;
				$xmlRet = (string) ($dataResp['xml_retorno'] ?? ($dataResp['xml'] ?? ($dataResp['xml_evento'] ?? '')));
				$xmlRespPath = (string) ($dataResp['xml_path'] ?? ($dataResp['xmlPath'] ?? ''));
				if ($xmlRet !== '') {
					$dir = base_path('DelphiEmissor/Win32/Debug/nfe/');
					@mkdir($dir, 0777, true);
					$cnpjDigits = preg_replace('/\D+/', '', (string) $data['emit_cnpj']);
					$fname = 'inut_' . $cnpjDigits . '_' . sprintf('%02d', (int)$data['ano']) . '_' . (int)$data['modelo'] . '_' . (int)$data['serie'] . '_' . $ini . '-' . $fim . '.xml';
					$file = $dir . $fname;
					@file_put_contents($file, $xmlRet);
					if (file_exists($file)) { $xmlPath = $file; }
				} elseif ($xmlRespPath !== '' && file_exists($xmlRespPath)) {
					$xmlPath = $xmlRespPath;
				}

				// Fallback: procurar XML gerado recentemente no diretório padrão quando emissor não retornou caminho/inline
				if ($xmlPath === null) {
					try {
						$dir = base_path('DelphiEmissor/Win32/Debug/nfe/');
						if (is_dir($dir)) {
							$files = @glob($dir . '*.xml') ?: [];
							usort($files, function($a,$b){ return @filemtime($b) <=> @filemtime($a); });
							$deadline = time() - 180; // últimos 3 min
							foreach (array_slice($files, 0, 60) as $fp) {
								$mt = @filemtime($fp) ?: 0;
								if ($mt < $deadline) { break; }
								$xmlStr2 = @file_get_contents($fp);
								if (!$xmlStr2) { continue; }
								$sx2 = @simplexml_load_string($xmlStr2);
								if ($sx2 === false) { continue; }
								try {
									$sx2->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
									$cx2 = $sx2->xpath('//nfe:retInutNFe/nfe:infInut');
									if (!is_array($cx2) || !isset($cx2[0])) { continue; }
									$inf = $cx2[0];
									$cnpjX = (string) ($inf->CNPJ ?? '');
									$anoX  = (int) ($inf->ano ?? 0);
									$modX  = (int) ($inf->mod ?? 0);
									$serX  = (int) ($inf->serie ?? 0);
									$nIniX = (int) ($inf->nNFIni ?? 0);
									$nFimX = (int) ($inf->nNFFin ?? 0);
									$okMatch = true;
									if ($cnpjX !== '') { $okMatch = $okMatch && ($cnpjX === preg_replace('/\D+/','',(string)$data['emit_cnpj'])); }
									if ($anoX > 0) { $okMatch = $okMatch && ($anoX === (int)$data['ano']); }
									if ($modX > 0) { $okMatch = $okMatch && ($modX === (int)$data['modelo']); }
									if ($serX > 0) { $okMatch = $okMatch && ($serX === (int)$data['serie']); }
									if ($nIniX > 0) { $okMatch = $okMatch && ($nIniX === (int)$ini); }
									if ($nFimX > 0) { $okMatch = $okMatch && ($nFimX === (int)$fim); }
									if ($okMatch) {
										$xmlPath = $fp;
										// Extrai cStat/xMotivo se ainda não temos
										if ($cStat === '' || $xMotivo === '') {
											try {
												$sx2->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
												$cs = $sx2->xpath('//nfe:retInutNFe/nfe:infInut/nfe:cStat');
												$xm = $sx2->xpath('//nfe:retInutNFe/nfe:infInut/nfe:xMotivo');
												if ($cStat === '' && is_array($cs) && isset($cs[0])) { $cStat = (string)$cs[0]; }
												if ($xMotivo === '' && is_array($xm) && isset($xm[0])) { $xMotivo = (string)$xm[0]; }
											} catch (\Throwable $e) {}
											if ($cStat === '' && preg_match('/<cStat>(\d+)<\/cStat>/u', $xmlStr2, $mm1)) { $cStat = (string)($mm1[1] ?? ''); }
											if ($xMotivo === '' && preg_match('/<xMotivo>([^<]+)<\/xMotivo>/u', $xmlStr2, $mm2)) { $xMotivo = (string)($mm2[1] ?? ''); }
										}
										break;
									}
								} catch (\Throwable $e) {}
							}
						}
					} catch (\Throwable $e) { /* ignore scan errors */ }
				}

				$stored = \App\Models\Setting::get('nfe.inutilizacoes', '[]');
				$list = is_string($stored) ? (json_decode($stored, true) ?: []) : (is_array($stored) ? $stored : []);
				$event = [
					'at' => now()->toDateTimeString(),
					'emit_cnpj' => preg_replace('/\D+/', '', (string) $data['emit_cnpj']),
					'ano' => (int) $data['ano'],
					'modelo' => (int) $data['modelo'],
					'serie' => (int) $data['serie'],
					'numero_inicial' => $ini,
					'numero_final' => $fim,
					'cStat' => $cStat,
					'xMotivo' => $xMotivo,
					'xml_path' => $xmlPath,
				];
				$list[] = $event;
				// Mantém somente os 100 mais recentes
				if (count($list) > 100) { $list = array_slice($list, -100); }
				\App\Models\Setting::set('nfe.inutilizacoes', json_encode($list, JSON_UNESCAPED_UNICODE));
			} catch (\Throwable $e) { /* ignore persist failure */ }

			return back()->with('success', $msg);
		}
        try { \Log::warning('NFe inutilizacao: falha', ['tenant_id'=>auth()->user()->tenant_id,'error'=>$res['error'] ?? null, 'status'=>$res['status'] ?? null]); } catch (\Throwable $e) {}
        return back()->with('error','Falha na inutilização: '.($res['error'] ?? 'Emissor não retornou confirmação da inutilização'));
    }

    public function reprocessInutilizacoes(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('nfe.inutilizar'), 403);
        $tenantId = auth()->user()->tenant_id;
        try {
            $stored = \App\Models\Setting::get('nfe.inutilizacoes', '[]');
            $list = is_string($stored) ? (json_decode($stored, true) ?: []) : (is_array($stored) ? $stored : []);
            if (!is_array($list)) { $list = []; }
            $dir = base_path('DelphiEmissor/Win32/Debug/nfe/');
            $changed = 0;
            foreach ($list as $idx => $ev) {
                $hasC = trim((string)($ev['cStat'] ?? '')) !== '';
                $hasX = trim((string)($ev['xMotivo'] ?? '')) !== '';
                $hasP = trim((string)($ev['xml_path'] ?? '')) !== '' && file_exists((string)$ev['xml_path']);
                if ($hasC && $hasX && $hasP) { continue; }
                $cnpj = preg_replace('/\D+/', '', (string)($ev['emit_cnpj'] ?? ''));
                $ano = (int)($ev['ano'] ?? 0);
                $modelo = (int)($ev['modelo'] ?? 0);
                $serie = (int)($ev['serie'] ?? 0);
                $ini = (int)($ev['numero_inicial'] ?? 0);
                $fim = (int)($ev['numero_final'] ?? 0);
                // Tenta padrão de nome conhecido
                $guess = $dir.'inut_'.$cnpj.'_'.sprintf('%02d',$ano).'_'.$modelo.'_'.$serie.'_'.$ini.'-'.$fim.'.xml';
                $xmlPath = null;
                if (file_exists($guess)) { $xmlPath = $guess; }
                // Se não, varredura curta
                if ($xmlPath === null && is_dir($dir)) {
                    $files = @glob($dir.'*.xml') ?: [];
                    usort($files, function($a,$b){ return @filemtime($b) <=> @filemtime($a); });
                    foreach (array_slice($files, 0, 120) as $fp) {
                        $xml = @file_get_contents($fp);
                        if (!$xml) { continue; }
                        $sx = @simplexml_load_string($xml);
                        if ($sx === false) { continue; }
                        try {
                            $sx->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
                            $inf = $sx->xpath('//nfe:retInutNFe/nfe:infInut');
                            if (!is_array($inf) || !isset($inf[0])) { continue; }
                            $i = $inf[0];
                            $ok = true;
                            if ($cnpj !== '' && (string)($i->CNPJ ?? '') !== '' && preg_replace('/\D+/','',(string)$i->CNPJ) !== $cnpj) { $ok = false; }
                            if ($ok && (int)($i->ano ?? 0) > 0 && (int)$i->ano !== $ano) { $ok = false; }
                            if ($ok && (int)($i->mod ?? 0) > 0 && (int)$i->mod !== $modelo) { $ok = false; }
                            if ($ok && (int)($i->serie ?? 0) > 0 && (int)$i->serie !== $serie) { $ok = false; }
                            if ($ok && (int)($i->nNFIni ?? 0) > 0 && (int)$i->nNFIni !== $ini) { $ok = false; }
                            if ($ok && (int)($i->nNFFin ?? 0) > 0 && (int)$i->nNFFin !== $fim) { $ok = false; }
                            if (!$ok) { continue; }
                            $xmlPath = $fp;
                            $cs = $sx->xpath('//nfe:retInutNFe/nfe:infInut/nfe:cStat');
                            $xm = $sx->xpath('//nfe:retInutNFe/nfe:infInut/nfe:xMotivo');
                            $ev['cStat'] = (string)((is_array($cs) && isset($cs[0])) ? $cs[0] : ($ev['cStat'] ?? ''));
                            $ev['xMotivo'] = (string)((is_array($xm) && isset($xm[0])) ? $xm[0] : ($ev['xMotivo'] ?? ''));
                            if (trim((string)$ev['cStat']) === '' && preg_match('/<cStat>(\d+)<\/cStat>/u', $xml, $m1)) { $ev['cStat'] = (string)($m1[1] ?? ''); }
                            if (trim((string)$ev['xMotivo']) === '' && preg_match('/<xMotivo>([^<]+)<\/xMotivo>/u', $xml, $m2)) { $ev['xMotivo'] = (string)($m2[1] ?? ''); }
                            break;
                        } catch (\Throwable $e) { continue; }
                    }
                }
                if ($xmlPath) { $ev['xml_path'] = $xmlPath; $changed++; }
                $list[$idx] = $ev;
            }
            \App\Models\Setting::set('nfe.inutilizacoes', json_encode($list, JSON_UNESCAPED_UNICODE));
            return back()->with('success', "Reprocesso concluído. Atualizados: $changed evento(s).");
        } catch (\Throwable $e) {
            return back()->with('error', 'Falha ao reprocessar eventos: '.$e->getMessage());
        }
    }
}


