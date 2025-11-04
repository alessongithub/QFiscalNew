<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelamento de OS #{{ $serviceOrder->number }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin-bottom: 20px;">
        <h1 style="color: #dc2626; margin: 0 0 10px 0; font-size: 24px;">Cancelamento de Ordem de Serviço</h1>
        <p style="margin: 0; color: #666;">OS #{{ $serviceOrder->number }}</p>
    </div>

    <div style="background-color: #ffffff; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 20px;">
        <p style="margin: 0 0 15px 0;">Prezado(a) <strong>{{ $client->name ?? 'Cliente' }}</strong>,</p>
        
        <p style="margin: 0 0 15px 0;">
            Informamos que a Ordem de Serviço <strong>#{{ $serviceOrder->number }}</strong> foi cancelada pela nossa empresa.
        </p>

        @if($serviceOrder->cancellation && $serviceOrder->cancellation->cancellation_reason)
        <div style="background-color: #fff7ed; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0;">
            <h3 style="margin: 0 0 10px 0; color: #92400e; font-size: 16px;">Motivo do Cancelamento:</h3>
            <p style="margin: 0; color: #78350f;">{{ $serviceOrder->cancellation->cancellation_reason }}</p>
        </div>
        @endif

        <div style="background-color: #f9fafb; padding: 15px; margin: 15px 0; border-radius: 6px;">
            <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #111827;">Detalhes da OS:</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 5px 0; color: #666;">OS:</td>
                    <td style="padding: 5px 0; font-weight: bold;">#{{ $serviceOrder->number }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; color: #666;">Título:</td>
                    <td style="padding: 5px 0;">{{ $serviceOrder->title ?? '—' }}</td>
                </tr>
                @if($serviceOrder->total_amount > 0)
                <tr>
                    <td style="padding: 5px 0; color: #666;">Valor:</td>
                    <td style="padding: 5px 0; font-weight: bold; color: #dc2626;">R$ {{ number_format($serviceOrder->total_amount, 2, ',', '.') }}</td>
                </tr>
                @endif
                @if($serviceOrder->cancelled_at)
                <tr>
                    <td style="padding: 5px 0; color: #666;">Data do Cancelamento:</td>
                    <td style="padding: 5px 0;">{{ $serviceOrder->cancelled_at->format('d/m/Y H:i') }}</td>
                </tr>
                @endif
            </table>
        </div>

        @if($serviceOrder->total_amount > 0)
        <div style="background-color: #ecfdf5; border-left: 4px solid #10b981; padding: 15px; margin: 15px 0;">
            <h3 style="margin: 0 0 10px 0; color: #047857; font-size: 16px;">Reembolso:</h3>
            <p style="margin: 0; color: #065f46;">
                O valor de <strong>R$ {{ number_format($serviceOrder->total_amount, 2, ',', '.') }}</strong> será devolvido através do mesmo método de pagamento utilizado na compra.
                O processamento do reembolso pode levar de 5 a 10 dias úteis para ser concluído.
            </p>
        </div>
        @endif

        @if($serviceOrder->cancellation && $serviceOrder->cancellation->notes)
        <div style="background-color: #f9fafb; padding: 15px; margin: 15px 0; border-radius: 6px; border: 1px solid #e5e7eb;">
            <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #111827;">Observações Adicionais:</h3>
            <p style="margin: 0; color: #4b5563;">{{ $serviceOrder->cancellation->notes }}</p>
        </div>
        @endif

        <p style="margin: 20px 0 0 0;">
            Caso tenha alguma dúvida sobre este cancelamento, entre em contato conosco através dos nossos canais de atendimento.
        </p>

        <p style="margin: 15px 0 0 0;">
            Pedimos desculpas pelo inconveniente e agradecemos sua compreensão.
        </p>
    </div>

    <div style="background-color: #f9fafb; padding: 15px; border-top: 1px solid #e5e7eb; margin-top: 20px; text-align: center; color: #6b7280; font-size: 12px;">
        <p style="margin: 0;">
            <strong>{{ $serviceOrder->tenant->name ?? 'Empresa' }}</strong><br>
            @if($serviceOrder->tenant->cnpj)
            CNPJ: {{ $serviceOrder->tenant->cnpj }}<br>
            @endif
            @if($serviceOrder->tenant->phone)
            Tel: {{ $serviceOrder->tenant->phone }}<br>
            @endif
            @if($serviceOrder->tenant->email)
            Email: {{ $serviceOrder->tenant->email }}
            @endif
        </p>
        <p style="margin: 10px 0 0 0; font-size: 11px;">
            Este é um email automático, por favor não responda.
        </p>
    </div>
</body>
</html>






