<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <title>Pedido PDV #{{ $order->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; color:#111; }
        .container { max-width: 900px; margin: 0 auto; padding: 16px; }
        h1 { margin: 0; }
        table { width:100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; font-size: 13px; }
        .right { text-align: right; }
        .muted { color:#666; font-size: 12px; }
        @media print { .no-print { display:none; } }
    </style>
    <script>function printNow(){ window.print(); }</script>
</head>
<body onload="printNow()">
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:start;">
            <div>
                <h1>Pedido de Venda - PDV</h1>
                <div class="muted">#{{ $order->id }} | {{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                <div class="muted">Cliente: {{ optional($order->client)->name ?? '—' }}</div>
            </div>
            <div><img src="http://localhost:8000/logo_transp.png" alt="logo" style="height:50px;"></div>
        </div>

        <table>
            <thead>
                <tr><th>Produto</th><th class="right">Qtd</th><th class="right">UN</th><th class="right">Vlr Unit</th><th class="right">Total</th></tr>
            </thead>
            <tbody>
                @foreach($items as $it)
                <tr>
                    <td>{{ $it->name }}</td>
                    <td class="right">{{ number_format($it->quantity, 3, ',', '.') }}</td>
                    <td class="right">{{ $it->unit }}</td>
                    <td class="right">R$ {{ number_format($it->unit_price, 2, ',', '.') }}</td>
                    <td class="right">R$ {{ number_format($it->line_total, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="right" style="margin-top:10px; font-size:16px;">Total: <strong>R$ {{ number_format($order->total_amount, 2, ',', '.') }}</strong></div>

        <div class="no-print" style="margin-top:12px;"><button onclick="window.print()">Imprimir</button></div>
    </div>
</body>
</html>


