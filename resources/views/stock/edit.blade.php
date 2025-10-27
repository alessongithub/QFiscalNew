<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Movimento de Estoque</h2>
            <a href="{{ route('stock.index') }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded">Voltar</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <form method="POST" action="{{ route('stock.update', $movement) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Produto</label>
                        <select name="product_id" class="border rounded p-2 w-full" required>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" @selected($movement->product_id === $p->id)>{{ $p->name }} {{ $p->sku ? '(' . $p->sku . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Tipo</label>
                            <select name="type" class="border rounded p-2 w-full" required>
                                <option value="entry" @selected($movement->type==='entry')>Entrada</option>
                                <option value="exit" @selected($movement->type==='exit')>Saída</option>
                                <option value="adjustment" @selected($movement->type==='adjustment')>Ajuste</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Quantidade</label>
                            <input type="number" step="0.001" name="quantity" class="border rounded p-2 w-full" value="{{ $movement->quantity }}" required />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Preço Unitário</label>
                            <input type="number" step="0.01" name="unit_price" class="border rounded p-2 w-full" value="{{ $movement->unit_price }}" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Documento (opcional)</label>
                            <input type="text" name="document" class="border rounded p-2 w-full" value="{{ $movement->document }}" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Observação</label>
                            <input type="text" name="note" class="border rounded p-2 w-full" value="{{ $movement->note }}" />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>



