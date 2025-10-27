<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Produto</h2>
            <a href="{{ route('products.index') }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded">Voltar</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="border rounded p-4 bg-gray-50">
                        <h3 class="font-semibold mb-3">Identificação</h3>
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div><dt class="text-gray-500">Nome</dt><dd class="text-gray-900">{{ $product->name }}</dd></div>
                            <div><dt class="text-gray-500">Tipo</dt><dd class="text-gray-900">{{ $product->type === 'service' ? 'Serviço' : 'Produto' }}</dd></div>
                            <div><dt class="text-gray-500">SKU</dt><dd class="text-gray-900">{{ $product->sku ?: '-' }}</dd></div>
                            <div><dt class="text-gray-500">EAN</dt><dd class="text-gray-900">{{ $product->ean ?: '-' }}</dd></div>
                            <div><dt class="text-gray-500">Unidade</dt><dd class="text-gray-900">{{ $product->unit }}</dd></div>
                            <div><dt class="text-gray-500">Status</dt><dd class="text-gray-900">{{ $product->active ? 'Ativo' : 'Inativo' }}</dd></div>
                            <div><dt class="text-gray-500">Preço</dt><dd class="text-gray-900">R$ {{ number_format($product->price, 2, ',', '.') }}</dd></div>
                        </dl>
                    </div>

                    <div class="border rounded p-4 bg-gray-50">
                        <h3 class="font-semibold mb-3">Classificação Fiscal</h3>
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div><dt class="text-gray-500">NCM</dt><dd class="text-gray-900">{{ $product->ncm ?: '-' }}</dd></div>
                            <div><dt class="text-gray-500">CEST</dt><dd class="text-gray-900">{{ $product->cest ?: '-' }}</dd></div>
                            <div><dt class="text-gray-500">CFOP</dt><dd class="text-gray-900">{{ $product->cfop ?: '-' }}</dd></div>
                            <div><dt class="text-gray-500">Origem</dt><dd class="text-gray-900">
                                @php $o = (string)($product->origin ?? ''); @endphp
                                @switch($o)
                                    @case('0') 0 - Nacional @break
                                    @case('1') 1 - Estrangeira - Importação direta @break
                                    @case('2') 2 - Estrangeira - Adquirida no mercado interno @break
                                    @case('3') 3 - Nacional - Mercadoria com >40% importação @break
                                    @case('4') 4 - Nacional - Produção conforme processo produtivo @break
                                    @case('5') 5 - Nacional - Mercadoria com <40% importação @break
                                    @case('6') 6 - Estrangeira - Importação direta sem similar nacional @break
                                    @case('7') 7 - Estrangeira - Mercado interno sem similar nacional @break
                                    @case('8') 8 - Nacional - Mercadoria com >70% importação @break
                                    @default -
                                @endswitch
                            </dd></div>
                            <div><dt class="text-gray-500">CSOSN</dt><dd class="text-gray-900">{{ $product->csosn ?: '-' }}</dd></div>
                            <div><dt class="text-gray-500">CST ICMS</dt><dd class="text-gray-900">{{ $product->cst_icms ?: '-' }}</dd></div>
                            <div><dt class="text-gray-500">CST PIS</dt><dd class="text-gray-900">{{ $product->cst_pis ?: '-' }}</dd></div>
                            <div><dt class="text-gray-500">CST COFINS</dt><dd class="text-gray-900">{{ $product->cst_cofins ?: '-' }}</dd></div>
                        </dl>
                    </div>

                    <div class="border rounded p-4 bg-gray-50 md:col-span-2">
                        <h3 class="font-semibold mb-3">Impostos</h3>
                        <dl class="grid grid-cols-3 gap-3 text-sm">
                            <div><dt class="text-gray-500">Alíquota ICMS</dt><dd class="text-gray-900">{{ $product->aliquota_icms !== null ? $product->aliquota_icms . '%' : '-' }}</dd></div>
                            <div><dt class="text-gray-500">Alíquota PIS</dt><dd class="text-gray-900">{{ $product->aliquota_pis !== null ? $product->aliquota_pis . '%' : '-' }}</dd></div>
                            <div><dt class="text-gray-500">Alíquota COFINS</dt><dd class="text-gray-900">{{ $product->aliquota_cofins !== null ? $product->aliquota_cofins . '%' : '-' }}</dd></div>
                        </dl>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>


