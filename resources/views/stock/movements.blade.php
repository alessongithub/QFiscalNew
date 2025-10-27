<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Movimentos de Estoque</h2>
            <div class="flex space-x-2">
                <button onclick="printMovements()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimir
                </button>
                <a href="{{ route('stock.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">Voltar</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                @if(session('success'))
                    <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded">{{ session('success') }}</div>
                @endif

                <!-- Estatísticas -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="text-sm font-medium text-green-800">Entradas</div>
                        <div class="text-2xl font-bold text-green-900">{{ \App\Helpers\QuantityHelper::formatByUnit($stats['total_entries'], 'UN') }}</div>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="text-sm font-medium text-red-800">Saídas</div>
                        <div class="text-2xl font-bold text-red-900">{{ \App\Helpers\QuantityHelper::formatByUnit($stats['total_exits'], 'UN') }}</div>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="text-sm font-medium text-yellow-800">Ajustes</div>
                        <div class="text-2xl font-bold text-yellow-900">{{ \App\Helpers\QuantityHelper::formatByUnit($stats['total_adjustments'], 'UN') }}</div>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="text-sm font-medium text-blue-800">Total Movimentos</div>
                        <div class="text-2xl font-bold text-blue-900">{{ number_format($stats['total_movements'], 0, ',', '.') }}</div>
                    </div>
                </div>

                <!-- Filtros -->
                <form method="GET" class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Data Início</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="border rounded p-2 w-full">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Data Fim</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="border rounded p-2 w-full">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Produto</label>
                        <input type="text" name="product" value="{{ request('product') }}" placeholder="Nome ou SKU" class="border rounded p-2 w-full">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Tipo</label>
                        <select name="type" class="border rounded p-2 w-full">
                            <option value="">Todos</option>
                            <option value="entry" @selected(request('type')==='entry')>Entrada</option>
                            <option value="exit" @selected(request('type')==='exit')>Saída</option>
                            <option value="adjustment" @selected(request('type')==='adjustment')>Ajuste</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Usuário</label>
                        <select name="user_id" class="border rounded p-2 w-full">
                            <option value="">Todos</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(request('user_id')==$user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="px-3 py-2 bg-gray-800 text-white rounded">Filtrar</button>
                        <a href="{{ route('stock.movements') }}" class="px-3 py-2 border rounded text-gray-700 bg-white">Limpar</a>
                    </div>
                </form>

                <!-- Ordenação e Paginação -->
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center space-x-4">
                        <label class="text-sm text-gray-600">Ordenar por:</label>
                        <select onchange="updateSort()" id="sortSelect" class="border rounded p-1 text-sm">
                            <option value="created_at" @selected(request('sort','created_at')==='created_at')>Data</option>
                            <option value="product_id" @selected(request('sort')==='product_id')>Produto</option>
                            <option value="type" @selected(request('sort')==='type')>Tipo</option>
                            <option value="quantity" @selected(request('sort')==='quantity')>Quantidade</option>
                            <option value="unit_price" @selected(request('sort')==='unit_price')>Preço</option>
                        </select>
                        <select onchange="updateSort()" id="directionSelect" class="border rounded p-1 text-sm w-32">
                            <option value="desc" @selected(request('direction','desc')==='desc')>Decrescente</option>
                            <option value="asc" @selected(request('direction')==='asc')>Crescente</option>
                        </select>
                    </div>
                    <div class="flex items-center space-x-2">
                        <label class="text-sm text-gray-600">Mostrar:</label>
                        <select onchange="updatePerPage()" id="perPageSelect" class="border rounded p-1 text-sm w-32">
                            @foreach([10,25,50,100] as $opt)
                                <option value="{{ $opt }}" @selected((int)request('per_page',25)===$opt)>{{ $opt }} por página</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Tabela de Movimentos -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantidade</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Preço Unit.</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Usuário</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($movements as $movement)
                                <tr>
                                    <td class="px-3 py-2 text-sm">{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $movement->product?->name ?? 'Produto excluído' }}</td>
                                    <td class="px-3 py-2 text-sm">
                                        @php
                                            $typeLabel = $movement->type === 'entry' ? 'Entrada' : ($movement->type === 'exit' ? 'Saída' : 'Ajuste');
                                            $typeColor = $movement->type === 'entry' ? 'green' : ($movement->type === 'exit' ? 'red' : 'yellow');
                                        @endphp
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-{{ $typeColor }}-100 text-{{ $typeColor }}-800">
                                            {{ $typeLabel }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-sm">{{ \App\Helpers\QuantityHelper::formatByUnit($movement->quantity, $movement->product?->unit ?? 'UN') }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $movement->unit_price !== null ? 'R$ '.number_format($movement->unit_price, 2, ',', '.') : '—' }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $movement->document ?? '—' }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $movement->user?->name ?? 'Sistema' }}</td>
                                    <td class="px-3 py-2 text-right">
                                        @if(auth()->user()->hasPermission('stock.edit'))
                                            <form method="POST" action="{{ route('stock.reversal', $movement) }}" class="inline" onsubmit="return confirm('Registrar estorno deste movimento?')">
                                                @csrf
                                                <button type="submit" title="Estornar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-yellow-50 hover:bg-yellow-100 text-yellow-700">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10l-4 4 4 4m0-4h11a4 4 0 000-8H9V4"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div class="mt-4">
                    {{ $movements->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
function updateSort() {
    const sort = document.getElementById('sortSelect').value;
    const direction = document.getElementById('directionSelect').value;
    updateUrl({ sort, direction });
}

function updatePerPage() {
    const perPage = document.getElementById('perPageSelect').value;
    updateUrl({ per_page: perPage });
}

function updateUrl(params) {
    const url = new URL(window.location);
    Object.keys(params).forEach(key => {
        if (params[key]) {
            url.searchParams.set(key, params[key]);
        } else {
            url.searchParams.delete(key);
        }
    });
    window.location.href = url.toString();
}

function printMovements() {
    // Criar nova janela para impressão
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    
    // Obter dados da tabela atual
    const table = document.querySelector('table');
    const tableClone = table.cloneNode(true);
    
    // Remover coluna "Ações" da impressão
    const headerRow = tableClone.querySelector('thead tr');
    const lastHeaderCell = headerRow.querySelector('th:last-child');
    if (lastHeaderCell && lastHeaderCell.textContent.trim() === 'Ações') {
        lastHeaderCell.remove();
    }
    
    // Remover coluna "Ações" de todas as linhas do corpo
    const bodyRows = tableClone.querySelectorAll('tbody tr');
    bodyRows.forEach(row => {
        const lastCell = row.querySelector('td:last-child');
        if (lastCell) {
            lastCell.remove();
        }
    });
    
    // Obter filtros aplicados
    const filters = {
        date_from: document.querySelector('input[name="date_from"]').value,
        date_to: document.querySelector('input[name="date_to"]').value,
        product: document.querySelector('input[name="product"]').value,
        type: document.querySelector('select[name="type"]').value,
        user_id: document.querySelector('select[name="user_id"]').value
    };
    
    // Criar conteúdo para impressão
    let printContent = `
        <html>
        <head>
            <title>Movimentos de Estoque</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #333; text-align: center; margin-bottom: 30px; }
                .filters { background: #f5f5f5; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
                .filters h3 { margin: 0 0 10px 0; color: #666; }
                .filters p { margin: 5px 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .stats { display: flex; justify-content: space-around; margin: 20px 0; }
                .stat-box { background: #f9f9f9; padding: 15px; border-radius: 5px; text-align: center; }
                .stat-label { font-size: 12px; color: #666; }
                .stat-value { font-size: 18px; font-weight: bold; color: #333; }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <h1>Relatório de Movimentos de Estoque</h1>
            
            <div class="filters">
                <h3>Filtros Aplicados:</h3>
                <p><strong>Período:</strong> ${filters.date_from || 'Todos'} até ${filters.date_to || 'Todos'}</p>
                <p><strong>Produto:</strong> ${filters.product || 'Todos'}</p>
                <p><strong>Tipo:</strong> ${filters.type ? (filters.type === 'entry' ? 'Entrada' : filters.type === 'exit' ? 'Saída' : 'Ajuste') : 'Todos'}</p>
                <p><strong>Usuário:</strong> ${filters.user_id ? document.querySelector('select[name="user_id"] option:checked').textContent : 'Todos'}</p>
            </div>
            
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-label">Total Entradas</div>
                    <div class="stat-value">{{ \App\Helpers\QuantityHelper::formatByUnit($stats['total_entries'], 'UN') }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Saídas</div>
                    <div class="stat-value">{{ \App\Helpers\QuantityHelper::formatByUnit($stats['total_exits'], 'UN') }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Ajustes</div>
                    <div class="stat-value">{{ \App\Helpers\QuantityHelper::formatByUnit($stats['total_adjustments'], 'UN') }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Movimentos</div>
                    <div class="stat-value">{{ $stats['total_movements'] }}</div>
                </div>
            </div>
            
            ${tableClone.outerHTML}
            
            <div style="margin-top: 30px; text-align: center; color: #666; font-size: 12px;">
                Relatório gerado em: ${new Date().toLocaleString('pt-BR')}
            </div>
        </body>
        </html>
    `;
    
    printWindow.document.write(printContent);
    printWindow.document.close();
    
    // Aguardar carregamento e imprimir
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}

</script>
