<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Pedido #{{ $order->number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #000;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        
        .logo {
            max-height: 60px;
            max-width: 200px;
            margin-bottom: 10px;
        }
        
        .company-name {
            text-align: center;
            margin-bottom: 15px;
            font-size: 18px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        
        .header p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .section h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 30%;
            padding: 3px 0;
        }
        
        .info-value {
            display: table-cell;
            padding: 3px 0;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .table th,
        .table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        .table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        .table .text-right {
            text-align: right;
        }
        
        .table .text-center {
            text-align: center;
        }
        
        .summary-table {
            width: 50%;
            margin-left: auto;
        }
        
        .summary-table td {
            padding: 5px 10px;
        }
        
        .total-row {
            border-top: 2px solid #000;
            font-weight: bold;
            background-color: #f5f5f5;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        
        @page {
            margin: 1cm;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($order->tenant->logo_path && file_exists(public_path('storage/' . $order->tenant->logo_path)))
            <div class="company-name">
                <strong>{{ $order->tenant->name ?? 'EMPRESA' }}</strong>
            </div>
        @endif
        <h1>PEDIDO #{{ $order->number }}</h1>
        <p><strong>Data:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
        <p><strong>Status:</strong> {{ 
            $order->status === 'open' ? 'Aberto' : 
            ($order->status === 'fulfilled' ? 'Finalizado' : 
            ($order->status === 'canceled' ? 'Cancelado' : 
            ucfirst($order->status))) 
        }}</p>
        @if($order->title)
            <p><strong>Título:</strong> {{ $order->title }}</p>
        @endif
    </div>

    <div class="section">
        <h3>Informações do Cliente</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nome:</div>
                <div class="info-value">{{ $order->client->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Documento:</div>
                <div class="info-value">{{ $order->client->document ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $order->client->email ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Telefone:</div>
                <div class="info-value">{{ $order->client->phone ?? 'N/A' }}</div>
            </div>
            @if($order->client->address)
            <div class="info-row">
                <div class="info-label">Endereço:</div>
                <div class="info-value">{{ $order->client->address }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="section">
        <h3>Itens do Pedido</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-center">Qtd</th>
                    <th class="text-center">UN</th>
                    <th class="text-right">V.Unit</th>
                    <th class="text-right">Desc.</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td class="text-center">{{ number_format($item->quantity, 3, ',', '.') }}</td>
                    <td class="text-center">{{ $item->unit }}</td>
                    <td class="text-right">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format((float)($item->discount_value ?? 0), 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format((float)$item->line_total, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Resumo Financeiro</h3>
        <table class="table summary-table">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">R$ {{ number_format($order->items->sum('line_total'), 2, ',', '.') }}</td>
            </tr>
            @php
                $totalDiscountItems = $order->items->sum('discount_value');
            @endphp
            @if($totalDiscountItems > 0)
            <tr>
                <td>Desconto por Itens:</td>
                <td class="text-right">- R$ {{ number_format($totalDiscountItems, 2, ',', '.') }}</td>
            </tr>
            @endif
            @if($order->discount_total > 0)
            <tr>
                <td>Desconto Global:</td>
                <td class="text-right">- R$ {{ number_format($order->discount_total, 2, ',', '.') }}</td>
            </tr>
            @endif
            @if($order->addition_total > 0)
            <tr>
                <td>Acréscimo:</td>
                <td class="text-right">+ R$ {{ number_format($order->addition_total, 2, ',', '.') }}</td>
            </tr>
            @endif
            @if($order->freight_cost > 0)
            <tr>
                <td>Frete:</td>
                <td class="text-right">R$ {{ number_format($order->freight_cost, 2, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>TOTAL:</td>
                <td class="text-right">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    @if($order->carrier || $order->freight_mode)
    <div class="section">
        <h3>Informações de Frete</h3>
        <div class="info-grid">
            @if($order->carrier)
            <div class="info-row">
                <div class="info-label">Transportadora:</div>
                <div class="info-value">{{ $order->carrier->name }}</div>
            </div>
            @endif
            @if($order->freight_mode)
            <div class="info-row">
                <div class="info-label">Modalidade:</div>
                <div class="info-value">
                    @switch($order->freight_mode)
                        @case(0) CIF (Emitente) @break
                        @case(1) FOB (Destinatário) @break
                        @case(2) Sem Frete @break
                        @default {{ $order->freight_mode }} @break
                    @endswitch
                </div>
            </div>
            @endif
            @if($order->freight_payer)
            <div class="info-row">
                <div class="info-label">Pagador:</div>
                <div class="info-value">{{ $order->freight_payer }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    @if($order->additional_info)
    <div class="section">
        <h3>Informações Adicionais</h3>
        <p>{{ $order->additional_info }}</p>
    </div>
    @endif

    @if($order->receivables->count() > 0)
    <div class="section">
        <h3>Forma de Pagamento</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Parcela</th>
                    <th class="text-right">Valor</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Vencimento</th>
                    <th class="text-center">Método</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->receivables as $receivable)
                <tr>
                    <td>{{ $receivable->installment_number ?? '1' }}/{{ $receivable->total_installments ?? '1' }}</td>
                    <td class="text-right">R$ {{ number_format($receivable->amount, 2, ',', '.') }}</td>
                    <td class="text-center">{{ ucfirst($receivable->status) }}</td>
                    <td class="text-center">{{ $receivable->due_date ? $receivable->due_date->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">
                        @switch($receivable->payment_method)
                            @case('cash') Dinheiro @break
                            @case('pix') PIX @break
                            @case('card') Cartão @break
                            @case('debit') Débito @break
                            @case('boleto') Boleto @break
                            @case('transfer') Transferência @break
                            @case('check') Cheque @break
                            @default {{ ucfirst($receivable->payment_method) }} @break
                        @endswitch
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Documento gerado em {{ now()->format('d/m/Y H:i') }} - Sistema QFiscal</p>
    </div>
</body>
</html>
