<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <title>PDV #{{ $order->id }} - 80mm</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Courier New', monospace, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            background: white;
            padding: 10px 5px;
        }
        .ticket { 
            width: 280px; 
            margin: 0 auto;
            max-width: 100%;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .left { text-align: left; }
        .separator {
            border: 0;
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .separator-bold {
            border: 0;
            border-top: 2px solid #000;
            margin: 10px 0;
        }
        .header {
            margin-bottom: 8px;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        .header-info {
            font-size: 10px;
            line-height: 1.4;
            margin-bottom: 2px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 6px 0 4px 0;
            text-transform: uppercase;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }
        .items-table td {
            padding: 3px 0;
            font-size: 11px;
            vertical-align: top;
        }
        .item-name {
            font-weight: bold;
            padding-bottom: 2px;
        }
        .item-details {
            font-size: 10px;
            color: #333;
            padding-left: 2px;
        }
        .item-total {
            font-weight: bold;
            font-size: 11px;
        }
        .totals {
            margin: 8px 0;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 11px;
        }
        .total-final {
            font-size: 14px;
            font-weight: bold;
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px dashed #000;
        }
        .payment-info {
            margin: 6px 0;
            font-size: 10px;
            line-height: 1.5;
        }
        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 11px;
        }
        .footer-message {
            font-weight: bold;
            margin: 6px 0;
            font-size: 12px;
        }
        .footer-text {
            font-size: 10px;
            line-height: 1.4;
            margin-top: 4px;
        }
        .order-info {
            background: #f0f0f0;
            padding: 6px;
            margin: 6px 0;
            border-left: 3px solid #000;
            font-size: 10px;
        }
        .no-print { display: none; }
        @media print {
            body { padding: 5px; }
            .no-print { display: none !important; }
        }
    </style>
    <script>function printNow(){ window.print(); }</script>
</head>
<body onload="printNow()">
    <div class="ticket">
        @php 
            $t = auth()->user()->tenant;
            $footer = \App\Models\Setting::get('print.footer', '');
            // Buscar todos os recebíveis do pedido (pagados e em aberto) para mostrar pagamento misto completo
            $receivables = \App\Models\Receivable::where('order_id', $order->id)
                ->whereIn('status', ['paid', 'open', 'partial'])
                ->orderBy('due_date')
                ->get();
            $totalPaid = $receivables->where('status', 'paid')->sum('amount');
            $paymentMethodLabels = [
                'cash' => 'Dinheiro',
                'card' => 'Cartão',
                'pix' => 'PIX',
                'boleto' => 'Boleto',
                'transfer' => 'Transferência'
            ];
        @endphp
        
        <!-- Cabeçalho -->
        <div class="header center">
            <div class="separator-bold"></div>
            <div class="header-title">{{ $t->fantasy_name ?: $t->name }}</div>
            <div class="header-info">CNPJ: {{ $t->cnpj ?? '—' }}</div>
            @if($t->address || $t->number)
                <div class="header-info">{{ ($t->address ?? '') }}{{ $t->number ? ', '.$t->number : '' }}{{ $t->neighborhood ? ' - '.$t->neighborhood : '' }}</div>
            @endif
            @if($t->city || $t->state)
                <div class="header-info">{{ ($t->city ?? '') }}{{ $t->state ? '/'.$t->state : '' }}{{ $t->zip_code ? ' - CEP '.$t->zip_code : '' }}</div>
            @endif
            @if(!empty($t->phone))
                <div class="header-info">Tel: {{ $t->phone }}</div>
            @endif
            <div class="separator-bold"></div>
        </div>

        <!-- Informações do Cupom -->
        <div class="section-title center">CUPOM FISCAL</div>
        <div class="order-info">
            <div><strong>PDV #{{ $order->number ?? $order->id }}</strong></div>
            <div>{{ optional($order->created_at)->format('d/m/Y H:i:s') }}</div>
        </div>

        <div class="separator"></div>

        <!-- Cliente -->
        @if($order->client)
            <div class="left">
                <strong>Cliente:</strong> {{ $order->client->name }}
            </div>
            <div class="separator"></div>
        @endif

        <!-- Itens -->
        <div class="section-title">Itens</div>
        <table class="items-table">
            @foreach($items as $it)
                <tr>
                    <td colspan="2" class="item-name">{{ $it->name }}</td>
                </tr>
                <tr>
                    <td class="item-details">
                        {{ number_format($it->quantity, 3, ',', '.') }} x R$ {{ number_format($it->unit_price, 2, ',', '.') }}
                    </td>
                    <td class="right item-total">R$ {{ number_format($it->line_total, 2, ',', '.') }}</td>
                </tr>
                @if(!$loop->last)
                    <tr><td colspan="2" style="padding: 2px 0;"></td></tr>
                @endif
            @endforeach
        </table>

        <div class="separator"></div>

        <!-- Totais -->
        <div class="totals">
            @php
                $subtotal = $items->sum('line_total');
                $discount = (float)($order->discount_total ?? 0);
                $addition = (float)($order->addition_total ?? 0);
                $freight = (float)($order->freight_cost ?? 0);
            @endphp
            
            @if($discount > 0)
                <div class="total-line">
                    <span>Subtotal:</span>
                    <span>R$ {{ number_format($subtotal, 2, ',', '.') }}</span>
                </div>
                <div class="total-line">
                    <span>Desconto:</span>
                    <span>- R$ {{ number_format($discount, 2, ',', '.') }}</span>
                </div>
            @endif
            
            @if($freight > 0)
                <div class="total-line">
                    <span>Frete:</span>
                    <span>R$ {{ number_format($freight, 2, ',', '.') }}</span>
                </div>
            @endif
            
            @if($addition > 0)
                <div class="total-line">
                    <span>Acréscimo:</span>
                    <span>R$ {{ number_format($addition, 2, ',', '.') }}</span>
                </div>
            @endif

            <div class="total-line total-final">
                <span>TOTAL:</span>
                <span>R$ {{ number_format($order->total_amount, 2, ',', '.') }}</span>
            </div>
        </div>

        <div class="separator"></div>

        <!-- Informações de Pagamento -->
        @if($receivables->count() > 0)
            <div class="section-title">Forma de Pagamento</div>
            <div class="payment-info">
                @php
                    // Agrupar por método de pagamento para mostrar de forma mais clara
                    $groupedPayments = [];
                    $cardInstallments = [];
                    foreach($receivables as $rec) {
                        $method = $rec->payment_method ?? 'cash';
                        $methodLabel = $paymentMethodLabels[$method] ?? 'Dinheiro';
                        
                        // Detectar parcelas de cartão
                        if ($method === 'card' && stripos($rec->description ?? '', 'Parcela') !== false) {
                            // Extrair informação de parcela (ex: "Parcela 1/3")
                            if (preg_match('/Parcela\s+(\d+)\/(\d+)/i', $rec->description ?? '', $matches)) {
                                $currentParcel = (int)$matches[1];
                                $totalParcels = (int)$matches[2];
                                if (!isset($cardInstallments[$totalParcels])) {
                                    $cardInstallments[$totalParcels] = [
                                        'total' => 0,
                                        'parcel_value' => $rec->amount,
                                        'count' => 0
                                    ];
                                }
                                $cardInstallments[$totalParcels]['total'] += $rec->amount;
                                $cardInstallments[$totalParcels]['count']++;
                            }
                        } else {
                            if (!isset($groupedPayments[$methodLabel])) {
                                $groupedPayments[$methodLabel] = 0;
                            }
                            $groupedPayments[$methodLabel] += $rec->amount;
                        }
                    }
                @endphp
                @foreach($groupedPayments as $methodLabel => $total)
                    <div class="total-line">
                        <span>{{ $methodLabel }}:</span>
                        <span>R$ {{ number_format($total, 2, ',', '.') }}</span>
                    </div>
                @endforeach
                @foreach($cardInstallments as $totalParcels => $installmentData)
                    <div class="total-line">
                        <span>Cartão ({{ $installmentData['count'] }}x):</span>
                        <span>R$ {{ number_format($installmentData['total'], 2, ',', '.') }}</span>
                    </div>
                    <div class="payment-info" style="font-size: 9px; padding-left: 8px; margin-top: 2px;">
                        {{ $totalParcels }}x de R$ {{ number_format($installmentData['parcel_value'], 2, ',', '.') }}
                    </div>
                @endforeach
            </div>
            <div class="separator"></div>
        @endif

        <!-- Rodapé -->
        <div class="footer">
            <div class="footer-message">Obrigado pela preferência!</div>
            <div class="footer-message">Volte sempre!</div>
            @if($footer)
                <div class="separator"></div>
                <div class="footer-text">{{ $footer }}</div>
            @endif
        </div>

        <div class="no-print center" style="margin-top: 12px;">
            <button onclick="window.print()" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
                🖨️ Imprimir
            </button>
        </div>
    </div>
</body>
</html>


