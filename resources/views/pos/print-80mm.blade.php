<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <title>PDV #{{ $order->id }} - 80mm</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .ticket { width: 280px; margin: 0 auto; }
        .center { text-align:center; }
        .right { text-align:right; }
        table { width:100%; border-collapse: collapse; }
        td { font-size: 12px; padding: 2px 0; }
        hr { border: 0; border-top: 1px dashed #333; margin: 6px 0; }
        @media print { .no-print { display:none; } }
    </style>
    <script>function printNow(){ window.print(); }</script>
</head>
<body onload="printNow()">
    <div class="ticket">
        <div class="center">
            <img src="http://localhost:8000/logo_transp.png" alt="logo" style="height:40px"><br>
            <strong>PDV #{{ $order->id }}</strong><br>
            {{ optional($order->created_at)->format('d/m/Y H:i') }}
        </div>
        <hr>
        <div>Cliente: {{ optional($order->client)->name ?? '—' }}</div>
        <hr>
        <table>
            @foreach($items as $it)
            <tr><td colspan="2">{{ $it->name }}</td></tr>
            <tr>
                <td>{{ number_format($it->quantity,3,',','.') }} x R$ {{ number_format($it->unit_price,2,',','.') }}</td>
                <td class="right">R$ {{ number_format($it->line_total,2,',','.') }}</td>
            </tr>
            @endforeach
        </table>
        <hr>
        <div class="right"><strong>Total: R$ {{ number_format($order->total_amount,2,',','.') }}</strong></div>
        <div class="center">Obrigado pela preferência!</div>
        <div class="no-print center" style="margin-top:8px;"><button onclick="window.print()">Imprimir</button></div>
    </div>
</body>
</html>


