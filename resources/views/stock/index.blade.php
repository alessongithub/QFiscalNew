<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Estoque</h2>
            <a href="{{ route('stock.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Novo Movimento</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                @if(session('success'))
                    <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded">{{ session('success') }}</div>
                @endif

                <form method="GET" class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <div class="md:col-span-12">
                        <label class="block text-xs text-gray-600">Buscar</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome ou SKU" class="border rounded p-2 w-full">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-600">Ordenar por</label>
                        <select name="sort" class="border rounded p-2 w-full min-w-[140px]">
                            <option value="name" @selected(request('sort','name')==='name')>Nome</option>
                            <option value="sku" @selected(request('sort')==='sku')>SKU</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600">Direção</label>
                        <select name="direction" class="border rounded p-2 w-full min-w-[120px]">
                            <option value="asc" @selected(request('direction','asc')==='asc')>Crescente</option>
                            <option value="desc" @selected(request('direction')==='desc')>Decrescente</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 md:col-start-11">
                        <label class="block text-xs text-gray-600">Mostrar</label>
                        <select name="per_page" class="border rounded p-2 w-full min-w-[140px]">
                            @foreach([10,12,25,50,100,200] as $opt)
                                <option value="{{ $opt }}" @selected((int)request('per_page',12)===$opt)>{{ $opt }} por página</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-12 flex items-end justify-end gap-2">
                        <button class="px-3 py-2 bg-gray-800 text-white rounded">Filtrar</button>
                        <a href="{{ route('stock.index') }}" class="px-3 py-2 border rounded text-gray-700 bg-white">Limpar</a>
                    </div>
                </form>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Saldo</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                            </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($products as $p)
                            <tr>
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        @php
                                            $unit = strtoupper($p->unit ?? 'UN');
                                            $isService = $p->type === 'service';
                                            $isWeightVolume = in_array($unit, ['KG', 'G', 'MG', 'L', 'ML', 'M', 'M²', 'M³']);
                                        @endphp
                                        
                                        @if($isService)
                                            {{-- Ícone de Serviço (Documento/Lista) --}}
                                            <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                            </svg>
                                        @elseif($isWeightVolume)
                                            {{-- Ícone de Peso/Volume (Cilindro/Garrafa) --}}
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                            </svg>
                                        @else
                                            {{-- Ícone de Caixa para Produtos em Unidades --}}
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                        @endif
                                        <span>{{ $p->name }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">{{ $p->sku ?: '-' }}</td>
                                <td class="px-3 py-2">
                                    @if($p->type === 'service')
                                        <span class="text-gray-600">∞</span>
                                    @else
                                        {{ \App\Helpers\QuantityHelper::formatByUnit($balances[$p->id] ?? 0, $p->unit) }}
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('stock.kardex', $p) }}" class="inline-flex items-center justify-center w-8 h-8 rounded bg-gray-50 hover:bg-gray-100 text-gray-700" title="Kardex">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h4a1 1 0 011 1v16a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm8 4a1 1 0 011-1h4a1 1 0 011 1v12a1 1 0 01-1 1h-4a1 1 0 01-1-1V8zm8 6a1 1 0 011-1h1a1 1 0 011 1v6a1 1 0 01-1 1h-1a1 1 0 01-1-1v-6z"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-8">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-semibold">Movimentos recentes</h3>
                        <button onclick="toggleMovements()" class="text-sm text-blue-600 hover:text-blue-800">Mostrar/Ocultar</button>
                    </div>
                    <table id="movementsTable" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qtd</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">V.Unit</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach(($movements ?? []) as $m)
                                <tr>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span>{{ $m->created_at?->format('d/m/Y H:i') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">{{ $m->product?->name }}</td>
                                    <td class="px-3 py-2">
                                        @if($m->type === 'entry')
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Entrada</span>
                                        @elseif($m->type === 'exit')
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Saída</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Ajuste</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">{{ \App\Helpers\QuantityHelper::formatByUnit($m->quantity, $m->product?->unit ?? 'UN') }}</td>
                                    <td class="px-3 py-2 text-right">
                                        @php
                                            // Primeiro tenta pegar o unit_price do movimento
                                            $unitPrice = $m->unit_price ?? null;
                                            
                                            // Se não tiver, usa o avg_cost (preço de compra) do produto
                                            if (($unitPrice === null || $unitPrice === '' || (float)$unitPrice <= 0) && $m->product) {
                                                $unitPrice = $m->product->avg_cost ?? null;
                                            }
                                        @endphp
                                        @if($unitPrice !== null && $unitPrice !== '' && (float)$unitPrice > 0)
                                            <span class="font-medium text-gray-900">R$ {{ number_format((float)$unitPrice, 2, ',', '.') }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        @if(auth()->user()->hasPermission('stock.edit'))
                                            <form method="POST" action="{{ route('stock.reversal', $m) }}" class="inline" onsubmit="return confirm('Registrar estorno deste movimento?')">
                                                @csrf
                                                <button type="submit" title="Estornar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-yellow-50 hover:bg-yellow-100 text-yellow-700">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10l-4 4 4 4m0-4h11a4 4 0 000-8H9V4"/></svg>                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4 flex flex-col sm:flex-row justify-between items-center gap-3 pt-3 border-t border-gray-200">
                        <div>{{ $movements->links() }}</div>
                        <a href="{{ route('stock.movements') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-150 shadow-sm hover:shadow">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                            <span>Ver todos os movimentos</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
function toggleMovements() {
    const table = document.getElementById('movementsTable');
    const button = event.target;
    
    if (table.style.display === 'none') {
        table.style.display = 'table';
        button.textContent = 'Mostrar/Ocultar';
    } else {
        table.style.display = 'none';
        button.textContent = 'Mostrar Movimentos';
    }
}

</script>

