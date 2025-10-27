<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notas de Entrada</h2>
            <a href="{{ route('inbound.create') }}" class="px-4 py-2 bg-green-600 text-white rounded">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Importar XML
            </a>
        </div>
    </x-slot>

    <div class="bg-white p-4 rounded shadow">
    <form method="GET"
      class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded grid grid-cols-1 md:grid-cols-12 gap-x-3 gap-y-2 items-end">

    <!-- Linha 1: Buscar + Fornecedor -->
    <div class="md:col-span-5">
        <label class="block text-xs text-gray-600">Buscar (número, chave, valor)</label>
        <input type="text" name="search" value="{{ request('search') }}"
               class="border rounded p-2 w-full" placeholder="Ex.: 123, 3524…" />
    </div>

    <div class="md:col-span-7">
        <label class="block text-xs text-gray-600">Fornecedor</label>
        <input type="text" name="supplier" value="{{ request('supplier') }}"
               class="border rounded p-2 w-full" placeholder="Nome ou CPF/CNPJ" />
    </div>

    <!-- Linha 2: De, Até, Ordenar, Direção, Mostrar, Botões -->
    <div class="md:col-span-2">
        <label class="block text-xs text-gray-600">De</label>
        <input type="date" name="from" value="{{ request('from') }}"
               class="border rounded p-2 w-full" />
    </div>

    <div class="md:col-span-2">
        <label class="block text-xs text-gray-600">Até</label>
        <input type="date" name="to" value="{{ request('to') }}"
               class="border rounded p-2 w-full" />
    </div>

    <div class="md:col-span-2">
        <label class="block text-xs text-gray-600">Ordenar</label>
        <select name="sort" class="border rounded p-2 w-full">
            <option value="id" @selected(request('sort','id')==='id')>Cadastro</option>
            <option value="issue_date" @selected(request('sort')==='issue_date')>Emissão</option>
            <option value="total_invoice" @selected(request('sort')==='total_invoice')>Valor</option>
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-xs text-gray-600">Dir.</label>
        <select name="direction" class="border rounded p-2 w-full">
            <option value="desc" @selected(request('direction','desc')==='desc')>Desc</option>
            <option value="asc" @selected(request('direction')==='asc')>Asc</option>
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-xs text-gray-600">Mostrar</label>
        <select name="per_page" class="border rounded p-2 w-full">
            @foreach([10,12,25,50,100,200] as $opt)
                <option value="{{ $opt }}" @selected((int)request('per_page',12)===$opt)>
                    {{ $opt }} por página
                </option>
            @endforeach
        </select>
    </div>

    <div class="md:col-span-2 flex justify-end gap-2">
        <button class="px-3 py-2 bg-gray-800 text-white rounded">Filtrar</button>
        <a href="{{ route('inbound.index') }}"
           class="px-3 py-2 border rounded text-gray-700 bg-white">Limpar</a>
    </div>
</form>

        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left border-b text-gray-600">
                    <th class="py-2 px-2">Número/Série</th>
                    <th class="py-2 px-2">Fornecedor</th>
                    <th class="py-2 px-2">Emissão</th>
                    <th class="py-2 px-2">Total</th>
                    <th class="py-2 px-2">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $n)
                <tr class="border-b">
                    <td class="py-2 px-2">{{ $n->number }} / {{ $n->series }}</td>
                    <td class="py-2 px-2">{{ optional($n->supplier)->name ?? ($n->raw_summary['supplier_name'] ?? '—') }}</td>
                    <td class="py-2 px-2">{{ optional($n->issue_date)->format('d/m/Y') }}</td>
                    <td class="py-2 px-2">R$ {{ number_format($n->total_invoice, 2, ',', '.') }}</td>
                    <td class="py-2 px-2">
                        <a href="{{ route('inbound.edit', $n) }}" class="inline-flex items-center justify-center w-8 h-8 rounded bg-blue-50 hover:bg-blue-100 text-blue-700" title="Conferir">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-6 text-center text-gray-500">Nenhuma nota importada</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $invoices->links() }}</div>
    </div>
</x-app-layout>


