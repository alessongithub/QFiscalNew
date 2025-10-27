<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orçamento {{ $quote->number }}</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            color: #2d3748; 
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: #f7fafc;
        }
        .container { 
            max-width: 900px; 
            margin: 0 auto; 
            padding: 32px; 
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .header {
            border-bottom: 3px solid #3182ce;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-info h1 {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin: 0 0 8px 0;
        }
        .header-info .subtitle {
            font-size: 16px;
            color: #4a5568;
            margin: 0 0 4px 0;
        }
        .header-info .date {
            font-size: 14px;
            color: #718096;
        }
        .logo {
            height: 60px;
            border-radius: 4px;
        }
        .client-info {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
            padding: 16px;
            background: #f7fafc;
            border-radius: 6px;
        }
        .info-item {
            text-align: center;
        }
        .info-label {
            font-weight: 600;
            color: #4a5568;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 14px;
            color: #2d3748;
            font-weight: 500;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 16px;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        th { 
            background: #3182ce;
            color: white;
            padding: 12px 8px;
            font-size: 13px;
            font-weight: 600;
            text-align: left;
        }
        td { 
            border-bottom: 1px solid #e2e8f0; 
            padding: 12px 8px; 
            font-size: 14px;
            vertical-align: top;
        }
        .right { text-align: right; }
        .total { 
            margin-top: 20px; 
            border: 2px solid #3182ce; 
            padding: 16px; 
            width: 350px; 
            margin-left: auto;
            border-radius: 6px;
            background: #f7fafc;
        }
        .total-row {
            display: flex; 
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .total-final {
            font-weight: 700;
            font-size: 16px;
            color: #2d3748;
            border-top: 2px solid #3182ce;
            padding-top: 8px;
            margin-top: 8px;
        }
        .muted { 
            color: #718096; 
            font-size: 12px; 
        }
        .section {
            margin-top: 24px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        .section-header {
            background: #f7fafc;
            padding: 12px 16px;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 1px solid #e2e8f0;
        }
        .section-content {
            padding: 16px;
        }
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        .payment-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background: #f7fafc;
            border-radius: 4px;
            font-size: 14px;
        }
        .payment-icon {
            width: 16px;
            height: 16px;
            margin-right: 8px;
            color: #3182ce;
        }
        .validity-highlight {
            background: #fed7d7;
            color: #c53030;
            padding: 8px 12px;
            border-radius: 4px;
            font-weight: 600;
            text-align: center;
            margin-top: 8px;
        }
        .footer {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            color: #718096;
            font-size: 12px;
        }
        @media print { 
            .no-print { display: none; }
            body { background: white; }
            .container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="header-info">
                    <h1>Orçamento #{{ $quote->number }}</h1>
                    <div class="subtitle">{{ $quote->title }}</div>
                    <div class="date">Emitido em: {{ optional($quote->created_at)->format('d/m/Y H:i') }}</div>
                </div>
                @php
                    $logoUrl = null;
                    if ($quote->tenant && $quote->tenant->logo_path) {
                        // Se logo_path é um caminho relativo, usar asset()
                        if (strpos($quote->tenant->logo_path, 'http') === 0) {
                            $logoUrl = $quote->tenant->logo_path;
                        } else {
                            // Verificar se arquivo existe
                            if (file_exists(public_path($quote->tenant->logo_path))) {
                                $logoUrl = asset($quote->tenant->logo_path);
                            } elseif (file_exists(storage_path('app/public/' . $quote->tenant->logo_path))) {
                                $logoUrl = asset('storage/' . $quote->tenant->logo_path);
                            }
                        }
                    }
                    
                    if (!$logoUrl) {
                        // Fallbacks
                        if (file_exists(public_path('logo/logo.png'))) {
                            $logoUrl = asset('logo/logo.png');
                        } elseif (file_exists(public_path('logo.png'))) {
                            $logoUrl = asset('logo.png');
                        } else {
                            $logoUrl = asset('logo.png'); // Fallback final
                        }
                    }
                @endphp
                <img src="{{ $logoUrl }}" class="logo"/>
            </div>
        </div>

        <!-- Client Information -->
        <div class="client-info">
            <div class="info-item">
                <div class="info-label">Cliente</div>
                <div class="info-value">{{ optional($quote->client)->name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value">
                    @switch($quote->status)
                        @case('awaiting') Aguardando @break
                        @case('approved') Aprovado @break
                        @case('not_approved') Rejeitado @break
                        @case('canceled') Cancelado @break
                        @default {{ $quote->status }} @break
                    @endswitch
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Validade</div>
                <div class="info-value">
                    @if($quote->validity_date)
                        {{ $quote->validity_date->format('d/m/Y') }}
                        @if($quote->validity_date->isPast())
                            <div class="validity-highlight">VENCIDO</div>
                        @endif
                    @else
                        Não definida
                    @endif
                </div>
            </div>
        </div>

        <table>
            <thead>
            <tr>
                <th>Item</th><th>Qtd</th><th>UN</th><th class="right">V.Unit</th><th class="right">Desc.</th><th class="right">Acrésc.</th><th class="right">Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($quote->items as $it)
                <tr>
                    <td>
                        <div>{{ $it->name }}</div>
                        @if($it->description)<div class="muted">{{ $it->description }}</div>@endif
                        @if($it->delivery_date)<div class="muted" style="font-size: 11px; color: #4a5568; margin-top: 2px;">📅 Entrega: {{ \Carbon\Carbon::parse($it->delivery_date)->format('d/m/Y') }}</div>@endif
                    </td>
                    <td>{{ number_format((float)$it->quantity, 3, ',', '.') }}</td>
                    <td>{{ $it->unit }}</td>
                    <td class="right">R$ {{ number_format((float)$it->unit_price, 2, ',', '.') }}</td>
                    <td class="right">R$ {{ number_format((float)($it->discount_value ?? 0), 2, ',', '.') }}</td>
                    <td class="right">R$ {{ number_format((float)($it->addition_value ?? 0), 2, ',', '.') }}</td>
                    <td class="right">R$ {{ number_format((float)$it->line_total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="total">
            @if($quote->discount_total > 0)
            <div class="total-row">
                <span>Desconto Global</span>
                <span style="color: #dc2626;">- R$ {{ number_format((float)$quote->discount_total, 2, ',', '.') }}</span>
            </div>
            @endif
            @if($quote->addition_total > 0)
            <div class="total-row">
                <span>Acréscimos</span>
                <span>R$ {{ number_format((float)$quote->addition_total, 2, ',', '.') }}</span>
            </div>
            @endif
            <div class="total-row total-final">
                <span>Total</span>
                <span>R$ {{ number_format((float)$quote->total_amount, 2, ',', '.') }}</span>
            </div>
        </div>

        <!-- Payment Methods -->
        @if($quote->payment_methods && count($quote->payment_methods) > 0)
        <div class="section">
            <div class="section-header">Formas de Pagamento Aceitas</div>
            <div class="section-content">
                <div class="payment-methods">
                    @foreach($quote->payment_methods as $method)
                        <div class="payment-item">
                            @switch($method)
                                @case('cash')
                                    <span class="payment-icon">💰</span>
                                    <span>À Vista (Dinheiro)</span>
                                    @break
                                @case('pix')
                                    <span class="payment-icon">📱</span>
                                    <span>PIX</span>
                                    @break
                                @case('boleto')
                                    <span class="payment-icon">📄</span>
                                    <span>Boleto Bancário</span>
                                    @break
                                @case('card')
                                    <span class="payment-icon">💳</span>
                                    <span>Cartão de Crédito</span>
                                    @if($quote->card_installments && $quote->card_installments > 1)
                                        <span class="muted">(até {{ $quote->card_installments }}x)</span>
                                    @endif
                                    @break
                            @endswitch
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        @php
            // Estimativa simples no orçamento (não oficial) se houver itens e NCM/CFOP
            $icmsE = 0.0; $pisE = 0.0; $cofinsE = 0.0;
            try {
                foreach ($quote->items as $it) {
                    $line = (float) ($it->line_total ?? ((float)$it->quantity * (float)$it->unit_price));
                    if ($line <= 0) { continue; }
                    $prod = $it->product_id ? \App\Models\Product::find($it->product_id) : null;
                    if (!$prod) { continue; }
                    $rate = \App\Models\TaxRate::where('tenant_id', $quote->tenant_id)
                        ->where('tipo_nota', 'produto')
                        ->where(function($q) use ($prod) {
                            $q->where('ncm', $prod->ncm)->orWhere('cfop', $prod->cfop);
                        })
                        ->where('ativo', 1)
                        ->orderByDesc('id')
                        ->first();
                    if ($rate) {
                        $icmsE += $line * (float)($rate->icms_aliquota ?? 0);
                        $pisE += $line * (float)($rate->pis_aliquota ?? 0);
                        $cofinsE += $line * (float)($rate->cofins_aliquota ?? 0);
                    }
                }
            } catch (\Throwable $e) {}
        @endphp
        <div class="muted" style="margin-top:16px; padding:12px; background:#f7fafc; border-radius:4px; text-align:center;">
            <strong>Estimativa de tributos (não oficial):</strong><br>
            ICMS: R$ {{ number_format($icmsE, 2, ',', '.') }} | 
            PIS: R$ {{ number_format($pisE, 2, ',', '.') }} | 
            COFINS: R$ {{ number_format($cofinsE, 2, ',', '.') }}
        </div>

        @if($quote->status === 'approved')
        <!-- Log de Aprovação -->
        <div class="section" style="border-left-color: #10b981; background: #f0fdf4;">
            <div class="section-header" style="color: #10b981; background: #f0fdf4;">
                ✅ Log de Aprovação
            </div>
            <div class="section-content">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                    <div>
                        <div style="font-weight: 600; color: #4a5568; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Status</div>
                        <div style="color: #10b981; font-weight: 700; font-size: 14px;">APROVADO</div>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #4a5568; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Aprovado em</div>
                        <div style="font-size: 14px; color: #2d3748;">{{ optional($quote->approved_at)->format('d/m/Y H:i') ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #4a5568; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Aprovado por</div>
                        <div style="font-size: 14px; color: #2d3748;">{{ $quote->approved_by ?? 'Usuário não informado' }}</div>
                    </div>
                </div>
                <div style="margin-top: 12px;">
                    <div style="font-weight: 600; color: #4a5568; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Motivo da Aprovação</div>
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; padding: 8px; color: #10b981; font-size: 14px;">{{ $quote->approval_reason ?? 'Motivo não informado' }}</div>
                </div>
            </div>
        </div>
        @endif

        @if($quote->status === 'canceled')
        <!-- Log de Cancelamento -->
        <div class="section" style="border-left-color: #dc2626; background: #fef2f2;">
            <div class="section-header" style="color: #dc2626; background: #fef2f2;">
                ⚠️ Log de Cancelamento
            </div>
            <div class="section-content">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                    <div>
                        <div style="font-weight: 600; color: #4a5568; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Status</div>
                        <div style="color: #dc2626; font-weight: 700; font-size: 14px;">CANCELADO</div>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #4a5568; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Cancelado em</div>
                        <div style="font-size: 14px; color: #2d3748;">{{ optional($quote->canceled_at)->format('d/m/Y H:i') ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #4a5568; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Cancelado por</div>
                        <div style="font-size: 14px; color: #2d3748;">{{ $quote->canceled_by ?? 'Usuário não informado' }}</div>
                    </div>
                </div>
                <div style="margin-top: 12px;">
                    <div style="font-weight: 600; color: #4a5568; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Motivo do Cancelamento</div>
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px; padding: 8px; color: #dc2626; font-size: 14px;">{{ $quote->cancel_reason ?? 'Motivo não informado' }}</div>
                </div>
            </div>
        </div>
        @endif

        @if($quote->notes)
        <div class="section">
            <div class="section-header">Observações</div>
            <div class="section-content">
                <p style="margin:0; line-height:1.6;">{{ $quote->notes }}</p>
            </div>
        </div>
        @endif

        <div class="footer">
            <div class="muted">
                @if($quote->status === 'canceled')
                    <div style="color: #dc2626; font-weight: 600; margin-bottom: 8px;">⚠️ ORÇAMENTO CANCELADO</div>
                    <div style="color: #dc2626;">Este orçamento foi cancelado e não possui mais validade legal.</div>
                @else
                    Tributos estimados — cálculo não oficial. Os valores definitivos constam no XML autorizado pela SEFAZ.
                @endif
            </div>
            <div class="no-print" style="margin-top:16px;">
                <button onclick="window.print()" style="background:#3182ce; color:white; border:none; padding:12px 24px; border-radius:6px; font-weight:600; cursor:pointer;">
                    🖨️ Imprimir Orçamento
                </button>
            </div>
            <div style="margin-top:12px;">
                <div class="muted">Orçamento emitido por QFiscal www.qfiscal.com.br</div>
            </div>
        </div>
    </div>
</body>
</html>


