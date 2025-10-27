<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NfeNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id','client_id','order_id','numero_pedido','serie_nfe','numero_nfe','chave_nfe','chave_acesso','protocolo','protocolo_autorizacao',
        'status','arquivo_xml','arquivo_danfe','data_emissao','data_transmissao',
        'cancelamento_justificativa','cancelamento_data','cc_sequencia','cc_ultima_correcao','cc_data',
        'payload_sent','response_received','emitted_at','error_message','xml_path','pdf_path'
    ];

    protected $casts = [
        'data_emissao' => 'datetime',
        'data_transmissao' => 'datetime',
        'cancelamento_data' => 'datetime',
        'cc_data' => 'datetime',
        'payload_sent' => 'array',
        'response_received' => 'array',
        'status' => 'string',
        'emitted_at' => 'datetime',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function order() { return $this->belongsTo(Order::class); }

    // Atributos computados para a UI
    public function getNumeroNfeResolvedAttribute(): ?string
    {
        if (!empty($this->numero_nfe)) { return (string) $this->numero_nfe; }
        $resp = $this->response_received;
        if (is_array($resp)) {
            $data = is_array($resp['data'] ?? null) ? $resp['data'] : [];
            return $resp['numero']
                ?? $resp['nNF']
                ?? ($data['numero'] ?? $data['nNF'] ?? null);
        }
        return null;
    }

    public function getChaveAcessoResolvedAttribute(): ?string
    {
        $chave = (string) ($this->chave_acesso ?: $this->chave_nfe ?: '');
        if ($chave === '') {
            $resp = $this->response_received;
            if (is_array($resp)) {
                $data = is_array($resp['data'] ?? null) ? $resp['data'] : [];
                $chave = (string) (
                    $resp['chave_acesso'] ?? $resp['chNFe'] ?? $resp['chave']
                    ?? $data['chave_acesso'] ?? $data['chNFe'] ?? $data['chave'] ?? ''
                );
            }
        }
        if ($chave !== '' && stripos($chave, 'NFe') === 0) { $chave = substr($chave, 3); }
        return $chave !== '' ? $chave : null;
    }

    public function getStatusNameAttribute(): string
    {
        $base = (string)$this->status;
        if ($base === 'error') {
            $cStat = null;
            if (is_array($this->response_received ?? null)) {
                $cStat = $this->response_received['cStat'] ?? null;
            }
            if ((string)$cStat === '204') { return 'Duplicada (204)'; }
            return 'Rejeitada';
        }
        return match($base) {
            'pending' => 'Pendente',
            'emitted' => 'Emitida',
            'cancelled' => 'Cancelada',
            default => ucfirst($base),
        };
    }

    public function getCanRetryAttribute(): bool
    {
        return (string)$this->status === 'error';
    }

    public function getCanCancelAttribute(): bool
    {
        return in_array((string)$this->status, ['emitted'], true);
    }

    public function getCanDownloadXmlAttribute(): bool
    {
        return !empty($this->xml_resolved_path);
    }

    public function getCanDownloadPdfAttribute(): bool
    {
        return !empty($this->pdf_resolved_path);
    }

    public function getStatusHintAttribute(): ?string
    {
        $resp = $this->response_received;
        if (!is_array($resp)) { return null; }
        $cStat = (string)($resp['cStat'] ?? '');
        $xMotivo = (string)($resp['xMotivo'] ?? ($resp['error'] ?? ''));
        if ($cStat === '204') { return 'Já autorizada anteriormente'; }
        if ($this->status === 'error' && $xMotivo !== '') { return $xMotivo; }
        return null;
    }

    // Caminhos resolvidos com fallback
    public function getXmlResolvedPathAttribute(): ?string
    {
        $candidates = [];
        if (!empty($this->xml_path)) { $candidates[] = (string)$this->xml_path; }
        if (!empty($this->arquivo_xml)) { $candidates[] = (string)$this->arquivo_xml; }
        // response_received
        $resp = $this->response_received;
        if (is_array($resp)) {
            if (!empty($resp['xml_path'])) { $candidates[] = (string)$resp['xml_path']; }
            if (!empty($resp['arquivo_xml'])) { $candidates[] = (string)$resp['arquivo_xml']; }
        }
        // Fallback pelo padrão do Emissor Delphi
        $chave = (string) ($this->chave_acesso ?: $this->chave_nfe ?: '');
        if ($chave === '' && is_array($resp)) {
            $chave = (string)($resp['chave_acesso'] ?? $resp['chNFe'] ?? $resp['chave'] ?? '');
            if (stripos($chave, 'NFe') === 0) { $chave = substr($chave, 3); }
        }
        if ($chave !== '') {
            $digits = preg_replace('/\D+/', '', $chave);
            if ($digits !== '') {
                $base1 = base_path('DelphiEmissor/Win32/Debug/nfe/' . $digits . '-nfe.xml');
                $base2 = base_path('DelphiEmissor/Win32/Release/nfe/' . $digits . '-nfe.xml');
                $candidates[] = $base1;
                $candidates[] = $base2;
            }
        }
        foreach ($candidates as $p) {
            if ($p && file_exists($p)) { return $p; }
        }
        return null;
    }

    public function getPdfResolvedPathAttribute(): ?string
    {
        $candidates = [];
        if (!empty($this->pdf_path)) { $candidates[] = (string)$this->pdf_path; }
        if (!empty($this->arquivo_danfe)) { $candidates[] = (string)$this->arquivo_danfe; }
        $resp = $this->response_received;
        if (is_array($resp)) {
            if (!empty($resp['pdf_path'])) { $candidates[] = (string)$resp['pdf_path']; }
            if (!empty($resp['danfe_pdf'])) { $candidates[] = (string)$resp['danfe_pdf']; }
        }
        foreach ($candidates as $p) {
            if ($p && file_exists($p)) { return $p; }
        }
        return null;
    }
}
