<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <title>Relatório</title>
    <style>
        body { font-family: Arial, sans-serif; color:#111; }
        .container { max-width: 1000px; margin: 0 auto; padding: 16px; }
        h1 { font-size: 18px; margin: 0; }
        .muted { color:#555; font-size: 12px; }
        table { width:100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border-bottom: 1px solid #ddd; padding: 6px; font-size: 12px; text-align: left; }
        .right { text-align: right; }
        @media print { .no-print { display:none; } }
    </style>
    <script>function printNow(){ window.print(); }</script>
</head>
<body onload="printNow()">
<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1>Relatório</h1>
        <img src="http://localhost:8000/logo_transp.png" style="height:32px;"/>
    </div>
    <div class="muted">Período: {{ $from->format('d/m/Y') }} a {{ $to->format('d/m/Y') }}</div>

    @if($includeReceivables && $receivablesDetailed->count())
        <h3 style="margin-top:16px;">A Receber</h3>
        <table>
            <thead><tr><th>Vencimento</th><th>Descrição</th><th>Status</th><th class="right">Valor</th></tr></thead>
            <tbody>
            @foreach($receivablesDetailed as $r)
                <tr><td>{{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }}</td><td>{{ $r->description }}</td><td>{{ $r->status }}</td><td class="right">R$ {{ number_format($r->amount, 2, ',', '.') }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if(($includeSuppliers ?? false) && ($suppliers->count() ?? 0))
        <h3 style="margin-top:16px;">Fornecedores</h3>
        <table>
            <thead><tr><th>Nome</th><th>Documento</th><th>E-mail</th><th>Telefone</th></tr></thead>
            <tbody>
            @foreach($suppliers as $s)
                <tr><td>{{ $s->name }}</td><td>{{ $s->cpf_cnpj }}</td><td>{{ $s->email }}</td><td>{{ $s->phone }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if(($includeCategories ?? false) && ($categories->count() ?? 0))
        <h3 style="margin-top:16px;">Categorias</h3>
        <table>
            <thead><tr><th>Nome</th><th>Categoria Pai</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($categories as $c)
                <tr><td>{{ $c->name }}</td><td>{{ optional($c->parent)->name }}</td><td>{{ $c->active ? 'Ativa' : 'Inativa' }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @endif
    @if($includePayables && $payablesDetailed->count())
        <h3 style="margin-top:16px;">A Pagar</h3>
        <table>
            <thead><tr><th>Vencimento</th><th>Descrição</th><th>Status</th><th class="right">Valor</th></tr></thead>
            <tbody>
            @foreach($payablesDetailed as $p)
                <tr><td>{{ \Carbon\Carbon::parse($p->due_date)->format('d/m/Y') }}</td><td>{{ $p->description }}</td><td>{{ $p->status }}</td><td class="right">R$ {{ number_format($p->amount, 2, ',', '.') }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($includeClients && $clients->count())
        <h3 style="margin-top:16px;">Clientes</h3>
        <table>
            <thead><tr><th>Nome</th><th>Documento</th><th>E-mail</th><th>Telefone</th></tr></thead>
            <tbody>
            @foreach($clients as $c)
                <tr><td>{{ $c->name }}</td><td>{{ $c->cpf_cnpj }}</td><td>{{ $c->email }}</td><td>{{ $c->phone }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($includeProducts && $products->count())
        <h3 style="margin-top:16px;">Produtos</h3>
        <table>
            <thead><tr><th>Nome</th><th>SKU</th><th>UN</th><th class="right">Preço</th></tr></thead>
            <tbody>
            @foreach($products as $p)
                <tr><td>{{ $p->name }}</td><td>{{ $p->sku }}</td><td>{{ $p->unit }}</td><td class="right">R$ {{ number_format($p->price, 2, ',', '.') }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @endif
    <div class="no-print" style="margin-top:12px;"><button onclick="window.print()">Imprimir</button></div>
</div>
</body>
</html>


