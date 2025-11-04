<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f8fafc; color: #111827; }
        .card { max-width: 640px; margin: 24px auto; background: #ffffff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; }
        .title { margin: 0; font-size: 18px; }
        .content { padding: 24px; }
        .row { margin-bottom: 12px; }
        .label { font-size: 12px; color: #64748b; display: block; }
        .value { font-size: 14px; color: #0f172a; font-weight: 600; }
        .footer { padding: 16px 24px; border-top: 1px solid #f1f5f9; font-size: 12px; color: #64748b; }
        .money { font-variant-numeric: tabular-nums; }
    </style>
    <title>Solicitação de Transferência</title>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1 class="title">Solicitação de Transferência</h1>
        </div>
        <div class="content">
            <div class="row">
                <span class="label">Tenant</span>
                <span class="value">{{ optional($balance->tenant)->name }} (ID: {{ $balance->tenant_id }})</span>
            </div>
            <div class="row">
                <span class="label">Recebível</span>
                <span class="value">#{{ $balance->receivable_id }}</span>
            </div>
            <div class="row">
                <span class="label">Valores</span>
                <span class="value money">Bruto: R$ {{ number_format($balance->gross_amount, 2, ',', '.') }} &nbsp;·&nbsp; Taxa MP: R$ {{ number_format($balance->mp_fee_amount, 2, ',', '.') }} &nbsp;·&nbsp; Taxa Plataforma (1%): R$ {{ number_format($balance->platform_fee_amount, 2, ',', '.') }} &nbsp;·&nbsp; Líquido: R$ {{ number_format($balance->net_amount, 2, ',', '.') }}</span>
            </div>
            @if($transferSettings)
            <div class="row">
                <span class="label">Destino preferido</span>
                @if($transferSettings->pix_key)
                    <span class="value">PIX: {{ $transferSettings->pix_key }}</span>
                @elseif($transferSettings->account)
                    <span class="value">Conta: {{ $transferSettings->account }}</span>
                @else
                    <span class="value">Não informado</span>
                @endif
            </div>
            @endif
            <div class="row">
                <span class="label">Solicitado em</span>
                <span class="value">{{ optional($balance->requested_at)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>
        <div class="footer">
            Você está recebendo este e-mail porque uma solicitação de transferência foi feita pelo tenant acima.
        </div>
    </div>
</body>
<!-- Variables provided by Mailable: $balance, $transferSettings -->
</html>


