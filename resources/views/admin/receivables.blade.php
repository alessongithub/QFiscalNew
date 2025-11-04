<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Boletos por Tenant</h2>
    </x-slot>

    <div class="bg-white p-4 rounded shadow">
        <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600">Status</label>
                @php $st = request('status','all'); @endphp
                <select name="status" class="w-full border rounded p-2">
                    <option value="all" @selected($st==='all')>Todos</option>
                    <option value="paid" @selected($st==='paid')>Pagos</option>
                    <option value="pending" @selected($st==='pending')>Pendentes</option>
                    <option value="overdue" @selected($st==='overdue')>Em atraso</option>
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs text-gray-600">Tenant (nome/email)</label>
                <input type="text" name="tenant" value="{{ request('tenant') }}" class="w-full border rounded p-2" placeholder="Buscar por tenant">
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs text-gray-600">Partner</label>
                <select name="partner_id" class="w-full border rounded p-2">
                    <option value="">Todos</option>
                    @isset($partners)
                        @foreach($partners as $p)
                            <option value="{{ $p->id }}" @selected((string)request('partner_id')===(string)$p->id)>{{ $p->name }}</option>
                        @endforeach
                    @endisset
                </select>
            </div>
            <div class="md:col-span-1">
                <label class="block text-xs text-gray-600">Venc. de</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-1">
                <label class="block text-xs text-gray-600">Venc. até</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-2 flex items-end gap-2">
                <button class="px-3 py-2 bg-gray-800 text-white rounded">Filtrar</button>
                <a href="{{ route('admin.receivables') }}" class="px-3 py-2 border rounded text-gray-700 bg-white">Limpar</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-600 uppercase">
                        <th>Tenant</th>
                        <th>Cliente</th>
                        <th>Descrição</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                        <th class="text-right">Valor</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receivables as $r)
                        <tr class="border-b">
                            <td class="py-1">{{ optional($r->tenant)->name }}</td>
                            <td class="py-1">{{ optional($r->client)->name ?? '—' }}</td>
                            <td class="py-1">{{ $r->description }}</td>
                            <td class="py-1">{{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }}</td>
                            <td class="py-1">
                                @php 
                                    $statusClass = $r->status === 'paid' ? 'bg-green-600' : (in_array($r->status,['open','partial']) ? 'bg-yellow-600' : 'bg-gray-600');
                                @endphp
                                <span class="px-2 py-1 rounded text-white text-xs {{ $statusClass }}">{{ ucfirst($r->status) }}</span>
                            </td>
                            <td class="py-1 text-right">R$ {{ number_format($r->amount, 2, ',', '.') }}</td>
                            <td class="py-1 text-right">
                                <div class="inline-flex items-center gap-2">
                                    @if(!empty($r->boleto_pdf_url) || !empty($r->boleto_url))
                                        <a href="{{ $r->boleto_pdf_url ?: $r->boleto_url }}" target="_blank" rel="noopener" title="Ver boleto" class="inline-flex items-center justify-center w-8 h-8 rounded bg-purple-50 hover:bg-purple-100 text-purple-700">
                                            <!-- Ícone estilo boleto: retângulo com linhas e "código de barras" -->
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="4" width="18" height="16" rx="2" ry="2"></rect>
                                                <line x1="6" y1="8" x2="12" y2="8"></line>
                                                <line x1="6" y1="11" x2="14" y2="11"></line>
                                                <!-- código de barras -->
                                                <line x1="6" y1="16" x2="6" y2="18"></line>
                                                <line x1="8" y1="16" x2="8" y2="18"></line>
                                                <line x1="9.5" y1="16" x2="9.5" y2="18"></line>
                                                <line x1="11" y1="16" x2="11" y2="18"></line>
                                                <line x1="13" y1="16" x2="13" y2="18"></line>
                                                <line x1="14.5" y1="16" x2="14.5" y2="18"></line>
                                                <line x1="16" y1="16" x2="16" y2="18"></line>
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-4 text-center text-gray-500">Nenhum boleto encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $receivables->links() }}</div>
    </div>
</x-admin-layout>


