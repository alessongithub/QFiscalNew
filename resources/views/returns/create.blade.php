<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Devolução do Pedido #{{ $order->id }}</h2>
    </x-slot>
    <div class="bg-white p-4 rounded shadow">
        @if($hasIssuedNfe ?? false)
            <div class="mb-4 p-4 border border-amber-300 bg-amber-50 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="text-amber-900 flex-1">
                        <div class="font-semibold mb-1">⚠️ Pedido com NF-e Transmitida</div>
                        <div class="text-sm">
                            <p class="mb-2">Este pedido possui uma NF-e transmitida 
                                @if($nfeNote)
                                    (Nº {{ $nfeNote->numero_nfe ?? '—' }}) 
                                    @if($nfeKey)
                                        — Chave: {{ substr($nfeKey, 0, 20) }}...
                                    @endif
                                @endif
                            </p>
                            <p class="font-medium">Importante:</p>
                            <ul class="list-disc list-inside ml-2 mt-1 space-y-1">
                                <li>Após processar a devolução, será necessário emitir uma <strong>NF-e de devolução</strong> (tipo 1 ou 1A) que referencia a NF-e original.</li>
                                <li>A NF-e de devolução garante a conformidade fiscal e a rastreabilidade da operação.</li>
                                <li>O pedido não poderá ser reaberto enquanto houver NF-e transmitida.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        <form method="POST" action="{{ route('returns.store') }}" x-data="{ emitNfe: false }">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->id }}">
            <div class="mb-3">
                <input type="hidden" name="refund_type" value="refund">
                <label class="block text-sm text-gray-600 mb-1">Estorno</label>
                <div class="space-y-2">
                    <div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            Estornar recebimento agora (sai do caixa)
                        </span>
                    </div>
                    <div class="mt-2">
                        <label class="block text-xs text-gray-600 mb-1">Meio do estorno</label>
                        <select name="refund_method" class="border rounded px-10 py-2">
                            <option value="cash">Dinheiro</option>
                            <option value="card">Cartão</option>
                            <option value="pix">PIX</option>
                        </select>
                    </div>
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
                        <td class="py-2 px-2 right">
                            @php
                                $unit = strtoupper($it->unit ?? $it->unit_measure ?? $it->unity ?? 'UN');
                                $intUnits = ['UN','PAR','PCT','PC','CX','KIT','DZ','JOGO'];
                                $isIntegerUnit = in_array($unit, $intUnits, true);
                                $step = $isIntegerUnit ? '1' : '0.001';
                                $max = max(0, $it->quantity - ($already[$it->id] ?? 0));
                            @endphp
                            <input type="number"
                                   name="items[{{ $it->id }}][quantity]"
                                   min="0"
                                   max="{{ $max }}"
                                   step="{{ $step }}"
                                   class="border rounded px-2 py-1 w-32"
                                   @if($isIntegerUnit) inputmode="numeric" @endif>
                        </td>
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


