<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Vendas PDV</h2>
            @php
                $lm = (bool) config('app.limited_mode', false);
                $isFree = optional(auth()->user()->tenant?->plan)->slug === 'free';
                $disabled = ($lm || $isFree);
            @endphp
            <a href="{{ route('pos.index') }}" class="px-3 py-2 bg-green-600 text-white rounded {{ $disabled ? 'opacity-60 pointer-events-none cursor-not-allowed' : '' }}" title="{{ $disabled ? 'Indisponível no seu plano' : '' }}">Nova venda</a>
        </div>
    </x-slot>
    <div class="bg-white p-4 rounded shadow">
        <table class="min-w-full text-sm">
            <thead><tr class="text-left border-b text-gray-600"><th class="py-2 px-2">#</th><th class="py-2 px-2">Cliente</th><th class="py-2 px-2">Data</th><th class="py-2 px-2 text-right">Total</th><th class="py-2 px-2">Ações</th></tr></thead>
            <tbody>
                @foreach($orders as $o)
                <tr class="border-b">
                    <td class="py-2 px-2">{{ $o->id }}</td>
                    <td class="py-2 px-2">{{ optional($o->client)->name ?? '—' }}</td>
                    <td class="py-2 px-2">{{ optional($o->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="py-2 px-2 text-right">R$ {{ number_format($o->total_amount, 2, ',', '.') }}</td>
                    <td class="py-2 px-2 space-x-3">
                        <a class="text-blue-700" href="{{ route('pos.receipt', $o) }}" target="_blank">Recibo</a>
                        <a class="text-blue-700" href="{{ route('pos.print', $o) }}" target="_blank">Imprimir Pedido</a>
                        <a class="text-blue-700" href="{{ route('pos.print80', $o) }}" target="_blank">Imprimir 80mm</a>
                        <a class="text-green-700" href="{{ route('returns.create', ['order'=>$o->id]) }}">Devolução</a>
                        <form action="{{ route('orders.issue_nfe', $o) }}" method="POST" style="display:inline;">
                            @csrf
                            <input type="hidden" name="type" value="products">
                            <input type="hidden" name="operation_type" value="venda">
                            <input type="hidden" name="tpNF" value="1"><!-- 1=Saída -->
                            <input type="hidden" name="finNFe" value="1"><!-- 1=Normal -->
                            <input type="hidden" name="cfop" value="5102">
                            <input type="hidden" name="natOp" value="Venda de mercadoria (PDV)">
                            <button type="submit" class="text-indigo-700 {{ $disabled ? 'opacity-60 cursor-not-allowed' : '' }}" title="{{ $disabled ? 'Indisponível no seu plano' : 'Emitir NF-e deste pedido' }}" {{ $disabled ? 'disabled' : '' }}>Emitir NFe</button>
                            @if($disabled)
                                <a href="{{ route('plans.upgrade') }}" class="ml-1 text-sm text-green-700 hover:underline">Upgrade</a>
                            @endif
                        </form>
                        <form action="{{ route('orders.issue_nfce', $o) }}" method="POST" style="display:inline;" class="ml-2" onsubmit="return confirm('Emitir NFC-e deste pedido? Esta ação transmitirá a nota fiscal com modelo 65.');">
                            @csrf
                            <button type="submit" class="text-green-700 {{ $disabled ? 'opacity-60 cursor-not-allowed' : '' }}" title="{{ $disabled ? 'Indisponível no seu plano' : 'Emitir NFC-e deste pedido (PDV)' }}" {{ $disabled ? 'disabled' : '' }}>Emitir NFC-e</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $orders->links() }}</div>
    </div>
</x-app-layout>


