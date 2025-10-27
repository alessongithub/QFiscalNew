<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cobranças</h2>
    </x-slot>

    <div class="bg-white p-4 rounded shadow">
        <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-8 gap-3 items-end">
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600">Status</label>
                <select name="status" class="w-full border rounded p-2">
                    @php $st = request('status','all'); @endphp
                    <option value="all" @selected($st==='all')>Todos</option>
                    <option value="paid" @selected($st==='paid')>Pagos</option>
                    <option value="pending" @selected($st==='pending')>Pendentes</option>
                    <option value="overdue" @selected($st==='overdue')>Em atraso</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600">Tenant (nome/email)</label>
                <input type="text" name="tenant" value="{{ request('tenant') }}" class="w-full border rounded p-2" placeholder="Buscar por tenant">
            </div>
            <div class="md:col-span-2">
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
            <div class="md:col-span-2 flex items-end gap-2">
                <label class="inline-flex items-center text-xs text-gray-600">
                    <input type="checkbox" name="without_partner" value="1" class="mr-2" @checked(request('without_partner'))>
                    Somente sem partner
                </label>
            </div>
            <div class="md:col-span-1">
                <label class="block text-xs text-gray-600">De</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-1">
                <label class="block text-xs text-gray-600">Até</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-8 flex justify-end gap-2">
                <a href="{{ route('admin.payments') }}" class="px-3 py-2 border rounded text-gray-700">Limpar</a>
                <button class="px-3 py-2 bg-gray-800 text-white rounded">Filtrar</button>
                <a href="{{ request()->fullUrlWithQuery(['export'=>'csv']) }}" class="px-3 py-2 bg-emerald-600 text-white rounded">Exportar CSV</a>
            </div>
        </form>

        @isset($summary)
        <div class="mb-4 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
            <div class="p-3 rounded bg-gray-50 border">
                <div class="text-xs text-gray-500">Faturas</div>
                <div class="font-semibold">{{ number_format((int)($summary['count'] ?? 0), 0, ',', '.') }}</div>
            </div>
            <div class="p-3 rounded bg-gray-50 border">
                <div class="text-xs text-gray-500">Valor total</div>
                <div class="font-semibold">R$ {{ number_format((float)($summary['total_amount'] ?? 0), 2, ',', '.') }}</div>
            </div>
            <div class="p-3 rounded bg-gray-50 border">
                <div class="text-xs text-gray-500">Valor pago</div>
                <div class="font-semibold text-green-700">R$ {{ number_format((float)($summary['paid_amount'] ?? 0), 2, ',', '.') }}</div>
            </div>
            <div class="p-3 rounded bg-gray-50 border">
                <div class="text-xs text-gray-500">Em atraso</div>
                <div class="font-semibold text-red-700">{{ number_format((int)($summary['overdue_count'] ?? 0), 0, ',', '.') }}</div>
            </div>
        </div>
        @endisset
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-600 uppercase">
                    <th>Tenant</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <th class="text-right">Valor</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $inv)
                    <tr class="border-b">
                        <td class="py-1">{{ optional($inv->tenant)->name }}</td>
                        <td class="py-1">{{ optional($inv->due_date)->format('d/m/Y') }}</td>
                        <td class="py-1">
                            {{ $inv->status_name }}
                            @if($inv->paid_at)
                                <span class="text-xs text-green-700">em {{ $inv->paid_at->format('d/m/Y H:i') }}</span>
                            @endif
                        </td>
                        <td class="py-1 text-right">R$ {{ number_format($inv->amount, 2, ',', '.') }}</td>
                        <td class="py-1 text-right">
                            @if($inv->status !== 'paid')
                                <form method="POST" action="#" onsubmit="alert('Simulação: marcado como pago'); return false;" class="inline">
                                    <button class="px-2 py-1 bg-green-600 text-white rounded text-xs">Marcar pago</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @if($inv->payments && $inv->payments->count() > 0)
                        <tr>
                            <td colspan="5" class="bg-gray-50">
                                <div class="px-2 py-2">
                                    <div class="text-xs text-gray-600 font-semibold mb-1">Pagamentos</div>
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="text-gray-500">
                                                <th class="text-left">Método</th>
                                                <th class="text-left">Status</th>
                                                <th class="text-left">Data</th>
                                                <th class="text-right">Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($inv->payments as $p)
                                                <tr>
                                                    <td>{{ strtoupper($p->method ?? '-') }}</td>
                                                    <td>{{ ucfirst($p->status ?? '-') }}</td>
                                                    <td>{{ $p->paid_at ? $p->paid_at->format('d/m/Y H:i') : '—' }}</td>
                                                    <td class="text-right">R$ {{ number_format((float)($p->amount ?? 0), 2, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        <div class="mt-3">{{ $invoices->links() }}</div>
    </div>
</x-admin-layout>
