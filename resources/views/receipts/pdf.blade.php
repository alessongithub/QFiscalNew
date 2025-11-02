<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo #{{ $receipt->number }}</title>
    <style>
        @page { 
            size: A4; 
            margin: 15mm; 
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body { 
            font-family: Arial, sans-serif;
            color: #000;
            background: #fff;
            font-size: 11pt;
            line-height: 1.4;
        }
        .receipt-container {
            max-width: 100%;
            margin: 0 auto;
        }
        .receipt-header {
            border: 2px solid #000;
            padding: 15px;
            text-align: center;
            margin-bottom: 15px;
            background: #fff;
        }
        .receipt-company-header {
            text-align: center;
        }
        .receipt-company-header > div {
            margin-bottom: 3px;
            font-size: 10pt;
            line-height: 1.3;
        }
        .receipt-title-section {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px 0;
            border-bottom: 2px solid #000;
        }
        .receipt-title {
            font-size: 28pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .receipt-subtitle {
            font-size: 14pt;
            font-weight: normal;
        }
        .receipt-body {
            padding: 0;
        }
        .receipt-section {
            border: 1px solid #000;
            margin-bottom: 15px;
            padding: 12px;
            background: #fff;
            page-break-inside: avoid;
        }
        .receipt-section-title {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #000;
        }
        .receipt-info {
            width: 100%;
        }
        .receipt-info-item {
            display: table;
            width: 100%;
            padding: 6px 0;
            border-bottom: 1px dotted #666;
        }
        .receipt-info-item:last-child {
            border-bottom: none;
        }
        .receipt-label {
            display: table-cell;
            width: 40%;
            font-weight: normal;
            vertical-align: top;
        }
        .receipt-value {
            display: table-cell;
            font-weight: bold;
            text-align: right;
            vertical-align: top;
        }
        .receipt-amount {
            font-size: 16pt;
            font-weight: bold;
        }
        .receipt-footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #000;
            display: table;
            width: 100%;
        }
        .receipt-signature {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .receipt-signature-line {
            border-bottom: 2px solid #000;
            width: 200px;
            margin: 0 auto 8px;
            height: 50px;
        }
        .receipt-signature-text {
            font-size: 9pt;
            font-weight: normal;
        }
        .receipt-disclaimer {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
            font-size: 9pt;
        }
        .canceled-section {
            border: 2px solid #000;
            background: #f0f0f0;
        }
        .canceled-title {
            border-bottom: 2px solid #000;
        }
        .canceled-value {
            font-weight: bold;
        }
        .receipt-notes {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #666;
            font-size: 10pt;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .receipt-container {
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header com informações da empresa -->
        <div class="receipt-header">
            <div class="receipt-company-header">
                <div class="receipt-company-name">{{ optional($receipt->tenant)->name ?? 'Empresa' }}</div>
                @if($receipt->tenant->address)
                <div>{{ $receipt->tenant->address }}</div>
                @endif
                @if($receipt->tenant->cnpj)
                <div>CNPJ: {{ $receipt->tenant->cnpj }}</div>
                @endif
                @if($receipt->tenant->phone)
                <div>Tel: {{ $receipt->tenant->phone }}</div>
                @endif
                @if($receipt->tenant->email)
                <div>Email: {{ $receipt->tenant->email }}</div>
                @endif
            </div>
        </div>

        <!-- Título do Recibo -->
        <div class="receipt-title-section">
            <div class="receipt-title">RECIBO</div>
            <div class="receipt-subtitle">Nº {{ $receipt->number }}</div>
        </div>

        <!-- Body -->
        <div class="receipt-body">
            <!-- Informações Principais -->
            <div class="receipt-section">
                <div class="receipt-section-title">INFORMAÇÕES DO PAGAMENTO</div>
                <div class="receipt-info">
                    <div class="receipt-info-item">
                        <span class="receipt-label">Recebemos de:</span>
                        <span class="receipt-value">{{ optional($receipt->client)->name ?? '—' }}</span>
                    </div>
                    <div class="receipt-info-item">
                        <span class="receipt-label">Valor:</span>
                        <span class="receipt-value receipt-amount">R$ {{ number_format($receipt->amount, 2, ',', '.') }}</span>
                    </div>
                    <div class="receipt-info-item">
                        <span class="receipt-label">Referente a:</span>
                        <span class="receipt-value">{{ $receipt->description ?? '—' }}</span>
                    </div>
                    <div class="receipt-info-item">
                        <span class="receipt-label">Data de Emissão:</span>
                        <span class="receipt-value">{{ optional($receipt->issue_date)->format('d/m/Y') ?? '—' }}</span>
                    </div>
                </div>
            </div>

            @if($receipt->status === 'canceled')
            <!-- Log de Cancelamento -->
            <div class="receipt-section canceled-section">
                <div class="receipt-section-title canceled-title">CANCELAMENTO</div>
                <div class="receipt-info">
                    <div class="receipt-info-item">
                        <span class="receipt-label">Status:</span>
                        <span class="receipt-value canceled-value">CANCELADO</span>
                    </div>
                    <div class="receipt-info-item">
                        <span class="receipt-label">Cancelado em:</span>
                        <span class="receipt-value">{{ optional($receipt->canceled_at)->format('d/m/Y H:i') ?? 'N/A' }}</span>
                    </div>
                    <div class="receipt-info-item">
                        <span class="receipt-label">Cancelado por:</span>
                        <span class="receipt-value">{{ $receipt->canceled_by ?? 'Usuário não informado' }}</span>
                    </div>
                    <div class="receipt-info-item">
                        <span class="receipt-label">Motivo:</span>
                        <span class="receipt-value canceled-value">{{ $receipt->cancel_reason ?? 'Motivo não informado' }}</span>
                    </div>
                </div>
            </div>
            @endif

            @if($receipt->notes)
            <!-- Observações -->
            <div class="receipt-section">
                <div class="receipt-section-title">OBSERVAÇÕES</div>
                <div class="receipt-notes">
                    <div class="receipt-value">{{ $receipt->notes }}</div>
                </div>
            </div>
            @endif

            <!-- Footer -->
            <div class="receipt-footer">
                <div class="receipt-signature">
                    <div class="receipt-signature-line"></div>
                    <div class="receipt-signature-text">Assinatura / Carimbo</div>
                </div>
                <div class="receipt-disclaimer">
                    <div style="font-size: 8pt;">
                        @if($receipt->status === 'canceled')
                            <strong>RECIBO CANCELADO</strong><br>
                            Este recibo foi cancelado e não possui mais validade legal.
                        @else
                            Válido sem valor fiscal
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

