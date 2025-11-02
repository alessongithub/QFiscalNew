<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório - {{ $tenant->fantasy_name ?? $tenant->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            color: #111; 
            margin: 0;
            padding: 16px;
            font-size: 12px;
        }
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            padding: 16px;
            background: white;
        }
        .header {
            border-bottom: 2px solid #059669;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .header-info {
            flex: 1;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 4px 0;
            color: #059669;
        }
        .company-details {
            font-size: 11px;
            color: #555;
            line-height: 1.5;
        }
        .logo {
            height: 48px;
            max-width: 150px;
            object-fit: contain;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin: 12px 0 8px 0;
            color: #333;
        }
        .period {
            color: #555;
            font-size: 11px;
            margin-top: 8px;
        }
        .generated-at {
            color: #888;
            font-size: 10px;
            margin-top: 4px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 16px;
            margin-bottom: 24px;
        }
        th { 
            background-color: #f3f4f6;
            border-bottom: 2px solid #059669;
            padding: 8px;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
            color: #333;
        }
        td { 
            border-bottom: 1px solid #ddd; 
            padding: 8px; 
            font-size: 11px;
        }
        .right { text-align: right; }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 24px;
            margin-bottom: 8px;
            color: #059669;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
        }
        .footer {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #888;
            text-align: center;
        }
        @media print { 
            .no-print { display: none; }
            body { padding: 0; }
            .container { padding: 16px; }
        }
        @page {
            margin: 1cm;
        }
    </style>
    <script>
        function printNow(){ 
            window.print(); 
        }
    </script>
</head>
<body onload="printNow()">
<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="header-info">
                <h1 class="company-name">{{ $tenant->fantasy_name ?? $tenant->name }}</h1>
                <div class="company-details">
                    @if($tenant->cnpj)
                        CNPJ: {{ $tenant->cnpj }}<br>
                    @endif
                    @if($tenant->address)
                        {{ $tenant->address }}@if($tenant->number), {{ $tenant->number }}@endif
                        @if($tenant->complement) - {{ $tenant->complement }}@endif<br>
                        @if($tenant->neighborhood){{ $tenant->neighborhood }} - @endif
                        {{ $tenant->city }}/{{ $tenant->state }}
                        @if($tenant->zip_code) - CEP: {{ $tenant->zip_code }}@endif
                    @endif
                </div>
            </div>
            @if(isset($logoUrl))
                <img src="{{ $logoUrl }}" class="logo" alt="Logo"/>
            @endif
        </div>
        <div class="report-title">Relatório Gerencial</div>
        <div class="period">Período: {{ $from->format('d/m/Y') }} a {{ $to->format('d/m/Y') }}</div>
        <div class="generated-at">Gerado em: {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    @if($includeReceivables && $receivablesDetailed && $receivablesDetailed->count())
        <div class="section-title">💰 A Receber</div>
        <table>
            <thead>
                <tr>
                    <th>Vencimento</th>
                    <th>Descrição</th>
                    <th>Cliente</th>
                    <th>Status</th>
                    <th class="right">Valor</th>
                </tr>
            </thead>
            <tbody>
            @foreach($receivablesDetailed as $r)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }}</td>
                    <td>{{ $r->description }}</td>
                    <td>{{ optional($r->client)->name ?? '—' }}</td>
                    <td>
                        @if($r->status === 'paid')
                            Pago
                        @elseif($r->status === 'open')
                            Em aberto
                        @else
                            {{ $r->status }}
                        @endif
                    </td>
                    <td class="right">R$ {{ number_format($r->amount, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($includePayables && $payablesDetailed && $payablesDetailed->count())
        <div class="section-title">💸 A Pagar</div>
        <table>
            <thead>
                <tr>
                    <th>Vencimento</th>
                    <th>Descrição</th>
                    <th>Fornecedor</th>
                    <th>Status</th>
                    <th class="right">Valor</th>
                </tr>
            </thead>
            <tbody>
            @foreach($payablesDetailed as $p)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($p->due_date)->format('d/m/Y') }}</td>
                    <td>{{ $p->description }}</td>
                    <td>{{ optional($p->supplier)->name ?? $p->supplier_name ?? '—' }}</td>
                    <td>
                        @if($p->status === 'paid')
                            Pago
                        @elseif($p->status === 'open')
                            Em aberto
                        @else
                            {{ $p->status }}
                        @endif
                    </td>
                    <td class="right">R$ {{ number_format($p->amount, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($includeClients && $clients && $clients->count())
        <div class="section-title">👥 Clientes</div>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Documento</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                </tr>
            </thead>
            <tbody>
            @foreach($clients as $c)
                <tr>
                    <td>{{ $c->name }}</td>
                    <td>{{ $c->cpf_cnpj }}</td>
                    <td>{{ $c->email ?? '—' }}</td>
                    <td>{{ $c->phone ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($includeProducts && $products && $products->count())
        <div class="section-title">📦 Produtos</div>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>SKU</th>
                    <th>Unidade</th>
                    <th class="right">Preço</th>
                </tr>
            </thead>
            <tbody>
            @foreach($products as $p)
                <tr>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->sku ?? '—' }}</td>
                    <td>{{ $p->unit ?? '—' }}</td>
                    <td class="right">R$ {{ number_format($p->price, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($includeSuppliers && $suppliers && $suppliers->count())
        <div class="section-title">🏭 Fornecedores</div>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Documento</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                </tr>
            </thead>
            <tbody>
            @foreach($suppliers as $s)
                <tr>
                    <td>{{ $s->name }}</td>
                    <td>{{ $s->cpf_cnpj ?? '—' }}</td>
                    <td>{{ $s->email ?? '—' }}</td>
                    <td>{{ $s->phone ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($includeCategories && $categories && $categories->count())
        <div class="section-title">🏷️ Categorias</div>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Categoria Pai</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @foreach($categories as $c)
                <tr>
                    <td>{{ $c->name }}</td>
                    <td>{{ optional($c->parent)->name ?? '—' }}</td>
                    <td>{{ $c->active ? 'Ativa' : 'Inativa' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if(($includeOrders ?? false) && $orders && $orders->count())
        <div class="section-title">🛒 Pedidos</div>
        <table>
            <thead>
                <tr>
                    <th>Nº</th>
                    <th>Cliente</th>
                    <th>Título</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th class="right">Valor</th>
                </tr>
            </thead>
            <tbody>
            @foreach($orders as $order)
                <tr>
                    <td>#{{ $order->number }}</td>
                    <td>{{ optional($order->client)->name ?? '—' }}</td>
                    <td>{{ $order->title ?? '—' }}</td>
                    <td>{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y') }}</td>
                    <td>
                        @if($order->status === 'open')
                            Em aberto
                        @elseif($order->status === 'fulfilled')
                            Finalizado
                        @else
                            {{ $order->status }}
                        @endif
                    </td>
                    <td class="right">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if(($includeServiceOrders ?? false) && $serviceOrders && $serviceOrders->count())
        <div class="section-title">🔧 Ordens de Serviço</div>
        <table>
            <thead>
                <tr>
                    <th>Nº</th>
                    <th>Cliente</th>
                    <th>Título</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th class="right">Valor</th>
                </tr>
            </thead>
            <tbody>
            @foreach($serviceOrders as $so)
                <tr>
                    <td>#{{ $so->number }}</td>
                    <td>{{ optional($so->client)->name ?? '—' }}</td>
                    <td>{{ $so->title ?? '—' }}</td>
                    <td>{{ \Carbon\Carbon::parse($so->created_at)->format('d/m/Y') }}</td>
                    <td>
                        @if($so->status === 'open')
                            Aberta
                        @elseif($so->status === 'in_progress')
                            Em andamento
                        @elseif($so->status === 'finished')
                            Finalizada
                        @else
                            {{ $so->status }}
                        @endif
                    </td>
                    <td class="right">R$ {{ number_format($so->total_amount, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if(($includeQuotes ?? false) && $quotes && $quotes->count())
        <div class="section-title">📋 Orçamentos</div>
        <table>
            <thead>
                <tr>
                    <th>Nº</th>
                    <th>Cliente</th>
                    <th>Título</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th class="right">Valor</th>
                </tr>
            </thead>
            <tbody>
            @foreach($quotes as $quote)
                <tr>
                    <td>#{{ $quote->number }}</td>
                    <td>{{ optional($quote->client)->name ?? '—' }}</td>
                    <td>{{ $quote->title ?? '—' }}</td>
                    <td>{{ \Carbon\Carbon::parse($quote->created_at)->format('d/m/Y') }}</td>
                    <td>
                        @if($quote->status === 'approved')
                            Aprovado
                        @elseif($quote->status === 'pending')
                            Pendente
                        @elseif($quote->status === 'rejected')
                            Rejeitado
                        @else
                            {{ $quote->status }}
                        @endif
                    </td>
                    <td class="right">R$ {{ number_format($quote->total_amount, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Este relatório foi gerado automaticamente pelo sistema QFiscal em {{ now()->format('d/m/Y H:i') }}</p>
        <p class="no-print">Para mais informações, acesse o painel administrativo.</p>
    </div>

    <div class="no-print" style="margin-top:16px; text-align:center;">
        <button onclick="window.print()" style="padding:8px 16px; background:#059669; color:white; border:none; border-radius:4px; cursor:pointer; font-size:14px;">
            Imprimir
        </button>
    </div>
</div>
</body>
</html>


