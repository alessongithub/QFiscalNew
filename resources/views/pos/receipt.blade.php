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
    @php $t = auth()->user()->tenant; @endphp
    <div style="text-align:center; margin-bottom:12px; border-bottom:1px solid #ddd; padding-bottom:8px;">
        <div style="font-size:16px; font-weight:bold; margin-bottom:4px;">{{ $t->fantasy_name ?: $t->name }}</div>
        @if($t->cnpj)
        <div class="muted" style="font-size:11px;">CNPJ: {{ $t->cnpj }}</div>
        @endif
        <div class="muted" style="font-size:11px; margin-top:2px;">
            {{ ($t->address ?? '') }}{{ $t->number ? ', '.$t->number : '' }}{{ $t->neighborhood ? ' - '.$t->neighborhood : '' }}
            {{ ($t->city ?? '') }} {{ $t->state ? '/'.$t->state : '' }} {{ $t->zip_code ? ' CEP '.$t->zip_code : '' }}
        </div>
        @if(!empty($t->phone))
        <div class="muted" style="font-size:11px; margin-top:2px;">Fone: {{ $t->phone }}</div>
        @endif
    </div>
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


