<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\TenantEmitter;
use App\Models\TenantTaxConfig;
use App\Models\Order;
use App\Models\Receivable;
use App\Models\TaxRate;
use App\Models\Product;
use App\Models\Client;
use App\Models\Setting;

class NFeService
{
    private $delphiEndpoint;
    private $timeout;

    public function __construct()
    {
        // Lê de Settings (admin UI) com fallback para config/.env
        $settingsUrl = Setting::getGlobal('services.delphi.url', null);
        $settingsTimeout = Setting::getGlobal('services.delphi.timeout', null);
        $this->delphiEndpoint = $settingsUrl ?: config('services.delphi.url', config('app.delphi_emissor_url', 'http://localhost:18080'));
        $this->timeout = (int) ($settingsTimeout ?: (int) config('services.delphi.timeout', 60));
    }

    /**
     * Emite NFe através do emissor Delphi
     */
    public function emitirNFe(array $dados): array
    {
        try {
            Log::info('Iniciando emissão de NFe', ['dados' => $dados]);

            // Pré-checagem de health/certificado
            try {
                // Usa a verificação robusta (bearer/x-token/query/none)
                $isUp = $this->verificarDisponibilidade();
                if (!$isUp) {
                    return [
                        'success' => false,
                        'error' => 'Sistema de emissão indisponível no momento. Tente novamente em alguns minutos.',
                        'status' => 503,
                    ];
                }
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'error' => 'Sistema de emissão indisponível no momento. Tente novamente em alguns minutos.',
                ];
            }

            // A configuração do emissor é feita diretamente no Delphi. Não mesclar defaults do ERP.
            if (!isset($dados['configuracoes'])) { $dados['configuracoes'] = []; }

            $http = Http::timeout($this->timeout);
            $token = Setting::getGlobal('services.delphi.token');
            if (!empty($token)) { $http = $http->withToken($token); }
            $response = $http->post($this->delphiEndpoint . '/api/emitir-nfe', $dados);

            if ($response->successful()) {
                $resultado = $response->json();
                Log::info('NFe emitida com sucesso', ['resultado' => $resultado]);
                return [
                    'success' => true,
                    'data' => $resultado,
                    'message' => 'NFe emitida com sucesso'
                ];
            } else {
                $data = $response->json() ?? [];
                $status = $response->status();
                $code = $data['code'] ?? null;
                $msg  = 'Não foi possível emitir a nota. Revise os dados e tente novamente.';
                Log::error('Erro ao emitir NFe', ['status' => $status, 'code' => $code, 'body' => $data]);

                // Tentar automaticamente um novo número em caso de duplicidade
                $isDuplicidade = false;
                $dupText = is_string($msg) ? mb_strtolower($msg) : '';
                if (str_contains($dupText, 'duplicidade')) { $isDuplicidade = true; }
                if (is_string($code) && preg_match('/^(204|539)$/', $code)) { $isDuplicidade = true; }

                if ($status === 400 && $isDuplicidade) {
                    try {
                        $tentativas = 0;
                        while ($tentativas < 3) {
                            $tentativas++;
                            // Incrementa número e força novo número
                            $numeroAtual = isset($dados['numero']) && is_numeric($dados['numero']) ? (int)$dados['numero'] : 0;
                            $novoNumero = max(1, $numeroAtual + 1);
                            $dados['numero'] = $novoNumero;
                            $dados['numero_nfe'] = $novoNumero;
                            if (!isset($dados['configuracoes']) || !is_array($dados['configuracoes'])) { $dados['configuracoes'] = []; }
                            $dados['configuracoes']['force_new_number'] = true;

                            Log::warning('Reemitindo NFe após duplicidade, tentando novo número', [
                                'tentativa' => $tentativas,
                                'serie' => $dados['serie'] ?? null,
                                'numero' => $dados['numero'] ?? null,
                            ]);

                            $retry = $http->post($this->delphiEndpoint . '/api/emitir-nfe', $dados);
                            if ($retry->successful()) {
                                $resultado = $retry->json();
                                Log::info('NFe emitida com sucesso após retry de duplicidade', ['resultado' => $resultado]);
                                return [
                                    'success' => true,
                                    'data' => $resultado,
                                    'message' => 'NFe emitida com sucesso'
                                ];
                            }

                            $dataRetry = $retry->json() ?? [];
                            $msgRetry  = $dataRetry['erro'] ?? ($dataRetry['error'] ?? ($dataRetry['mensagem'] ?? ($dataRetry['motivo'] ?? 'Erro ao emitir NFe')));
                            $dupAgain = is_string($msgRetry) && str_contains(mb_strtolower($msgRetry), 'duplicidade');
                            $codeRetry = $dataRetry['code'] ?? null;
                            if (!($retry->status() === 400 && ($dupAgain || (is_string($codeRetry) && preg_match('/^(204|539)$/', $codeRetry))))) {
                                // Sai do loop se não for duplicidade
                                $status = $retry->status();
                                $code = $codeRetry;
                                $msg = $msgRetry;
                                break;
                            }
                        }
                    } catch (Exception $e) {
                        Log::warning('Falha no retry de duplicidade', ['error' => $e->getMessage()]);
                    }
                }
                if ($status === 400) {
                    return [
                        'success' => false,
                        'error' => $msg,
                        'status' => 400,
                        'code' => $code,
                    ];
                }
                if ($response->serverError()) {
                    return [
                        'success' => false,
                        'error' => 'Sistema de emissão indisponível no momento. Tente novamente em alguns minutos.',
                        'status' => $status,
                    ];
                }
                return [
                    'success' => false,
                    'error' => $msg,
                    'status' => $status,
                    'code' => $code,
                ];
            }

        } catch (Exception $e) {
            $msg = $e->getMessage();
            Log::error('Exceção ao emitir NFe', [
                'message' => $msg,
                'trace' => $e->getTraceAsString()
            ]);

            // Mensagem amigável para timeout/conexão
            $friendly = 'Sistema de emissão indisponível no momento. Tente novamente em alguns minutos.';

            return [
                'success' => false,
                'error' => $friendly,
            ];
        }
    }

    /**
     * Emite NFC-e (modelo 65) através do emissor Delphi (ACBrNFe Tunk2)
     * Ajusta automaticamente o payload para PDV/consumidor final e imprime DANFE NFC-e.
     */
    public function emitirNFCE(array $dados): array
    {
        try {
            Log::info('Iniciando emissão de NFC-e', ['dados' => $dados]);

            // Health/cert check usando verificação robusta
            try {
                if (!$this->verificarDisponibilidade()) {
                    return [
                        'success' => false,
                        'error' => 'Sistema de emissão indisponível no momento. Tente novamente em alguns minutos.',
                        'status' => 503,
                    ];
                }
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'error' => 'Sistema de emissão indisponível no momento. Tente novamente em alguns minutos.',
                ];
            }

            // Normalizações específicas de NFC-e
            if (!isset($dados['configuracoes']) || !is_array($dados['configuracoes'])) { $dados['configuracoes'] = []; }
            // Modelo 65
            $dados['configuracoes']['tipo_nota'] = '65'; // compat: alguns emissores aceitam 'tipo_nota' como modelo
            $dados['configuracoes']['modelo'] = 65;
            // Série padrão de PDV pode ser distinta se definido
            try {
                $serieNfce = (string) (\App\Models\Setting::get('nfce.series', '1'));
                $dados['configuracoes']['serie'] = (int) $serieNfce;
            } catch (\Throwable $e) {}
            // Formato de impressão NFC-e (DANFE NFC-e/QR)
            $dados['configuracoes']['formato_impressao'] = 4; // 4 = DANFe NFC-e
            // Indicador de presença (1=presencial) e idDest (1=Operação interna) quando aplicável
            $dados['configuracoes']['indPres'] = (int) ($dados['configuracoes']['indPres'] ?? 1);
            $dados['configuracoes']['idDest'] = (int) ($dados['configuracoes']['idDest'] ?? 1);
            $dados['configuracoes']['indIEDest'] = (int) ($dados['configuracoes']['indIEDest'] ?? 9); // 9 = não contribuinte

            // Certificado: respeita bloco existente, sem mesclar defaults do ERP
            // Pagamentos: NFC-e exige TAG de pagamentos; se ausente, tenta montar a partir de totais
            if (empty($dados['pagamentos']) && !empty($dados['totais'])) {
                $vNF = (float) ($dados['totais']['vNF'] ?? 0);
                if ($vNF > 0) {
                    $tpag = '01'; // dinheiro como fallback
                    $dados['pagamentos'] = [[ 'tPag' => $tpag, 'valor' => $vNF ]];
                }
            }
            // Em vendas de consumidor final, força indicador no cliente quando possível
            try {
                if (isset($dados['cliente']) && is_array($dados['cliente'])) {
                    $isCF = false;
                    $cfVal = $dados['cliente']['consumidor_final'] ?? null;
                    if (is_string($cfVal)) { $isCF = (strtoupper($cfVal) === 'S' || strtoupper($cfVal) === 'SIM'); }
                    if (is_numeric($cfVal)) { $isCF = ((int)$cfVal) === 1; }
                    if (is_bool($cfVal)) { $isCF = $cfVal; }
                    // Heurística adicional para cliente padrão do PDV
                    $nomeCli = (string) ($dados['cliente']['nome'] ?? '');
                    $cpfCli = preg_replace('/\D+/', '', (string) ($dados['cliente']['cpf_cnpj'] ?? ''));
                    if (!$isCF && stripos($nomeCli, 'consumidor final') !== false) { $isCF = true; }
                    if (!$isCF && str_starts_with($cpfCli, '888')) { $isCF = true; }
                    if ($isCF) { $dados['cliente']['consumidor_final'] = 1; }
                    // Se CPF inválido OU faltar UF/cidade/logradouro, envia destinatário mínimo com placeholders
                    $cpfOk = (bool) preg_match('/^\d{11}$/', $cpfCli);
                    $hasUF = trim((string)($dados['cliente']['uf'] ?? '')) !== '';
                    $hasCidade = trim((string)($dados['cliente']['cidade'] ?? '')) !== '';
                    $hasLogr = trim((string)($dados['cliente']['endereco'] ?? '')) !== '';
                    // Sempre enviar bloco cliente com mínimos para o ACBr (sem CPF/CNPJ)
                    unset($dados['cliente']['cpf'], $dados['cliente']['cnpj'], $dados['cliente']['cpf_cnpj']);
                    $dados['cliente']['nome'] = trim((string) ($dados['cliente']['nome'] ?? '')) !== '' ? (string)$dados['cliente']['nome'] : 'Consumidor Final';
                    if (!$hasLogr) { $dados['cliente']['endereco'] = 'Nao informado'; }
                    if (trim((string) ($dados['cliente']['numero'] ?? '')) === '') { $dados['cliente']['numero'] = 'S/N'; }
                    if (trim((string) ($dados['cliente']['bairro'] ?? '')) === '') { $dados['cliente']['bairro'] = 'Nao informado'; }
                    // Herdar cidade/UF/código IBGE do emitente se faltarem
                    try {
                        $tenantIdLocal = auth()->user()->tenant_id ?? null;
                        $emit = $tenantIdLocal ? \App\Models\TenantEmitter::where('tenant_id', $tenantIdLocal)->first() : null;
                        if (!$hasCidade && !empty($emit?->city)) { $dados['cliente']['cidade'] = (string) $emit->city; }
                        if (!$hasUF && !empty($emit?->state)) { $dados['cliente']['uf'] = (string) $emit->state; }
                        $cm = (int) ($dados['cliente']['codigo_municipio'] ?? 0);
                        if ($cm === 0 && !empty($emit?->codigo_ibge)) { $dados['cliente']['codigo_municipio'] = (int) $emit->codigo_ibge; }
                    } catch (\Throwable $e) {}
                }
            } catch (\Throwable $e) {}

            // Endpoint: muitos emissores reutilizam /api/emitir-nfe para 55/65
            $endpoint = rtrim((string)$this->delphiEndpoint, '/') . '/api/emitir-nfe';

            // Autenticação robusta (x-token | bearer | query | none)
            $token = (string) Setting::getGlobal('services.delphi.token', '');
            $authPref = (string) Setting::getGlobal('services.delphi.auth', 'x-token');
            $schemes = $authPref === 'bearer' ? ['bearer','x-token','query','none'] : ($authPref === 'query' ? ['query','x-token','bearer','none'] : ($authPref === 'none' ? ['none','x-token','bearer','query'] : ['x-token','bearer','query','none']));

            $final = null; $lastErr = null; $used = null; $urlUsed = null;
            foreach ($schemes as $scheme) {
                try {
                    $url = $endpoint;
                    $req = Http::timeout($this->timeout);
                    if ($token !== '') {
                        if ($scheme === 'bearer') {
                            $req = $req->withHeaders(['Authorization' => 'Bearer '.$token]);
                        } elseif ($scheme === 'x-token') {
                            $req = $req->withHeaders(['X-Token' => $token, 'X-Authorization' => $token, 'X-Api-Token' => $token]);
                        } elseif ($scheme === 'query') {
                            $url .= (str_contains($url,'?')?'&':'?').'token='.urlencode($token);
                        }
                    }
                    $resp = $req->post($url, $dados);
                    if ($resp->successful()) { $final = $resp; $used = $scheme; $urlUsed = $url; break; }
                    $lastErr = ['status'=>$resp->status(),'body'=>(function($r){ try { return $r->json(); } catch (\Throwable $e) { return $r->body(); } })($resp),'scheme'=>$scheme];
                } catch (\Throwable $e) {
                    $lastErr = ['exception'=>$e->getMessage(),'scheme'=>$scheme];
                }
            }

            if ($final && $final->successful()) {
                $resultado = $final->json();
                $ok = null; try { $ok = is_array($resultado) ? ($resultado['ok'] ?? null) : null; } catch (\Throwable $e) { $ok = null; }
                if ($ok === false) {
                    $msg = $resultado['error'] ?? $resultado['erro'] ?? $resultado['mensagem'] ?? 'Erro ao emitir NFC-e';
                    Log::error('NFC-e retorno com ok=false', ['resultado' => $resultado]);
                    return [ 'success' => false, 'error' => $msg, 'status' => 400, 'data' => $resultado ];
                }
                Log::info('NFC-e emitida com sucesso', ['resultado' => $resultado, 'auth_scheme' => $used, 'endpoint' => $urlUsed]);
                return [ 'success' => true, 'data' => $resultado, 'message' => 'NFC-e emitida com sucesso' ];
            }

            $status = $lastErr['status'] ?? null;
            $body = $lastErr['body'] ?? null;
            $msg = 'Não foi possível emitir a nota. Revise os dados e tente novamente.';
            Log::error('Erro ao emitir NFC-e', ['status' => $status, 'body' => $body, 'last_scheme' => $lastErr['scheme'] ?? null]);
            return [ 'success' => false, 'error' => $msg, 'status' => $status ];

        } catch (Exception $e) {
            Log::error('Exceção ao emitir NFC-e', [ 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString() ]);
            $msg = $e->getMessage();
            $friendly = 'Sistema de emissão indisponível no momento. Tente novamente em alguns minutos.';
            return [ 'success' => false, 'error' => $friendly ];
        }
    }

    /**
     * Emite NFSe através do emissor Delphi
     */
    public function emitirNFSe(array $dados): array
    {
        try {
            Log::info('Iniciando emissão de NFSe', ['dados' => $dados]);

            $response = Http::timeout($this->timeout)
                ->post($this->delphiEndpoint . '/api/emitir-nfse', $dados);

            if ($response->successful()) {
                $resultado = $response->json();
                Log::info('NFSe emitida com sucesso', ['resultado' => $resultado]);
                return [
                    'success' => true,
                    'data' => $resultado,
                    'message' => 'NFSe emitida com sucesso'
                ];
            } else {
                $error = $response->json() ?? ['error' => 'Erro desconhecido'];
                Log::error('Erro ao emitir NFSe', ['error' => $error, 'status' => $response->status()]);
                return [
                    'success' => false,
                    'error' => $error['error'] ?? 'Erro ao comunicar com emissor',
                    'status' => $response->status()
                ];
            }

        } catch (Exception $e) {
            Log::error('Exceção ao emitir NFSe', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Erro de comunicação: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verifica se o emissor Delphi está disponível
     */
    public function verificarDisponibilidade(): bool
    {
        try {
            $baseUrl = rtrim((string)$this->delphiEndpoint, '/');
            $token = (string) Setting::getGlobal('services.delphi.token', '');
            $authPref = (string) Setting::getGlobal('services.delphi.auth', 'x-token'); // x-token|bearer|query|none
            $schemes = $authPref === 'bearer' ? ['bearer','x-token','query','none'] : ($authPref === 'query' ? ['query','x-token','bearer','none'] : ($authPref === 'none' ? ['none','x-token','bearer','query'] : ['x-token','bearer','query','none']));

            foreach ($schemes as $scheme) {
                try {
                    $url = $baseUrl . '/api/status';
                    $req = Http::timeout(5);
                    if ($token !== '') {
                        if ($scheme === 'bearer') {
                            $req = $req->withHeaders(['Authorization' => 'Bearer '.$token]);
                        } elseif ($scheme === 'x-token') {
                            $req = $req->withHeaders(['X-Token' => $token, 'X-Authorization' => $token, 'X-Api-Token' => $token]);
                        } elseif ($scheme === 'query') {
                            $url .= (str_contains($url,'?')?'&':'?').'token='.urlencode($token);
                        }
                    }
                    $resp = $req->get($url);
                    if ($resp->successful()) {
                        // Considera ok quando 2xx e, se JSON, ok=true ou cert_ok verdadeiro/sem erro
                        try {
                            $j = $resp->json();
                            if (is_array($j)) {
                                if (array_key_exists('ok', $j)) { if ((bool)$j['ok'] === true) { return true; } }
                                if (array_key_exists('cert_ok', $j)) { if ((bool)$j['cert_ok'] !== false) { return true; } }
                            }
                        } catch (\Throwable $e) {}
                        return true;
                    }
                } catch (\Throwable $e) {
                    // tenta próximo esquema
                    continue;
                }
            }
            return false;
        } catch (Exception $e) {
            Log::warning('Emissor Delphi não disponível', ['error' => $e->getMessage()]);
            return false;
        }
    }

    // Eventos: cancelamento, carta de correção e inutilização
    public function cancelarNFe(string $chave, string $justificativa, ?string $xmlPath = null, array $extras = []): array
    {
        try {
            $payload = [
                'chave' => $chave,
                'justificativa' => $justificativa,
            ];
            if ($xmlPath) { $payload['xml_path'] = $xmlPath; }

            // Merge campos extras suportados pelo emissor (conforme Postman do usuário)
            // Suporta: cnpj, emit_cnpj, ambiente, cert[path,password], configuracoes[path_schemas, path_xml]
            if (!empty($extras)) {
                foreach (['cnpj','emit_cnpj','ambiente','configuracoes','cert'] as $k) {
                    if (array_key_exists($k, $extras)) { $payload[$k] = $extras[$k]; }
                }
            }

            // Autenticação: tenta Bearer e também cabeçalhos X-Token (robustez)
            $http = Http::timeout($this->timeout);
            $token = Setting::getGlobal('services.delphi.token');
            $authPref = (string) Setting::getGlobal('services.delphi.auth', 'x-token');
            $schemes = $authPref === 'bearer' ? ['bearer','x-token','query','none'] : ($authPref === 'query' ? ['query','x-token','bearer','none'] : ($authPref === 'none' ? ['none','x-token','bearer','query'] : ['x-token','bearer','query','none']));
            $final = null; $lastErr = null; $urlUsed = null;
            foreach ($schemes as $scheme) {
                try {
                    $url = rtrim((string)$this->delphiEndpoint, '/') . '/api/cancelar-nfe';
                    $req = Http::timeout($this->timeout);
                    if (!empty($token)) {
                        if ($scheme === 'bearer') {
                            $req = $req->withHeaders(['Authorization' => 'Bearer '.$token]);
                        } elseif ($scheme === 'x-token') {
                            $req = $req->withHeaders(['X-Token'=>$token,'X-Authorization'=>$token,'X-Api-Token'=>$token]);
                        } elseif ($scheme === 'query') {
                            $url .= (str_contains($url,'?')?'&':'?').'token='.urlencode($token);
                        }
                    }
                    $resp = $req->post($url, $payload);
                    if ($resp->successful()) { $final = $resp; $urlUsed = $url; break; }
                    $lastErr = ['status'=>$resp->status(),'body'=>$resp->body(),'scheme'=>$scheme];
                } catch (\Throwable $e) { $lastErr = ['exception'=>$e->getMessage(),'scheme'=>$scheme]; }
            }
            $res = $final;
            if (!$res) {
                return ['success'=>false,'error'=>'Emissor indisponível para cancelar NF-e','last_error'=>$lastErr,'status'=> $lastErr['status'] ?? null];
            }
            if ($res->successful()) {
                return ['success'=>true, 'data'=>$res->json()];
            }
            return ['success'=>false, 'error'=>$res->body(), 'status'=>$res->status()];
        } catch (Exception $e) {
            return ['success'=>false, 'error'=>$e->getMessage()];
        }
    }

    public function cartaCorrecao(string $chave, string $correcao, int $sequencia = 1, ?string $xmlPath = null, ?string $emitCnpj = null, array $extras = []): array
    {
        try {
            $payload = [
                'chave' => $chave,
                'correcao' => $correcao,
                'sequencia' => $sequencia,
            ];
            if ($xmlPath) { $payload['xml_path'] = $xmlPath; }
            if ($emitCnpj) { $payload['emit_cnpj'] = $emitCnpj; }
            // Merge extras suportados (cert/config)
            if (!empty($extras)) {
                foreach (['configuracoes','cert','ambiente'] as $k) {
                    if (array_key_exists($k, $extras)) { $payload[$k] = $extras[$k]; }
                }
            }
            // Autenticação robusta (mesmo padrão do cancelar)
            $token = Setting::getGlobal('services.delphi.token');
            $authPref = (string) Setting::getGlobal('services.delphi.auth', 'x-token');
            $schemes = $authPref === 'bearer' ? ['bearer','x-token','query','none'] : ($authPref === 'query' ? ['query','x-token','bearer','none'] : ($authPref === 'none' ? ['none','x-token','bearer','query'] : ['x-token','bearer','query','none']));
            $final = null; $lastErr = null; $urlUsed = null;
            foreach ($schemes as $scheme) {
                try {
                    $url = rtrim((string)$this->delphiEndpoint, '/') . '/api/carta-correcao';
                    $req = Http::timeout($this->timeout);
                    if (!empty($token)) {
                        if ($scheme === 'bearer') {
                            $req = $req->withHeaders(['Authorization' => 'Bearer '.$token]);
                        } elseif ($scheme === 'x-token') {
                            $req = $req->withHeaders(['X-Token'=>$token,'X-Authorization'=>$token,'X-Api-Token'=>$token]);
                        } elseif ($scheme === 'query') {
                            $url .= (str_contains($url,'?')?'&':'?').'token='.urlencode($token);
                        }
                    }
                    $resp = $req->post($url, $payload);
                    if ($resp->successful()) { $final = $resp; $urlUsed = $url; break; }
                    $lastErr = ['status'=>$resp->status(),'body'=>$resp->body(),'scheme'=>$scheme];
                } catch (\Throwable $e) { $lastErr = ['exception'=>$e->getMessage(),'scheme'=>$scheme]; }
            }
            $res = $final;
            if (!$res) {
                return ['success'=>false,'error'=>'Emissor indisponível para carta de correção','last_error'=>$lastErr,'status'=> $lastErr['status'] ?? null];
            }
            
            
            
            $res = $final;
            if ($res && $res->successful()) {
                return ['success'=>true, 'data'=>$res->json()];
            }
            return ['success'=>false, 'error'=>$res?->body(), 'status'=>$res?->status()];
        } catch (Exception $e) {
            return ['success'=>false, 'error'=>$e->getMessage()];
        }
    }

    public function inutilizar(string $emitCnpj, string $justificativa, int $ano, int $modelo, int $serie, int $numeroInicial, ?int $numeroFinal = null): array
    {
        try {
            // Monta payload base
            $payload = [
                'emit_cnpj' => preg_replace('/\D+/', '', (string) $emitCnpj),
                'justificativa' => (string) $justificativa,
                'ano' => (int) $ano,
                'modelo' => (int) $modelo,
                'serie' => (int) $serie,
                'numero_inicial' => (int) $numeroInicial,
                'numero_final' => (int) ($numeroFinal ?? $numeroInicial),
            ];

            // Ambiente do ERP/Delphi
            $ambiente = Setting::get('nfe.environment', Setting::getGlobal('services.delphi.environment', (config('app.env') === 'production' ? 'producao' : 'homologacao')));
            if ($ambiente) { $payload['ambiente'] = (string) $ambiente; }

            // Acrescenta blocos compatíveis (certificado e caminhos) quando disponíveis
            try {
                $tenantId = auth()->user()->tenant_id ?? null;
                if ($tenantId) {
                    $emit = TenantEmitter::where('tenant_id', $tenantId)->first();
                    if ($emit) {
                        // Bloco de certificado (PFX) quando existir
                        $cert = [];
                        if (!empty($emit->certificate_path)) {
                            $disk = $emit->base_storage_disk ?: config('filesystems.default', 'local');
                            try {
                                $abs = \Illuminate\Support\Facades\Storage::disk($disk)->path((string)$emit->certificate_path);
                            } catch (\Throwable $e) {
                                $abs = storage_path('app/' . ltrim((string)$emit->certificate_path, '/'));
                            }
                            $cert['path'] = $abs;
                            $cert['password'] = $emit->certificate_password_encrypted ? decrypt((string)$emit->certificate_password_encrypted) : null;
                            if (file_exists($abs)) { $payload['cert'] = $cert; }
                        }
                        // Fallback por número de série do certificado
                        $serial = Setting::get('nfe.certificate_serial');
                        if (!empty($serial)) {
                            if (!isset($payload['cert']) || !is_array($payload['cert'])) { $payload['cert'] = []; }
                            $payload['cert']['serial'] = (string) $serial;
                        }

                        // UF para a SEFAZ correta
                        if (!empty($emit->state)) {
                            $payload['uf'] = (string) $emit->state;
                        }

                        // Caminhos de schemas/XML (compatibilidade com emissor Delphi padrão)
                        $payload['configuracoes'] = [
                            'path_schemas' => base_path('DelphiEmissor/Win32/Debug/Schemas/'),
                            'path_xml' => base_path('DelphiEmissor/Win32/Debug/nfe/'),
                            'ambiente' => (string) $ambiente,
                            'uf' => (string) ($emit->state ?? ''),
                        ];
                    }
                }
            } catch (\Throwable $e) { /* best effort */ }

            // Injeta UF/caminhos quando fornecido pelo controller
            try {
                $cfg = request()->input('__inut_cfg');
                $uf = (string) (request()->input('__inut_uf') ?? '');
                if (is_array($cfg)) {
                    $payload['configuracoes'] = array_merge(($payload['configuracoes'] ?? []), $cfg);
                }
                if ($uf !== '') { $payload['uf'] = $uf; }
            } catch (\Throwable $e) {}

            // Autenticação: tenta bearer, x-token, query e sem auth, nesta ordem configurável
            $token = Setting::getGlobal('services.delphi.token');
            $authPref = (string) Setting::getGlobal('services.delphi.auth', 'x-token'); // x-token|bearer|query|none
            $schemes = $authPref === 'bearer' ? ['bearer','x-token','query','none'] : ($authPref === 'query' ? ['query','x-token','bearer','none'] : ($authPref === 'none' ? ['none','x-token','bearer','query'] : ['x-token','bearer','query','none']));
            $final = null; $lastErr = null; $used = null; $urlUsed = null;
            foreach ($schemes as $scheme) {
                try {
                    $url = rtrim((string)$this->delphiEndpoint, '/') . '/api/inutilizar-nfe';
                    $req = Http::timeout($this->timeout);
                    if (!empty($token)) {
                        if ($scheme === 'bearer') {
                            $req = $req->withHeaders(['Authorization' => 'Bearer '.$token]);
                        } elseif ($scheme === 'x-token') {
                            $req = $req->withHeaders(['X-Token'=>$token,'X-Authorization'=>$token,'X-Api-Token'=>$token]);
                        } elseif ($scheme === 'query') {
                            $url .= (str_contains($url,'?')?'&':'?').'token='.urlencode($token);
                        }
                    }
                    \Log::info('NFe inutilizacao: POST emissor', ['scheme'=>$scheme,'url'=>$url,'tenant_id'=>auth()->user()->tenant_id ?? null,'payload_preview'=>[
                        'emit_cnpj'=>$payload['emit_cnpj'] ?? null,
                        'ano'=>$payload['ano'] ?? null,
                        'modelo'=>$payload['modelo'] ?? null,
                        'serie'=>$payload['serie'] ?? null,
                        'numero_inicial'=>$payload['numero_inicial'] ?? null,
                        'numero_final'=>$payload['numero_final'] ?? null,
                        'ambiente'=>$payload['ambiente'] ?? null,
                    ]]);
                    $resp = $req->post($url, $payload);
                    if ($resp->successful()) { $final = $resp; $used = $scheme; $urlUsed = $url; break; }
                    $lastErr = ['status'=>$resp->status(),'body'=>$resp->body(),'scheme'=>$scheme];
                } catch (\Throwable $e) { $lastErr = ['exception'=>$e->getMessage(),'scheme'=>$scheme]; }
            }

            if (!$final) {
                return ['success'=>false, 'error'=>'Emissor indisponível para inutilização', 'last_error'=>$lastErr, 'status'=>$lastErr['status'] ?? null];
            }

            // Normaliza a resposta
            if ($final->successful()) {
                $parsed = null;
                try { $parsed = $final->json(); } catch (\Throwable $e) { $parsed = null; }

                // Se não for JSON válido ou faltarem campos chave, tenta interpretar o corpo como XML
                $body = null; $contentType = null;
                try { $body = $final->body(); $contentType = (string) ($final->header('Content-Type') ?? ''); } catch (\Throwable $e) {}

                $out = is_array($parsed) ? $parsed : [];
                // Flatten quando vier dentro de data
                if (is_array($out['data'] ?? null)) {
                    $out = array_merge($out, (array)$out['data']);
                }

                $needExtract = (empty($out) || (!isset($out['cStat']) && !isset($out['xml_retorno'])));
                if ($needExtract && is_string($body) && trim($body) !== '') {
                    $xmlStr = null;
                    if (stripos($contentType, 'xml') !== false || str_starts_with(trim($body), '<')) { $xmlStr = $body; }
                    if ($xmlStr !== null) {
                        try {
                            $sx = @simplexml_load_string($xmlStr);
                            if ($sx !== false) {
                                $sx->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
                                $cx = $sx->xpath('//nfe:retInutNFe/nfe:infInut/nfe:cStat');
                                $mx = $sx->xpath('//nfe:retInutNFe/nfe:infInut/nfe:xMotivo');
                                $cStat = (is_array($cx) && isset($cx[0])) ? (string)$cx[0] : '';
                                $xMotivo = (is_array($mx) && isset($mx[0])) ? (string)$mx[0] : '';
                                if ($cStat === '') { if (preg_match('/<cStat>(\d+)<\/cStat>/u', $xmlStr, $m1)) { $cStat = (string)($m1[1] ?? ''); } }
                                if ($xMotivo === '') { if (preg_match('/<xMotivo>([^<]+)<\/xMotivo>/u', $xmlStr, $m2)) { $xMotivo = (string)($m2[1] ?? ''); } }
                                $out['xml_retorno'] = $xmlStr;
                                if ($cStat !== '') { $out['cStat'] = $cStat; }
                                if ($xMotivo !== '') { $out['xMotivo'] = $xMotivo; }
                            }
                        } catch (\Throwable $e) { /* ignore parse errors */ }
                    }
                }

                // Keep raw body for diagnóstico
                if (!isset($out['raw_body']) && is_string($body) && trim($body) !== '') {
                    $out['raw_body'] = mb_substr($body, 0, 10000);
                }

                $cStat = (string) ($out['cStat'] ?? '');
                $xMotivo = (string) ($out['xMotivo'] ?? ($out['mensagem'] ?? ($out['message'] ?? ($out['error'] ?? ''))));
                $hasXml = isset($out['xml_retorno']) || isset($out['xml_path']);
                $hasOk = array_key_exists('ok', $out);
                $okVal = $hasOk ? (bool) $out['ok'] : null;

                // Critérios de sucesso: cStat 102 (inutilização homologada)
                $isSuccess = ($cStat === '102');

                // Falha: qualquer cStat diferente de 102 deve ser tratado como erro
                if (!$isSuccess) {
                    \Log::warning('NFe inutilizacao: resposta não processada', [
                        'tenant_id'=>auth()->user()->tenant_id ?? null,
                        'cStat'=>$cStat,'xMotivo'=>$xMotivo,
                        'has_xml'=>$hasXml,
                    ]);
                    return [
                        'success'=>false,
                        'error'=> ($xMotivo !== '' ? $xMotivo : 'Emissor não retornou confirmação da inutilização'),
                        'data'=>$out,
                    ];
                }

                \Log::info('NFe inutilizacao: resposta sucesso', [
                    'tenant_id'=>auth()->user()->tenant_id ?? null,
                    'cStat'=>$cStat,
                    'has_xml'=>$hasXml,
                ]);
                return ['success'=>true, 'data'=>$out];
            }
            // Quando não vier JSON
            $status = $final->status();
            $data = null; try { $data = $final->json(); } catch (\Throwable $e) { $data = null; }
            $msg = $data['erro'] ?? ($data['error'] ?? $final->body() ?? 'Falha na inutilização');
            \Log::warning('NFe inutilizacao: resposta erro', ['tenant_id'=>auth()->user()->tenant_id ?? null,'status'=>$status,'data'=>$data,'msg'=>$msg]);
            return ['success'=>false, 'error'=>$msg, 'status'=>$status, 'body'=>$final->body(), 'data'=>$data];
        } catch (Exception $e) {
            return ['success'=>false, 'error'=>$e->getMessage()];
        }
    }

    // ====== Builders de Payload Completo (parte 1) ======
    public function buildEmitente(int $tenantId): array
    {
        $emitter = TenantEmitter::where('tenant_id', $tenantId)->first();
        $taxConfig = TenantTaxConfig::where('tenant_id', $tenantId)->first();
        if (!$emitter) { throw new Exception('Emissor não configurado para este tenant.'); }
        return [
            'cnpj' => preg_replace('/\D/', '', (string)$emitter->cnpj),
            'ie' => (string)$emitter->ie,
            'razao_social' => (string)$emitter->razao_social,
            'nome_fantasia' => (string)$emitter->nome_fantasia,
            'logradouro' => (string)$emitter->logradouro,
            'numero' => (string)$emitter->numero,
            'complemento' => (string)$emitter->complemento,
            'bairro' => (string)$emitter->bairro,
            'cep' => preg_replace('/\D/', '', (string)$emitter->cep),
            'cidade' => (string)$emitter->cidade,
            'uf' => (string)$emitter->uf,
            'codigo_municipio' => (string)$emitter->codigo_municipio,
            'telefone' => preg_replace('/\D/', '', (string)$emitter->telefone),
            'email' => (string)$emitter->email,
            'regime_tributario' => (string)($taxConfig->regime_tributario ?? 'simples_nacional'),
            'certificado_caminho' => (string)$emitter->certificado_caminho,
            'certificado_senha' => $emitter->certificado_senha ? decrypt((string)$emitter->certificado_senha) : null,
        ];
    }

    public function buildDestinatario(\App\Models\Client $client): array
    {
        $isPF = (string)$client->type === 'person';
        $doc = preg_replace('/\D/', '', (string)$client->cpf_cnpj);
        return [
            'tipo_pessoa' => $isPF ? 'fisica' : 'juridica',
            $isPF ? 'cpf' : 'cnpj' => $doc,
            $isPF ? 'rg' : 'ie' => (string)$client->ie_rg,
            'razao_social' => (string)$client->name,
            'logradouro' => (string)$client->address,
            'numero' => (string)$client->number,
            'complemento' => (string)$client->complement,
            'bairro' => (string)$client->neighborhood,
            'cep' => preg_replace('/\D/', '', (string)$client->zip_code),
            'cidade' => (string)$client->city,
            'uf' => (string)$client->state,
            'codigo_municipio' => (string)$client->codigo_ibge,
            'telefone' => preg_replace('/\D/', '', (string)$client->phone),
            'email' => (string)$client->email,
            'consumidor_final' => (bool)$client->consumidor_final,
        ];
    }

    /**
     * Prepara dados do cliente para o formato do Delphi
     */
    public function prepararDadosCliente($cliente): array
    {
        // Mantido por compatibilidade com fluxos antigos
        $isPF = (string)$cliente->type === 'person';
        $doc = preg_replace('/\D/', '', (string)$cliente->cpf_cnpj);
        return [
            'tipo_pessoa' => $isPF ? 'fisica' : 'juridica',
            $isPF ? 'cpf' : 'cnpj' => $doc,
            $isPF ? 'rg' : 'ie' => (string)$cliente->ie_rg,
            'razao_social' => (string)$cliente->name,
            'logradouro' => (string)$cliente->address,
            'numero' => (string)$cliente->number,
            'complemento' => (string)$cliente->complement,
            'bairro' => (string)$cliente->neighborhood,
            'cep' => preg_replace('/\D/', '', (string)$cliente->zip_code),
            'cidade' => (string)$cliente->city,
            'uf' => (string)$cliente->state,
            'codigo_municipio' => (string)$cliente->codigo_ibge,
            'telefone' => preg_replace('/\D/', '', (string)$cliente->phone),
            'email' => (string)$cliente->email,
            'consumidor_final' => (bool)$cliente->consumidor_final,
        ];
    }

    /**
     * Prepara dados dos produtos para o formato do Delphi
     */
    public function prepararDadosProdutos($itens): array
    {
        // Método obsoleto no novo fluxo. Mantido por compatibilidade.
        $out = [];
        foreach ($itens as $item) {
            $prod = $item->product ?? null;
            $out[] = [
                'id' => $item->product_id,
                'nome' => $prod?->name,
                'ncm' => $prod?->ncm,
                'cest' => $prod?->cest,
                'origem' => $prod?->origin,
                'quantidade' => $item->quantity,
                'valor_unitario' => $item->unit_price,
                'unidade' => $prod?->unit,
                'valor_total' => $item->line_total ?? ($item->quantity * $item->unit_price),
            ];
        }
        return $out;
    }

    /**
     * Monta payload completo de emissão a partir de um Pedido
     * Compatível com o emissor Delphi/ACBr utilizado no projeto (Tunk2).
     */
    public function buildOrderPayload(\App\Models\Order $order, int $tenantId, array $options = []): array
    {
        $cliente = $order->client; // pode ser null em PDV sem cliente obrigatório
        $emitter = \App\Models\TenantEmitter::where('tenant_id', $tenantId)->first();

        // Produtos (iteração primeiro para calcular rateios)
        $items = $order->items()->get();
        $grosses = [];
        $itemDesc = [];
        $weights = [];
        $totalVProd = 0.0; $totalDesc = 0.0; $totalFrete = 0.0; $totalSeg = 0.0; $totalOutro = 0.0; $totalAcresc = 0.0;
        foreach ($items as $it) {
            $q = (float) $it->quantity; $u = (float) $it->unit_price; $gross = round($q * $u, 2);
            $d = (float) ($it->discount_value ?? 0.0);
            $grosses[] = $gross; $itemDesc[] = $d; $weights[] = max(0.0, $gross - $d);
            $totalVProd += $gross;
        }
        $totalDescHeader = (float) ($order->discount_total ?? 0.0);
        $totalDescItens = array_sum($itemDesc);
        $totalDesc = $totalDescItens + $totalDescHeader;
        $totalFrete = (float) ($order->freight_cost ?? 0);
        $totalSeg = (float) ($order->valor_seguro ?? 0);
        $totalOutro = (float) ($order->outras_despesas ?? 0);
        $totalAcresc = (float) ($order->addition_total ?? 0);
        $totalOutroComAcresc = (float) ($totalOutro + $totalAcresc);

        // Rateio proporcional do desconto de cabeçalho
        $alocDescHeader = [];
        $sumW = array_sum($weights);
        if ($totalDescHeader > 0 && $sumW > 0) {
            $raw = [];$floor = [];$sumFloor = 0.0;
            foreach ($weights as $w) { $val = ($w / $sumW) * $totalDescHeader; $raw[] = $val; $fv = floor($val*100)/100; $floor[] = $fv; $sumFloor += $fv; }
            $remCents = (int) round(($totalDescHeader - $sumFloor) * 100);
            $idxs = array_keys($raw);
            usort($idxs, function($a,$b) use ($raw,$floor){ $fa=($raw[$a]-$floor[$a]); $fb=($raw[$b]-$floor[$b]); return $fb <=> $fa; });
            $aloc = $floor; $k = 0;
            while ($remCents > 0 && $k < count($idxs)) { $aloc[$idxs[$k]] = round($aloc[$idxs[$k]] + 0.01, 2); $remCents--; $k++; }
            $alocDescHeader = $aloc;
        } else {
            $alocDescHeader = array_fill(0, count($weights), 0.0);
        }

        // Montagem dos produtos no formato esperado pelo emissor (tudo como string para evitar 0)
        $produtos = [];
        foreach ($items as $i => $item) {
            $product = $item->product;
            if (!$product) { continue; }
            $qtd = (float) $item->quantity;
            $unit = (float) $item->unit_price;
            $vProd = (float) ($grosses[$i] ?? round($qtd * $unit, 2));
            $vDesc = round(((float)($itemDesc[$i] ?? 0)) + ((float)($alocDescHeader[$i] ?? 0)), 2);
            if ($vDesc > $vProd) { $vDesc = $vProd; }
            $vFrete = 0.0; $vSeg = 0.0; $vOutro = 0.0; // sem rateio fino aqui
            $qtySafe = max(0.0001, $qtd);
            $vUnCom = round($vProd / $qtySafe, 10);

            $skuVal = trim((string) ($product->sku ?? ''));
            if ($skuVal === '') { $skuVal = 'PROD-' . str_pad((string) $item->product_id, 6, '0', STR_PAD_LEFT); }
            $ncm = preg_replace('/\D/', '', (string) ($product->ncm ?? ''));
            if (strlen($ncm) !== 8) { $ncm = '00000000'; }
            $cest = preg_replace('/\D/', '', (string) ($product->cest ?? ''));
            $origem = (int) ($product->origin ?? 0);
            if ($origem < 0 || $origem > 8) { $origem = 0; }

            $produtos[] = [
                'id' => $item->product_id,
                'nome' => (string) ($product->name ?? $item->name ?? 'Item'),
                'codigo' => $skuVal,
                'cProd' => $skuVal,
                'ncm' => $ncm,
                'cest' => $cest,
                'origem' => $origem,
                'unidade' => (string) ($product->unit ?? $item->unit ?? 'UN'),
                'quantidade' => number_format($qtd, 4, ',', ''),
                'valor_unitario' => number_format($vUnCom, 10, ',', ''),
                'valor_total' => number_format($vProd, 2, ',', ''),
                'vDesc' => number_format($vDesc, 2, ',', ''),
                'vFrete' => number_format($vFrete, 2, ',', ''),
                'vSeg' => number_format($vSeg, 2, ',', ''),
                'vOutro' => number_format($vOutro, 2, ',', ''),
                'cfop' => (string) ($product->cfop ?? '5102'),
                'cst' => (string) ($product->cst ?? $product->cst_icms ?? ''),
                'aliquota_icms' => (float) ($product->aliquota_icms ?? 0),
                'aliquota_pis' => (float) ($product->aliquota_pis ?? 0),
                'aliquota_cofins' => (float) ($product->aliquota_cofins ?? 0),
            ];
        }

        $vNF = max(0.0, ($totalVProd - $totalDesc) + $totalFrete + $totalSeg + $totalOutroComAcresc);

        // Pagamentos a partir dos títulos do pedido (ou fallback por total)
        $tPagMap = [
            'DINHEIRO' => '01', 'CHEQUE' => '02', 'CARTAO' => '03', 'CARTAO_CREDITO' => '03',
            'CARTAO_DEBITO' => '04', 'CREDITO_LOJA' => '05', 'BOLETO' => '15', 'PIX' => '17',
            'DEPOSITO' => '16', 'OUTROS' => '99', 'CASH' => '01', 'CARD' => '03',
        ];
        $pagamentos = [];
        try {
            $receivables = \App\Models\Receivable::where('tenant_id', $tenantId)->where('order_id', $order->id)->get();
            if ($receivables->count() > 0) {
                foreach ($receivables as $rec) {
                    $tipo = strtoupper((string) ($rec->payment_method ?? 'OUTROS'));
                    if ($tipo === 'CREDIT' || $tipo === 'CARTAOCREDITO') { $tipo = 'CARTAO_CREDITO'; }
                    if ($tipo === 'DEBIT' || $tipo === 'CARTAODEBITO') { $tipo = 'CARTAO_DEBITO'; }
                    $tPag = $rec->tpag_override ?: ($tPagMap[$tipo] ?? '99');
                    $p = [ 'tipo' => $tipo, 'tPag' => $tPag, 'valor' => (float) $rec->amount ];
                    if ($tPag === '15' && $rec->due_date) { $p['vencimento'] = optional($rec->due_date)->toDateString(); }
                    $pagamentos[] = $p;
                }
            }
        } catch (\Throwable $e) {}
        if (count($pagamentos) === 0) {
            if ($vNF > 0) { $pagamentos[] = [ 'tPag' => '01', 'valor' => round($vNF, 2) ]; }
        }

        // Certificado (serial e/ou PFX)
        $certBlock = [];
        try {
            $certificateSerial = \App\Models\Setting::get('nfe.certificate_serial');
            if (!empty($certificateSerial)) { $certBlock['serial'] = (string) $certificateSerial; }
            if (!empty($emitter?->certificate_path)) {
                $disk = $emitter->base_storage_disk ?: config('filesystems.default', 'local');
                try { $abs = \Illuminate\Support\Facades\Storage::disk($disk)->path((string) $emitter->certificate_path); }
                catch (\Throwable $e) { $abs = storage_path('app/' . ltrim((string)$emitter->certificate_path, '/')); }
                $certBlock['path'] = $abs;
                $certBlock['password'] = $emitter->certificate_password_encrypted ? decrypt((string)$emitter->certificate_password_encrypted) : null;
            }
        } catch (\Throwable $e) {}

        // Cliente no formato esperado pelo emissor (quando houver)
        $clienteCodigoMunicipio = 0;
        if ($cliente) {
            try { $clienteCodigoMunicipio = (int) ($cliente->codigo_municipio ?: $cliente->codigo_ibge ?: 0); } catch (\Throwable $e) { $clienteCodigoMunicipio = 0; }
        }
        if ($clienteCodigoMunicipio === 0) { $clienteCodigoMunicipio = (int) ($emitter?->codigo_ibge ?: 0); }

        $clienteBlock = $cliente ? [
            'id' => $cliente->id,
            'nome' => (string) $cliente->name,
            'cpf_cnpj' => (string) $cliente->cpf_cnpj,
            'tipo' => ($cliente->type === 'pf' || $cliente->type === 'person') ? 'PESSOA FÍSICA' : 'JURÍDICA',
            'endereco' => (string) $cliente->address,
            'numero' => (string) $cliente->number,
            'complemento' => (string) $cliente->complement,
            'bairro' => (string) $cliente->neighborhood,
            'cidade' => (string) $cliente->city,
            'uf' => (string) $cliente->state,
            'cep' => (string) $cliente->zip_code,
            'telefone' => (string) $cliente->phone,
            'email' => (string) $cliente->email,
            'consumidor_final' => ($cliente->consumidor_final === 'S' ? 1 : 0),
            'codigo_municipio' => $clienteCodigoMunicipio,
        ] : null;

        // Configurações gerais
        $ambiente = (string) (\App\Models\Setting::get('nfe.environment', \App\Models\Setting::getGlobal('services.delphi.environment', (config('app.env') === 'production' ? 'producao' : 'homologacao'))));
        $serie = (int) (\App\Models\Setting::get('nfe.series', '1'));

            $payload = [
            'tipo' => 'nfe',
            'numero_pedido' => (string) $order->number,
            'tenant_id' => $tenantId,
            'cliente' => $clienteBlock,
            'produtos' => $produtos,
            'cert' => $certBlock,
            'emitente' => [
                'cnpj' => preg_replace('/\D+/', '', (string) ($emitter?->cnpj ?? '')),
                'ie' => $emitter?->ie,
                'razao_social' => $emitter?->razao_social, 'nome_fantasia' => $emitter?->nome_fantasia,
                'endereco' => $emitter?->address, 'numero' => $emitter?->number, 'complemento' => $emitter?->complement,
                'bairro' => $emitter?->neighborhood, 'codigo_municipio' => (int) $emitter?->codigo_ibge,
                'cidade' => $emitter?->city, 'uf' => $emitter?->state, 'cep' => $emitter?->zip_code,
            ],
            'configuracoes' => [
                'cfop' => (string) ($options['cfop'] ?? '5102'),
                'ambiente' => $ambiente,
                'serie' => (string) $serie,
                'tipo_nota' => (string) ($options['tipo_nota'] ?? '55'),
                'natOp' => (string) ($options['natOp'] ?? 'Venda de mercadoria'),
                'uf' => (string) ($emitter?->state ?: ($cliente?->state ?? 'SP')),
                'cMunFG' => (int) ($emitter?->codigo_ibge ?: $clienteCodigoMunicipio ?: 0),
                'tpNF' => (int) ($options['tpNF'] ?? 1),
                'finNFe' => (int) ($options['finNFe'] ?? 1),
                'idDest' => (int) ($options['idDest'] ?? 1),
                'operation_type' => (string) ($options['tipo_operacao'] ?? 'venda'),
                'reference_key' => ($rk = trim((string) ($options['reference_key'] ?? ''))) !== '' ? $rk : null,
                'gerar_pdf' => (bool) ($options['gerar_pdf'] ?? true),
                'logo_path' => (function(){
                    try {
                        $p = (string) \App\Models\Setting::get('ui.logo_path');
                        if ($p && file_exists(public_path($p))) { return public_path($p); }
                        $fallback = public_path('logo/logo.png');
                        return file_exists($fallback) ? $fallback : null;
                    } catch (\Throwable $e) { return null; }
                })(),
            ],
            'transporte' => [
                'modalidade' => (int) ($order->freight_mode ?? 9),
                'responsavel' => $order->freight_payer,
                'transportadora_id' => $order->carrier_id,
                'valor_frete' => $order->freight_cost,
                'observacoes' => $order->freight_obs,
                'despesas' => [
                    'seguro' => (float) ($order->valor_seguro ?? 0),
                    'outras' => (float) ($order->outras_despesas ?? 0),
                ],
            ],
            'observacoes' => [
                'inf_complementar' => (string) ($order->additional_info ?? ''),
                'inf_fisco' => (string) ($order->fiscal_info ?? ''),
            ],
            'pagamentos' => $pagamentos,
            'totais' => [
                'vProd' => number_format((float)$totalVProd, 2, '.', ''),
                'vDesc' => number_format((float)$totalDesc, 2, '.', ''),
                'vFrete' => number_format((float)$totalFrete, 2, '.', ''),
                'vSeg' => number_format((float)$totalSeg, 2, '.', ''),
                'vOutro' => number_format((float)$totalOutroComAcresc, 2, '.', ''),
                'vNF' => number_format((float)$vNF, 2, '.', ''),
            ],
        ];

        // Ajustar indIEDest conforme tipo/IE do cliente (quando presente)
        try {
            if (is_array($payload['configuracoes'] ?? null) && is_array($payload['cliente'] ?? null)) {
                $clienteType = (string) ($order->client?->type ?? 'pf');
                $ieVal = trim((string) ($order->client?->ie_rg ?? ''));
                $indIEDest = 9;
                if (strtolower($clienteType) !== 'pf') { // PJ
                    if ($ieVal !== '' && !preg_match('/^(ISENTO|ISENTA)$/i', $ieVal)) { $indIEDest = 1; }
                    else { $indIEDest = 2; }
                }
                $payload['configuracoes']['indIEDest'] = (int) $indIEDest;
                if ($indIEDest === 1 || $indIEDest === 2) { $payload['cliente']['ie'] = $ieVal !== '' ? $ieVal : 'ISENTO'; }
            }
        } catch (\Throwable $e) {}

        return $payload;
    }
}
