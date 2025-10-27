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
                                <td class="px-3 py-2">{{ $p->name }}</td>
                                <td class="px-3 py-2">{{ $p->sku ?: '-' }}</td>
                                <td class="px-3 py-2">{{ \App\Helpers\QuantityHelper::formatByUnit($balances[$p->id] ?? 0, $p->unit) }}</td>
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
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">V.Unit</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach(($movements ?? []) as $m)
                                <tr>
                                    <td class="px-3 py-2">{{ $m->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2">{{ $m->product?->name }}</td>
                                    <td class="px-3 py-2">
                                        @php
                                            $typeLabel = $m->type === 'entry' ? 'Entrada' : ($m->type === 'exit' ? 'Saída' : 'Ajuste');
                                        @endphp
                                        {{ $typeLabel }}
                                    </td>
                                    <td class="px-3 py-2">{{ \App\Helpers\QuantityHelper::formatByUnit($m->quantity, $m->product?->unit ?? 'UN') }}</td>
                                    <td class="px-3 py-2">{{ $m->unit_price !== null ? 'R$ '.number_format($m->unit_price, 2, ',', '.') : '—' }}</td>
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
                    <div class="mt-3 flex justify-between items-center">
                        <div>{{ $movements->links() }}</div>
                        <a href="{{ route('stock.movements') }}" class="text-blue-600 hover:text-blue-800 text-sm">Ver todos os movimentos →</a>
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

