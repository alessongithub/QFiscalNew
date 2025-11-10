<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Caixa do Dia</h2>
            <button onclick="window.print()" class="px-3 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300" title="Imprimir caixa e movimentações">Imprimir</button>
        </div>
    </x-slot>

    <style>
    @media print {
        body * { visibility: hidden; }
        #print-area, #print-area * { visibility: visible; }
        #print-area { position: absolute; left: 0; top: 0; width: 100%; }
    }
    </style>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif

    <div id="print-area">
    <div class="bg-white p-4 rounded shadow max-w-3xl">
        <form method="GET" class="mb-4 flex items-end gap-3">
            <div>
                <label class="block text-xs text-gray-600">Data</label>
                <input type="date" name="date" value="{{ $date }}" class="w-full border rounded p-2">
            </div>
            <div>
                <button class="px-3 py-2 bg-gray-800 text-white rounded">Atualizar</button>
            </div>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-3 border rounded bg-green-50">
                <div class="text-xs text-gray-600">Recebimentos</div>
                <div class="text-lg font-semibold">R$ {{ number_format($received, 2, ',', '.') }}</div>
            </div>
            <div class="p-3 border rounded bg-red-50">
                <div class="text-xs text-gray-600">Pagamentos</div>
                <div class="text-lg font-semibold">R$ {{ number_format($paid, 2, ',', '.') }}</div>
            </div>
            <div class="p-3 border rounded bg-blue-50">
                <div class="text-xs text-gray-600">Líquido</div>
                <div class="text-lg font-semibold">R$ {{ number_format($net, 2, ',', '.') }}</div>
            </div>
        </div>

        <div class="mt-6">
            @if($closed)
                <div class="p-3 border rounded bg-gray-50">
                    Fechado por {{ optional($closed->closedByUser)->name ?? '—' }} em {{ optional($closed->closed_at)->format('d/m/Y H:i') }}.
                </div>
            @else
                <form method="POST" action="{{ route('cash.close') }}" class="text-right">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">
                    <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Fechar Caixa</button>
                </form>
            @endif
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white">
                <div class="font-semibold mb-2">Recebimentos do dia</div>
                <div class="border rounded">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600">
                                <th class="text-left p-2">Cliente</th>
                                <th class="text-left p-2">Descrição</th>
                                <th class="text-right p-2">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($receiptsPaid as $r)
                                <tr class="border-t" @if((float)$r->amount < 0) style="background-color: #fef2f2;" @endif>
                                    <td class="p-2">{{ optional($r->client)->name ?? '-' }}</td>
                                    <td class="p-2">{{ $r->description }}</td>
                                    <td class="p-2 text-right @if((float)$r->amount < 0) text-red-600 font-semibold @endif">
                                        R$ {{ number_format((float)$r->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="p-3 text-gray-500">Sem recebimentos no dia.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white">
                <div class="font-semibold mb-2">Pagamentos do dia</div>
                <div class="border rounded">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600">
                                <th class="text-left p-2">Fornecedor</th>
                                <th class="text-left p-2">Descrição</th>
                                <th class="text-right p-2">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payablesPaid as $p)
                                <tr class="border-t">
                                    <td class="p-2">{{ $p->supplier_name }}</td>
                                    <td class="p-2">{{ $p->description }}</td>
                                    <td class="p-2 text-right">R$ {{ number_format((float)$p->amount, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="p-3 text-gray-500">Sem pagamentos no dia.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white">
                <div class="font-semibold mb-2">Sangrias do dia</div>
                <div class="border rounded">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600">
                                <th class="text-left p-2">Motivo</th>
                                <th class="text-right p-2">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($withdrawals as $w)
                                <tr class="border-t">
                                    <td class="p-2">{{ $w->reason }}</td>
                                    <td class="p-2 text-right">R$ {{ number_format((float)$w->amount, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="p-3 text-gray-500">Sem sangrias no dia.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 font-semibold">
                                <td class="p-2 text-right">Total:</td>
                                <td class="p-2 text-right">R$ {{ number_format((float)$withdrawalsTotal, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div></div>
        </div>

        <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-600">Precisa lançar uma sangria do caixa?</div>
            <a href="{{ route('cash_withdrawals.index', ['date' => $date]) }}" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Sangria do Caixa</a>
        </div>
    </div>
    </div>
</x-app-layout>


