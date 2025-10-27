<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <title>Recibo PDV #{{ $order->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; color:#111; }
        .container { max-width: 480px; margin: 0 auto; padding: 12px; }
        h1 { font-size: 16px; margin: 0 0 8px; }
        table { width:100%; border-collapse: collapse; }
        td, th { padding: 4px 0; font-size: 12px; }
        .right { text-align: right; }
        .muted { color:#666; }
        @media print { .no-print { display:none; } }
    </style>
    <script>function printNow(){ window.print(); }</script>
 </head>
 <body onload="printNow()">
 <div class="container">
    <h1>Recibo de Venda - PDV</h1>
    <div class="muted">Pedido #{{ $order->id }} | {{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
    <div class="muted">Cliente: {{ optional($order->client)->name ?? '—' }}</div>
    <hr>
    <table>
        <thead><tr><th>Item</th><th class="right">Qtd</th><th class="right">Unit</th><th class="right">Total</th></tr></thead>
        <tbody>
        @foreach($items as $it)
            <tr>
                <td>{{ $it->name }}</td>
                <td class="right">{{ number_format($it->quantity, 3, ',', '.') }}</td>
                <td class="right">R$ {{ number_format($it->unit_price, 2, ',', '.') }}</td>
                <td class="right">R$ {{ number_format($it->line_total, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <hr>
    <div class="right">Total: <strong>R$ {{ number_format($order->total_amount, 2, ',', '.') }}</strong></div>
    <div class="no-print" style="margin-top:10px;"><button onclick="window.print()">Imprimir</button></div>
 </div>
 </body>
 </html>


