<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Definir Pagamento do Pedido {{ $order->number }}</h2>
    </x-slot>

    <div class="bg-white p-6 rounded shadow max-w-3xl">
        <form action="{{ route('orders.fulfill', $order) }}" method="POST" class="grid grid-cols-12 gap-3 items-end">
            @csrf
            <div class="col-span-12">
                <p class="text-sm text-gray-600">Total do pedido: <strong>R$ {{ number_format($order->total_amount, 2, ',', '.') }}</strong></p>
            </div>
            @if(!empty($hasPhysicalProducts) && $hasPhysicalProducts)
            <div class="col-span-12">
                <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-3 py-2">Este pedido contém produtos físicos. É necessário informar o frete para finalizar e faturar.</p>
            </div>
            @endif

            <div class="col-span-12"><h3 class="font-semibold">Frete</h3></div>
            <div class="col-span-3">
                <label class="block text-xs text-gray-600">Modalidade</label>
                <select name="freight_mode" class="w-full border rounded p-2">
                    <option value="0">0 - Emitente (CIF)</option>
                    <option value="1">1 - Destinatário (FOB)</option>
                    <option value="2">2 - Terceiros</option>
                    <option value="9" @if(!$hasPhysicalProducts) selected @endif>9 - Sem frete</option>
                </select>
            </div>
            <div class="col-span-3">
                <label class="block text-xs text-gray-600">Responsável</label>
                <select name="freight_payer" class="w-full border rounded p-2">
                    <option value="company">Empresa</option>
                    <option value="buyer">Comprador</option>
                </select>
            </div>
            <div class="col-span-6">
                <label class="block text-xs text-gray-600">Obs. do frete</label>
                <input type="text" name="freight_obs" class="w-full border rounded p-2">
            </div>
            <div class="col-span-12"><hr class="my-2"></div>
            <div class="col-span-12"><h3 class="font-semibold">Pagamento</h3></div>
            <div class="col-span-4">
                <label class="block text-xs text-gray-600">Forma</label>
                <select name="payment_type" class="w-full border rounded p-2" id="payType" onchange="onPayTypeChange()">
                    <option value="immediate">À vista</option>
                    <option value="invoice">Faturado</option>
                    <option value="mixed">Misto</option>
                </select>
            </div>
            <div class="col-span-4" id="entryWrap" style="display:none;">
                <label class="block text-xs text-gray-600">Entrada (R$)</label>
                <input type="number" step="0.01" name="entry_amount" class="w-full border rounded p-2">
            </div>
            <div class="col-span-4" id="instWrap" style="display:none;">
                <label class="block text-xs text-gray-600">Nº parcelas</label>
                <input type="number" min="1" max="36" name="installments" class="w-full border rounded p-2">
            </div>
            <div class="col-span-6" id="firstDueWrap" style="display:none;">
                <label class="block text-xs text-gray-600">1º vencimento</label>
                <input type="date" name="first_due_date" class="w-full border rounded p-2" value="{{ now()->toDateString() }}">
            </div>
            <div class="col-span-6" id="intervalWrap" style="display:none;">
                <label class="block text-xs text-gray-600">Intervalo (dias)</label>
                <input type="number" min="1" max="120" name="interval_days" class="w-full border rounded p-2" value="30">
            </div>
            <div class="col-span-12 text-right mt-2">
                <button class="px-4 py-2 bg-green-700 text-white rounded hover:bg-green-800" onclick="return confirm('Finalizar pedido e gerar pagamentos?');">Finalizar</button>
                <a href="{{ route('orders.index') }}" class="ml-2 text-gray-700">Cancelar</a>
            </div>
        </form>
    </div>
    <script>
        function onPayTypeChange(){
            var t = document.getElementById('payType').value;
            document.getElementById('entryWrap').style.display = (t==='mixed') ? '' : 'none';
            var showSchedule = (t!=='immediate');
            document.getElementById('instWrap').style.display = showSchedule ? '' : 'none';
            document.getElementById('firstDueWrap').style.display = showSchedule ? '' : 'none';
            document.getElementById('intervalWrap').style.display = showSchedule ? '' : 'none';
        }
        onPayTypeChange();
    </script>
</x-app-layout>


