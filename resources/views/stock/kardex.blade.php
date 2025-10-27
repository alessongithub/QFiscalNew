<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kardex • {{ $product->name }} ({{ $product->sku ?: '—' }})</h2>
            <a href="{{ route('stock.index') }}" class="px-3 py-2 border rounded">Voltar</a>
        </div>
    </x-slot>

    <div class="bg-white p-4 rounded shadow">
        <form method="GET" class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-3">
                <label class="block text-xs text-gray-600">De</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs text-gray-600">Até</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600">Tipo</label>
                <select name="type" class="w-full border rounded p-2">
                    <option value="">Todos</option>
                    <option value="entry" @selected($type==='entry')>Entrada</option>
                    <option value="exit" @selected($type==='exit')>Saída</option>
                    <option value="adjustment" @selected($type==='adjustment')>Ajuste</option>
                </select>
            </div>
            <div class="md:col-span-2 md:col-start-11">
                <div class="text-sm text-gray-700">Saldo atual: <strong>{{ number_format($currentBalance, 3, ',', '.') }}</strong></div>
            </div>
            <div class="md:col-span-12 flex items-end justify-end gap-2">
                <button class="px-3 py-2 bg-gray-800 text-white rounded">Filtrar</button>
                <a href="{{ route('stock.kardex', $product) }}" class="px-3 py-2 border rounded text-gray-700 bg-white">Limpar</a>
            </div>
        </form>

        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Obs</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qtd</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">V.Unit</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($movements as $m)
                    <tr>
                        <td class="px-3 py-2">{{ $m->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-2">{{ ['entry'=>'Entrada','exit'=>'Saída','adjustment'=>'Ajuste'][$m->type] ?? $m->type }}</td>
                        <td class="px-3 py-2">{{ $m->document ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $m->note ?: '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($m->quantity, 3, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">{{ $m->unit_price !== null ? 'R$ '.number_format($m->unit_price, 2, ',', '.') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-gray-500">Nenhum movimento</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $movements->links() }}</div>
    </div>
</x-app-layout>


