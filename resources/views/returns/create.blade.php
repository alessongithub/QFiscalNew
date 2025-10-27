<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Devolução do Pedido #{{ $order->id }}</h2>
    </x-slot>
    <div class="bg-white p-4 rounded shadow">
        <form method="POST" action="{{ route('returns.store') }}" x-data="{ emitNfe: false }">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->id }}">
            <div class="mb-3" x-data="{ type: 'abatement' }">
                <label class="block text-sm text-gray-600 mb-1">Como registrar financeiramente?</label>
                <div class="space-y-2">
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="refund_type" value="abatement" x-model="type">
                        <span>Abater do Contas a Receber (não altera o caixa agora)</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="refund_type" value="refund" x-model="type">
                        <span>Estornar recebimento agora (sai do caixa)</span>
                    </label>
                    <div class="ml-6" x-show="type==='refund'">
                        <label class="block text-xs text-gray-600 mb-1">Meio do estorno</label>
                        <select name="refund_method" class="border rounded px-3 py-2">
                            <option value="cash">Dinheiro</option>
                            <option value="card">Cartão</option>
                            <option value="pix">PIX</option>
                        </select>
                    </div>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="refund_type" value="credit" x-model="type">
                        <span>Gerar crédito ao cliente (usar em compras futuras)</span>
                    </label>
                </div>
            </div>
            <table class="min-w-full text-sm">
                <thead><tr class="text-left border-b text-gray-600"><th class="py-2 px-2">Produto</th><th class="py-2 px-2 right">Vendido</th><th class="py-2 px-2 right">Devolvido</th><th class="py-2 px-2 right">Devolver</th><th class="py-2 px-2 right">Unit</th></tr></thead>
                <tbody>
                    @foreach($items as $it)
                    <tr class="border-b">
                        <td class="py-2 px-2">{{ $it->name }}</td>
                        <td class="py-2 px-2 right">{{ number_format($it->quantity,3,',','.') }}</td>
                        <td class="py-2 px-2 right">{{ number_format($already[$it->id] ?? 0,3,',','.') }}</td>
                        <td class="py-2 px-2 right"><input type="number" name="items[{{ $it->id }}][quantity]" min="0" max="{{ max(0, $it->quantity - ($already[$it->id] ?? 0)) }}" step="0.001" class="border rounded px-2 py-1 w-32"></td>
                        <td class="py-2 px-2 right">R$ {{ number_format($it->unit_price,2,',','.') }}</td>
                        <input type="hidden" name="items[{{ $it->id }}][order_item_id]" value="{{ $it->id }}">
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4 text-right">
                <label class="text-sm text-gray-700 mr-3 inline-flex items-center gap-2">
                    <input type="checkbox" x-model="emitNfe">
                    <span>Emitir NF-e de devolução agora</span>
                </label>
                <button class="px-4 py-2 bg-green-600 text-white rounded">Registrar Devolução</button>
            </div>
        </form>
    </div>
</x-app-layout>


