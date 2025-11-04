<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Saldos e Transferências</h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 space-y-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs mb-1">Status</label>
                    <select name="status" class="w-full border rounded px-3 py-2">
                        @php $statusVal = $status ?? 'requested'; @endphp
                        <option value="all" {{ $statusVal==='all' ? 'selected' : '' }}>Todos</option>
                        <option value="requested" {{ $statusVal==='requested' ? 'selected' : '' }}>Solicitados</option>
                        <option value="available" {{ $statusVal==='available' ? 'selected' : '' }}>Disponíveis</option>
                        <option value="pending" {{ $statusVal==='pending' ? 'selected' : '' }}>Aguardando Liquidação</option>
                        <option value="transferred" {{ $statusVal==='transferred' ? 'selected' : '' }}>Transferidos</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs mb-1">Tenant</label>
                    <input type="text" name="tenant" value="{{ $tenantTerm ?? '' }}" class="w-full border rounded px-3 py-2" placeholder="Nome, fantasia ou e-mail">
                </div>
                <div class="flex items-end">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded">Filtrar</button>
                </div>
            </form>

            <div class="flex items-center gap-4 text-sm">
                <div class="px-3 py-2 bg-yellow-50 text-yellow-800 rounded">Solicitados: <strong>{{ $summary['requested_count'] }}</strong></div>
                <div class="px-3 py-2 bg-green-50 text-green-800 rounded">Total a enviar (solicitados): <strong>R$ {{ number_format($summary['requested_total'], 2, ',', '.') }}</strong></div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs text-gray-600">
                            <th class="px-4 py-2">Data</th>
                            <th class="px-4 py-2">Tenant</th>
                            <th class="px-4 py-2">Recebível</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Bruto</th>
                            <th class="px-4 py-2">Taxa MP</th>
                            <th class="px-4 py-2">Taxa Plataforma (1%)</th>
                            <th class="px-4 py-2">Líquido</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @forelse($balances as $b)
                            <tr class="border-b">
                                <td class="px-4 py-2">{{ optional($b->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2">{{ optional($b->tenant)->name }}</td>
                                <td class="px-4 py-2">#{{ $b->receivable_id }}</td>
                                <td class="px-4 py-2">{{ $b->status }}</td>
                                <td class="px-4 py-2">R$ {{ number_format($b->gross_amount, 2, ',', '.') }}</td>
                                <td class="px-4 py-2">R$ {{ number_format($b->mp_fee_amount, 2, ',', '.') }}</td>
                                <td class="px-4 py-2">R$ {{ number_format($b->platform_fee_amount, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 font-semibold">R$ {{ number_format($b->net_amount, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-center text-gray-500" colspan="8">Nenhum registro encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $balances->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>


