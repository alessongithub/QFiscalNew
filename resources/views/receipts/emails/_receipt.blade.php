<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo #{{ $receipt->number }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f0f9ff; border-left: 4px solid #0284c7; padding: 15px; margin-bottom: 20px;">
        <h1 style="color: #0284c7; margin: 0 0 10px 0; font-size: 24px;">Recibo</h1>
        <p style="margin: 0; color: #666;">Número: #{{ $receipt->number }}</p>
    </div>

    <div style="background-color: #ffffff; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 20px;">
        <p style="margin: 0 0 15px 0;">Prezado(a) <strong>{{ optional($receipt->client)->name ?? 'Cliente' }}</strong>,</p>
        
        <p style="margin: 0 0 15px 0;">
            Segue em anexo o recibo referente ao pagamento realizado.
        </p>

        <div style="background-color: #f9fafb; padding: 15px; margin: 15px 0; border-radius: 6px;">
            <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #111827;">Detalhes do Recibo:</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 5px 0; color: #666;">Número:</td>
                    <td style="padding: 5px 0; font-weight: bold;">#{{ $receipt->number }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; color: #666;">Descrição:</td>
                    <td style="padding: 5px 0;">{{ $receipt->description ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; color: #666;">Valor:</td>
                    <td style="padding: 5px 0; font-weight: bold; color: #059669;">R$ {{ number_format($receipt->amount, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; color: #666;">Data de Emissão:</td>
                    <td style="padding: 5px 0;">{{ optional($receipt->issue_date)->format('d/m/Y') ?? '—' }}</td>
                </tr>
                @if($receipt->notes)
                <tr>
                    <td style="padding: 5px 0; color: #666;">Observações:</td>
                    <td style="padding: 5px 0;">{{ $receipt->notes }}</td>
                </tr>
                @endif
            </table>
        </div>

        <p style="margin: 20px 0 0 0;">
            Este recibo comprova o recebimento do valor mencionado acima. Em caso de dúvidas, entre em contato conosco através dos nossos canais de atendimento.
        </p>

        <p style="margin: 15px 0 0 0;">
            Obrigado pela sua preferência!
        </p>
    </div>

    <div style="background-color: #f9fafb; padding: 15px; border-top: 1px solid #e5e7eb; margin-top: 20px; text-align: center; color: #6b7280; font-size: 12px;">
        <p style="margin: 0;">
            <strong>{{ $receipt->tenant->name ?? 'Empresa' }}</strong><br>
            @if($receipt->tenant->cnpj)
            CNPJ: {{ $receipt->tenant->cnpj }}<br>
            @endif
            @if($receipt->tenant->phone)
            Tel: {{ $receipt->tenant->phone }}<br>
            @endif
            @if($receipt->tenant->email)
            Email: {{ $receipt->tenant->email }}
            @endif
        </p>
        <p style="margin: 10px 0 0 0; font-size: 11px;">
            Este é um email automático, por favor não responda.
        </p>
    </div>
</body>
</html>






