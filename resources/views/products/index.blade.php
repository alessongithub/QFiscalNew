<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Produtos / Serviços</h2>
            <a href="{{ route('products.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Novo Produto ou Serviço</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                @if(session('success'))
                    <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded">{{ session('success') }}</div>
                @endif

                @php
                    $tenant = Auth::user()->tenant;
                    $plan = $tenant?->plan;
                    $features = is_array($plan?->features) ? $plan->features : (json_decode($plan?->features ?? '[]', true) ?? []);
                    $maxProducts = $features['max_products'] ?? null; // null = sem limite definido; -1 = ilimitado
                    $currentProducts = $products->total();
                    $limiteAtingido = $maxProducts !== null && (int)$maxProducts !== -1 && $currentProducts >= (int)$maxProducts;
                @endphp

        <div class="flex items-center justify-between mb-4 text-sm text-gray-700">
                    <div>
                        Produtos: <span class="font-semibold">{{ $currentProducts }}</span>
                        /
                        <span class="font-semibold">{{ $maxProducts === null ? '—' : ((int)$maxProducts === -1 ? 'Ilimitado' : $maxProducts) }}</span>
                    </div>
                    @if($limiteAtingido)
                        <a href="{{ route('plans.upgrade') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded">Fazer Upgrade</a>
                    @endif
                </div>

                <form method="GET" class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                        <div class="md:col-span-3">
                            <label class="block text-xs text-gray-600 mb-1">Buscar</label>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome, SKU, EAN ou NCM" class="border rounded p-2 w-full" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Tipo</label>
                            <div class="relative">
                                <select name="type" id="type_filter" class="border rounded p-2 w-full appearance-none bg-white pr-8">
                                    <option value="">🔍 Todos</option>
                                    <option value="product" @selected(request('type')==='product')>📦 Produtos</option>
                                    <option value="service" @selected(request('type')==='service')>📋 Serviços</option>
                                </select>
                                <div class="absolute right-2 top-1/2 transform -translate-y-1/2 pointer-events-none">
                                    <svg id="type_icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if(request('type') === 'product')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        @elseif(request('type') === 'service')
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                        @endif
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Ordenar por</label>
                            <select name="sort" class="border rounded p-2 w-full">
                                <option value="name" @selected(request('sort','name')==='name')>Nome</option>
                                <option value="created_at" @selected(request('sort')==='created_at')>Cadastro</option>
                                <option value="price" @selected(request('sort')==='price')>Preço</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Direção</label>
                            <select name="direction" class="border rounded p-2 w-full">
                                <option value="asc" @selected(request('direction','asc')==='asc')>Crescente</option>
                                <option value="desc" @selected(request('direction')==='desc')>Decrescente</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Mostrar</label>
                            <select name="per_page" class="border rounded p-2 w-full">
                                @foreach([10,12,25,50,100,200] as $opt)
                                    <option value="{{ $opt }}" @selected((int)request('per_page',10)===$opt)>{{ $opt }} por página</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2 flex items-end gap-2 justify-end">
                            <button class="px-4 py-2 bg-gray-800 text-white rounded">Filtrar</button>
                            <a href="{{ route('products.index') }}" class="px-4 py-2 border rounded text-gray-700 bg-white">Limpar</a>
                        </div>
                    </div>
                </form>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Preço</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estoque</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($products as $product)
                            <tr>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        @php
                                            $unit = strtoupper($product->unit ?? 'UN');
                                            $isService = $product->type === 'service';
                                            $isWeightVolume = in_array($unit, ['KG', 'G', 'MG', 'L', 'ML', 'M', 'M²', 'M³']);
                                        @endphp
                                        
                                        @if($isService)
                                            {{-- Ícone de Serviço (Documento/Lista) --}}
                                            <svg class="w-5 h-5 flex-shrink-0 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                            </svg>
                                        @elseif($isWeightVolume)
                                            {{-- Ícone de Peso/Volume (Cilindro/Garrafa) --}}
                                            <svg class="w-5 h-5 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                            </svg>
                                        @else
                                            {{-- Ícone de Caixa para Produtos em Unidades --}}
                                            <svg class="w-5 h-5 flex-shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                        @endif
                                        <span class="truncate">{{ $product->name }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">{{ $product->sku ?: '-' }}</td>
                                <td class="px-3 py-2">R$ {{ number_format($product->price, 2, ',', '.') }}</td>
                                <td class="px-3 py-2">
                                    @if($product->type === 'service')
                                        <span class="text-gray-600">∞</span>
                                    @else
                                        {{ isset($balances) ? \App\Helpers\QuantityHelper::formatByUnit($balances[$product->id] ?? 0, $product->unit) : '—' }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">{{ $product->type === 'service' ? 'Serviço' : 'Produto' }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-col items-start">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $product->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $product->active ? 'Ativo' : 'Inativo' }}
                                        </span>
                                        @php
                                            $missing = [];
                                            if ($product->type === 'product') {
                                                $ncmDigits = preg_replace('/\D/','', (string)($product->ncm ?? ''));
                                                if (empty($ncmDigits) || strlen($ncmDigits) !== 8) { $missing[] = 'NCM'; }
                                                if (empty($product->cfop)) { $missing[] = 'CFOP'; }
                                                if ($product->aliquota_icms === null) { $missing[] = 'ICMS'; }
                                                if ($product->aliquota_pis === null) { $missing[] = 'PIS'; }
                                                if ($product->aliquota_cofins === null) { $missing[] = 'COFINS'; }
                                            } else { // service
                                                if (empty($product->codigo_servico)) { $missing[] = 'Cód. Serviço'; }
                                                if ($product->iss_aliquota === null) { $missing[] = 'ISS'; }
                                                if ($product->aliquota_pis === null) { $missing[] = 'PIS'; }
                                                if ($product->aliquota_cofins === null) { $missing[] = 'COFINS'; }
                                            }
                                            $isIncomplete = count($missing) > 0;
                                            $hint = $isIncomplete ? ('Faltam dados para emissão de nota: ' . implode(', ', array_unique($missing))) : '';
                                        @endphp
                                        @if($isIncomplete && $product->type === 'product')
                                            <span class="mt-1 text-[10px] leading-4 font-medium text-red-600" title="{{ $hint }}">
                                                Incompleto
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-right text-sm space-x-3">
                                    <a href="{{ route('products.show', $product) }}" class="text-gray-700 hover:text-gray-900" title="Ver">
                                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    @if($product->active)
                                    <a href="{{ route('products.edit', $product) }}" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    @endif
                                    @php
                                        $hasMov = \App\Models\StockMovement::where('tenant_id', auth()->user()->tenant_id)->where('product_id', $product->id)->exists();
                                    @endphp
                                    @if(!$hasMov)
                                    <form method="POST" action="{{ route('products.destroy', $product) }}" class="inline" onsubmit="return confirm('Excluir este produto?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Excluir">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2m-9 0h10"/></svg>
                                        </button>
                                    </form>
                                    @else
                                        <form method="POST" action="{{ route('products.toggle_active', $product) }}" class="inline" onsubmit="return confirm('{{ $product->active ? 'Desativar' : 'Ativar' }} este produto?')">
                                            @csrf
                                            <button type="submit" class="{{ $product->active ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800' }}" title="{{ $product->active ? 'Desativar' : 'Ativar' }}">
                                                @if($product->active)
                                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                @else
                                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                @endif
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-4">{{ $products->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeFilter = document.getElementById('type_filter');
    const typeIcon = document.getElementById('type_icon');
    
    if (typeFilter && typeIcon) {
        typeFilter.addEventListener('change', function() {
            const value = this.value;
            let iconPath = '';
            let fill = 'none';
            let viewBox = '0 0 24 24';
            
            if (value === 'product') {
                fill = 'none';
                iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>';
            } else if (value === 'service') {
                fill = 'currentColor';
                viewBox = '0 0 20 20';
                iconPath = '<path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>';
            } else {
                fill = 'none';
                iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>';
            }
            
            typeIcon.setAttribute('fill', fill);
            typeIcon.setAttribute('viewBox', viewBox);
            typeIcon.innerHTML = iconPath;
        });
    }
});
</script>


