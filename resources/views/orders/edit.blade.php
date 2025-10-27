<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Pedido</h2>
                    <p class="text-sm text-gray-500">Pedido #{{ $order->number }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <a href="#frete" class="inline-flex items-center px-3 py-2 bg-amber-100 text-amber-800 rounded-lg hover:bg-amber-200 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    Frete
                </a>
                <a href="{{ route('orders.print', $order) }}" target="_blank" class="inline-flex items-center px-3 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimir
                </a>
                <a href="{{ route('orders.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>
    </x-slot>

    <!-- Toast notifications -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
            const icon = type === 'success' ? 
                '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' :
                type === 'error' ?
                '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>' :
                '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"></path></svg>';
            
            toast.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 transform translate-x-full transition-transform duration-300`;
            toast.innerHTML = `
                ${icon}
                <span class="font-medium">${message}</span>
            `;
            
            container.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, 5000);
        }

        // Show toasts for session messages
    @if(session('success'))
            showToast('{{ session('success') }}', 'success');
    @endif
    @if(session('error'))
            showToast('{{ session('error') }}', 'error');
    @endif
    @if(session('info'))
            showToast('{{ session('info') }}', 'info');
    @endif
    @if($errors->any())
        @php $errs = $errors->all(); @endphp
        @foreach($errs as $error)
            showToast('{{ $error }}', 'error');
        @endforeach
        console.log('[Form Errors]', @json($errs));
    @endif
    </script>

    @php
        $latestNfe = $order->latestNfeNoteCompat ?? null;
        $nfeStatus = strtolower((string) ($latestNfe->status ?? ''));
        $nfeLocked = in_array($nfeStatus, ['emitted','transmitida']);
        $freightOnly = request()->boolean('freight_only');
        $isOrderLocked = ($order->status==='fulfilled') || !empty($order->nfe_issued_at) || $nfeLocked;
        $freightPaymentLocked = !empty($order->nfe_issued_at) || $nfeLocked; // permitir ajuste em 'fulfilled' se ainda não emitiu NFe
        
        // Definir estratégia de cancelamento para todos os casos
        $refundStrategy = \App\Models\Setting::get('orders.cancel.refund_strategy', \App\Models\Setting::getGlobal('orders.cancel.refund_strategy_default','refund_immediate'));
        $strategyExplanation = $refundStrategy === 'refund_immediate'
            ? 'Este cancelamento irá gerar estorno no Caixa do Dia para valores já recebidos e cancelará os títulos em aberto. Os itens serão devolvidos ao estoque.'
            : 'Este cancelamento irá compensar os títulos em aberto (abatimento). Nenhum movimento de caixa será gerado agora. Os itens serão devolvidos ao estoque.';
    @endphp

    @if(in_array($nfeStatus, ['cancelled','cancelada']))
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 p-4 border border-amber-300 bg-amber-50 rounded">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                    </svg>
                    <div class="text-amber-900">
                        <div class="font-semibold">NF-e cancelada</div>
                        <div class="text-sm mt-1">Você pode reabrir o pedido para correções e emitir uma nova NF-e, ou cancelar o pedido definitivamente (estoque e financeiro serão estornados).</div>
                        <div class="mt-3 flex gap-2">
                            @if(auth()->user()->hasPermission('orders.edit') && $order->status==='fulfilled')
                            @php $canShowReopen = $canShowReopen ?? (($order->status==='fulfilled') && !$order->has_successful_nfe); @endphp
                            @if($canShowReopen)
                                <button type="button" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded" onclick="document.getElementById('reopenModalDetail').classList.remove('hidden')">Reabrir pedido</button>
                            @endif
                            <div id="reopenModalDetail" class="fixed inset-0 bg-black/30 hidden items-center justify-center z-50">
                                <div class="bg-white rounded shadow p-4 w-full max-w-md">
                                    <h3 class="font-semibold mb-2">Reabrir pedido #{{ $order->number }}</h3>
                                    <form action="{{ route('orders.reopen', $order) }}" method="POST">
                                        @csrf
                                        <div class="mb-2">
                                            <label class="block text-xs text-gray-600">Justificativa</label>
                                            <textarea name="justification" class="w-full border rounded p-2" required minlength="10" maxlength="500" placeholder="Descreva o motivo"></textarea>
                                        </div>
                                        <div class="mb-2">
                                            <label class="inline-flex items-center"><input type="checkbox" name="estornar" value="1" class="mr-2">Estornar financeiro deste pedido agora</label>
                                        </div>
                                        <div class="text-right space-x-2 mt-3">
                                            <button type="button" class="px-3 py-1 border rounded" onclick="document.getElementById('reopenModalDetail').classList.add('hidden')">Cancelar</button>
                                            <button type="submit" class="px-3 py-1 bg-amber-600 text-white rounded">Reabrir</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif
                            @if(auth()->user()->hasPermission('returns.create'))
                            <a href="{{ route('returns.create', ['order' => $order->id]) }}" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded">Gerar Devolução</a>
                            @endif
                            @if(auth()->user()->hasPermission('orders.delete'))
                            <button type="button" onclick="alert('Botão clicado!'); openCancelModal();" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded">
                                Cancelar pedido
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden" x-data="orderEditItems()">
                
                <!-- Header do Card -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Informações do Pedido</h3>
                            <p class="text-blue-100 text-sm">Edite os dados básicos do pedido</p>
                        </div>
                    </div>
                </div>

                <!-- Formulário -->
                <div class="p-6">
                    <!-- Informações básicas -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-6 mb-6">
        <form id="orderEditMainForm" action="{{ route('orders.update', $order) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
            @php
                $isConsumerFinal = optional($order->client)->name === 'Consumidor Final' || optional($order->client)->consumidor_final === 'S';
            @endphp
            <fieldset @disabled($isOrderLocked && !$isConsumerFinal)>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        Cliente
                                    </span>
                                </label>
                                <select name="client_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required @disabled((!$isConsumerFinal) || !auth()->user()->hasPermission('orders.edit'))>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" @selected($order->client_id===$c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                        </svg>
                                        Número
                                    </span>
                                </label>
                                <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-500" value="{{ $order->number }}" readonly>
                </div>
            </div>
                        
            <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    Título
                                </span>
                            </label>
                            <input type="text" name="title" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" value="{{ $order->title }}" required @disabled(true)>
            </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Status
                                    </span>
                                </label>
                                <select name="status" id="orderStatusSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" @disabled(true)>
                        <option value="open" @selected($order->status==='open')>Aberto</option>
                        <option value="canceled" @selected($order->status==='canceled')>Cancelado</option>
                        <option value="fulfilled" disabled @selected($order->status==='fulfilled')>Finalizado (use o botão abaixo)</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                            Desconto Total (R$)
                        </span>
                    </label>
                    <input type="number" step="0.01" min="0" name="discount_total_override" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-right"
                           value="{{ number_format((float)($order->discount_total ?? 0), 2, '.', '') }}"
                           placeholder="0,00"
                           @input="document.getElementById('discount_total_override_inline').value = $event.target.value; document.getElementById('discount_total_override_inline').dispatchEvent(new Event('input'));">
                </div>

            </fieldset>
            @if($isConsumerFinal)
                <input type="hidden" name="client_only" value="1">
            @endif
        </form>
            </fieldset>
        <!-- Observações removidas daqui; serão definidas apenas no modal de emissão de NF-e -->
                    </div>
                </div>
                <!-- Seção de Itens -->
                <div class="mb-8">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Itens do Pedido</h3>
                            <p class="text-sm text-gray-500">Gerencie os produtos e serviços do pedido</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
                        <div class="p-6 border-b border-gray-200">
                            <form action="{{ route('orders.add_item', $order) }}" method="POST" class="space-y-4" @submit.prevent>
                    @csrf
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                    <div class="md:col-span-4 relative" @click.away="suggestions=[]">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                </svg>
                                                Produto/Serviço
                                            </span>
                                        </label>
                                        <input type="hidden" name="product_id" x-model="newProductId">
                                        <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Buscar produto ou serviço..." x-model="newSearch" @input.debounce.300ms="search()">
                                        <div class="absolute z-10 bg-white border border-gray-300 rounded-lg mt-1 w-full max-h-64 overflow-auto shadow-lg" x-show="suggestions.length">
                                            <template x-for="s in suggestions" :key="s.id">
                                                <div class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0" @click="choose(s)">
                                                    <div class="flex items-center justify-between">
                                                        <div class="font-medium text-gray-900" x-text="s.name"></div>
                                                        <template x-if="s.type !== 'service' && s.balance != null">
                                                            <div class="text-xs text-gray-500">Estoque: <span x-text="Number(s.balance||0).toLocaleString('pt-BR', {minimumFractionDigits: 3})"></span></div>
                                                        </template>
                                                    </div>
                                                    <div class="text-sm text-gray-500" x-text="`${s.unit || ''} • R$ ${Number(s.price||0).toFixed(2)}`"></div>
                                                </div>
                                            </template>
                                        </div>
                </div>
                                    
                                    <div class="md:col-span-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                                </svg>
                                                Qtd
                                            </span>
                                        </label>
                                        <input type="number" :step="getQuantityStep()" :min="getQuantityMin()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" x-model.number="newQty" name="quantity" required>
                </div>
                                    
                                    <div class="md:col-span-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                                </svg>
                                                UN
                                            </span>
                                        </label>
                                        <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-500" x-model="newUnit" readonly>
                </div>
                                    
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                                </svg>
                                                V.Unit
                                            </span>
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 text-sm">R$</span>
                </div>
                                            <input type="text" class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-right font-medium" :value="Number(newPrice||0).toFixed(2)" readonly>
                                            <input id="new_item_price_hidden" type="hidden" x-model.number="newPrice" name="_new_price_hidden">
                </div>
                                    </div>
                                    
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                                </svg>
                                                Desc.
                                            </span>
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 text-sm">R$</span>
                                            </div>
                                            <input id="new_item_discount" type="number" step="0.01" min="0" class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-right" x-model.number="newDiscount" name="discount_value">
                                        </div>
                                    </div>
                                    
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                                </svg>
                                                V.Total
                                            </span>
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 text-sm">R$</span>
                                            </div>
                                            <input type="text" class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-right font-semibold" :value="(Math.max(0, Number(newQty||0)*Number(newPrice||0) - Number(newDiscount||0))).toFixed(2)" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                @if($order->status !== 'fulfilled' && empty($order->nfe_issued_at))
                                    <div class="text-right">
                        @if($order->status !== 'fulfilled' && empty($order->nfe_issued_at))
                                            <button class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all transform hover:scale-105" @click.prevent="$event.target.closest('form').submit()">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                Adicionar Item
                            </button>
                        @else
                                            <button type="button" class="inline-flex items-center px-6 py-3 bg-gray-300 text-gray-600 rounded-lg cursor-not-allowed" title="Pedido finalizado. Para editar, estorne o pedido." disabled>
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                Adicionar Item
                            </button>
                        @endif
                    </div>
                @endif
            </form>
                        </div>

                        <div class="w-full">
                            <table class="w-full">
                                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">Item</th>
                                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">Qtd</th>
                                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">UN</th>
                                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">V.Unit</th>
                                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">Desc. (R$)</th>
                                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">Total</th>
                                        <th scope="col" class="px-6 py-4 text-right text-sm font-semibold text-gray-700 uppercase tracking-wider">Ações</th>
                </tr>
                </thead>
                                <tbody class="bg-white">
                @foreach($items as $it)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" data-product-in-row data-ncm="{{ optional($it->product)->ncm }}" data-cfop="{{ optional($it->product)->cfop }}" data-cst="{{ optional($it->product)->cst ?? optional($it->product)->cst_icms ?? '' }}" data-aliq-icms="{{ optional($it->product)->aliquota_icms ?? '' }}" data-aliq-pis="{{ optional($it->product)->aliquota_pis ?? '' }}" data-aliq-cofins="{{ optional($it->product)->aliquota_cofins ?? '' }}" data-incomplete="{{ (empty(optional($it->product)->ncm) || strlen((string)optional($it->product)->ncm) < 8 || empty(optional($it->product)->cfop) || (float)(optional($it->product)->aliquota_icms ?? 0) <= 0 || (float)(optional($it->product)->aliquota_pis ?? 0) <= 0 || (float)(optional($it->product)->aliquota_cofins ?? 0) <= 0 || (empty(optional($it->product)->cst) && empty(optional($it->product)->cst_icms))) ? '1' : '0' }}">{{ $it->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ number_format($it->quantity, 3, ',', '.') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $it->unit }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">R$ {{ number_format($it->unit_price, 2, ',', '.') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">R$ {{ number_format((float)($it->discount_value ?? 0), 2, ',', '.') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">R$ {{ number_format((float)$it->line_total, 2, ',', '.') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if($order->status !== 'fulfilled' && empty($order->nfe_issued_at))
                                <form action="{{ route('orders.remove_item', [$order, $it]) }}" method="POST" class="inline" onsubmit="return confirm('Remover item?')">
                                @csrf @method('DELETE')
                                                        <button class="inline-flex items-center px-3 py-1.5 text-sm text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                            Remover
                                                        </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>

                        <div class="px-6 py-6 bg-gradient-to-r from-gray-50 to-gray-100 border-t border-gray-200">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center space-x-3">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-700">Status:</span>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                        @if($order->status === 'open') bg-yellow-100 text-yellow-800
                                        @elseif($order->status === 'fulfilled') bg-green-100 text-green-800
                                        @else bg-red-100 text-red-800 @endif">
                                            @if($order->status === 'open')
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            @elseif($order->status === 'fulfilled')
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            @endif
                                        {{ ['open'=>'Aberto','fulfilled'=>'Finalizado','canceled'=>'Cancelado'][$order->status] ?? $order->status }}
                                    </span>
                                </div>
                            </div>
                            
                                <div class="text-right space-y-3" x-data="totalsInline()">
                                    <div class="inline-flex items-center gap-3 bg-white px-4 py-3 rounded-lg shadow-sm border border-gray-200">
                                        <label for="discount_total_override_inline" class="text-sm font-medium text-gray-700">Desconto Total</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 text-sm">R$</span>
                                            </div>
                                            <input form="orderEditMainForm" type="number" step="0.01" min="0" name="discount_total_override" id="discount_total_override_inline"
                                                   class="w-32 pl-8 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-right"
                                                   x-model.number="headerDiscount"
                                                   @input="document.querySelector('input[name=discount_total_override]').value = $event.target.value"
                                                   value="{{ number_format((float)($order->discount_total ?? 0), 2, '.', '') }}">
                                        </div>
                        </div>
                        
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <div class="flex justify-between">
                                            <span>Bruto:</span>
                                            <span class="font-medium">R$ <span x-text="grossText()"></span></span>
                    </div>
                                        <div class="flex justify-between">
                                            <span>Desc. itens:</span>
                                            <span class="font-medium text-red-600">R$ <span x-text="itemsDiscountText()"></span></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Desc. total:</span>
                                            <span class="font-medium text-red-600">R$ <span x-text="headerDiscountText()"></span></span>
                                        </div>
                                    </div>
                                    
                                    <div class="text-xl font-bold text-gray-900 bg-white px-4 py-3 rounded-lg shadow-sm border border-gray-200">
                                        Líquido: R$ <span x-text="netText()"></span>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                        
                    </div>
                    <div class="text-right mt-6">
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('orders.index') }}" class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Cancelar
                            </a>
    <button form="orderEditMainForm" type="submit"
                                class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all transform hover:scale-105"
        @disabled((($order->status==='fulfilled') && !$isConsumerFinal) || !empty($order->nfe_issued_at) || $nfeLocked)>
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Salvar Alterações
    </button>
                        </div>
</div>

                </div>

                <!-- Seção de Frete e Pagamento -->
                @php
                    $latestNfe = $order->latestNfeNoteCompat ?? null;
                    $nfeStatus = strtolower((string) ($latestNfe->status ?? ''));
                    $nfeLocked = in_array($nfeStatus, ['emitted','transmitida']);
                    $freightOnly = request()->boolean('freight_only');
                @endphp
                <div id="frete" class="mb-8" x-data="freightForm()">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Frete e Pagamento</h3>
                            <p class="text-sm text-gray-500">Configure o frete e informações de pagamento</p>
                        </div>
                    </div>
                    
                @if(!empty($hasPhysicalProducts) && $hasPhysicalProducts)
                        <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-amber-700">Este pedido contém produtos físicos. É necessário informar o frete para finalizar e faturar.</p>
                                </div>
                            </div>
                        </div>
                @endif
                    
                @if(!empty($order->nfe_issued_at) || $nfeLocked)
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">NF-e transmitida. Frete e pagamento não podem mais ser alterados.</p>
                                </div>
                            </div>
                        </div>
                @endif
                    
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                <form action="{{ route('orders.fulfill', $order) }}" method="POST" class="grid grid-cols-12 gap-3 items-end" onsubmit="return confirm('Confirmar frete e forma de pagamento? O pedido será finalizado e as movimentações geradas. Você ainda poderá editar frete e pagamento enquanto a NF-e não for emitida.');">
                    @csrf
                    @if (false)
                    <div class="col-span-12"></div>
                    @endif
                    <div class="col-span-3">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Modalidade</label>
                        <select name="freight_mode" x-model.number="mode" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" @disabled($freightPaymentLocked)>
                            <option value="0">0 - Emitente (CIF)</option>
                            <option value="1">1 - Destinatário (FOB)</option>
                            <option value="2">2 - Terceiros</option>
                            <option value="9">9 - Sem frete</option>
                        </select>
                    </div>
                    <div class="col-span-3">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Responsável pelo frete</label>
                        <select name="freight_payer" x-model="payer" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" @disabled($freightPaymentLocked)>
                            <option value="company">Empresa</option>
                            <option value="buyer">Comprador</option>
                        </select>
                    </div>
                    <div class="col-span-4" x-show="requiresCarrier">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Transportadora</label>
                        <select name="carrier_id" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" :required="requiresCarrier" @disabled($freightPaymentLocked)>
                            <option value="">— Selecionar —</option>
                            @php $carriers = \App\Models\Carrier::where('tenant_id', auth()->user()->tenant_id)->where('active',1)->orderBy('name')->get(); @endphp
                            @foreach($carriers as $c)
                                <option value="{{ $c->id }}" @selected($order->carrier_id===$c->id)>{{ $c->name }} ({{ $c->cnpj }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2" x-show="requiresFreightValue">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Valor do frete</label>
                        <input type="number" step="0.01" name="freight_cost" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" :required="requiresFreightValue" value="{{ $order->freight_cost }}" @disabled($freightPaymentLocked)>
                    </div>
                    <div class="col-span-3" x-show="mode!==9">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Obs. do frete</label>
                        <input type="text" name="freight_obs" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" value="{{ $order->freight_obs }}" placeholder="Informações adicionais" @disabled($freightPaymentLocked)>
                    </div>
                    <div class="col-span-12"><hr class="my-2"></div>
                    <div class="col-span-12"><hr class="my-2"></div>
                    <div class="col-span-2" x-show="mode!==9">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Qtd. Volumes</label>
                        <input type="number" min="1" name="volume_qtd" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" value="{{ $order->volume_qtd }}" @disabled(!empty($order->nfe_issued_at) || $nfeLocked)>
                    </div>
                    <div class="col-span-3" x-show="mode!==9">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Espécie dos Volumes</label>
                        <input type="text" name="volume_especie" maxlength="50" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" value="{{ $order->volume_especie }}" placeholder="Caixas, Paletes..." @disabled(!empty($order->nfe_issued_at) || $nfeLocked)>
                    </div>
                    <div class="col-span-2" x-show="mode!==9">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Peso Bruto (kg)</label>
                        <input type="number" step="0.001" min="0" name="peso_bruto" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" value="{{ $order->peso_bruto }}" @disabled(!empty($order->nfe_issued_at) || $nfeLocked)>
                    </div>
                    <div class="col-span-2" x-show="mode!==9">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Peso Líquido (kg)</label>
                        <input type="number" step="0.001" min="0" name="peso_liquido" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" value="{{ $order->peso_liquido }}" @disabled(!empty($order->nfe_issued_at) || $nfeLocked)>
                    </div>
                    <div class="col-span-1" x-show="mode!==9">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Seguro (R$)</label>
                        <input type="number" step="0.01" min="0" name="valor_seguro" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" value="{{ $order->valor_seguro }}" @disabled(!empty($order->nfe_issued_at) || $nfeLocked)>
                    </div>
                    <div class="col-span-2" x-show="mode!==9">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Outras Despesas (R$)</label>
                        <input type="number" step="0.01" min="0" name="outras_despesas" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" value="{{ $order->outras_despesas }}" @disabled(!empty($order->nfe_issued_at) || $nfeLocked)>
                    </div>
                    <div class="col-span-12"><hr class="my-2"></div>
                    <div class="col-span-4">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Forma de pagamento</label>
                        <select name="payment_type" x-model="pay.type" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" @disabled($freightPaymentLocked)>
                            <option value="immediate">À vista (entra no caixa hoje)</option>
                            <option value="invoice">Faturado (contas a receber)</option>
                            <option value="mixed">Misto (entrada + parcelas)</option>
                        </select>
                    </div>
                    <div class="col-span-2" x-show="pay.type==='immediate'">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Meio (à vista)</label>
                        <select name="immediate_method" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" @disabled($freightPaymentLocked) x-model="pay.immediateMethod">
                            <option value="cash">Dinheiro</option>
                            <option value="pix">PIX</option>
                            <option value="card">Cartão</option>
                        </select>
                    </div>
                    <div class="col-span-2" x-show="pay.type==='mixed'">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Entrada (R$)</label>
                        <input type="number" step="0.01" name="entry_amount" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" x-model.number="pay.entry" @disabled($freightPaymentLocked)>
                    </div>
                    <div class="col-span-2" x-show="pay.type==='mixed'">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Meio (entrada)</label>
                        <select name="entry_method" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" @disabled($freightPaymentLocked) x-model="pay.entryMethod">
                            <option value="cash">Dinheiro</option>
                            <option value="pix">PIX</option>
                            <option value="card">Cartão</option>
                        </select>
                    </div>
                    <div class="col-span-2" x-show="pay.type!=='immediate'">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Nº parcelas</label>
                        <input type="number" min="1" max="36" name="installments" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" x-model.number="pay.installments" @disabled($freightPaymentLocked)>
                    </div>
                    
                    <div class="col-span-3" x-show="pay.type!=='immediate'">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Meio (parcelas)</label>
                        <select name="installment_method" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" @disabled($freightPaymentLocked) x-model="pay.installmentMethod">
                            <option value="boleto">Boleto</option>
                            <option value="pix">PIX</option>
                            <option value="card">Cartão</option>
                        </select>
                    </div>
                    
                    <div class="col-span-12">
                        <template x-if="pay.type!=='immediate' && pay.installments>=1">
                            <div class="mb-2">
                                <div class="text-xs text-gray-600 dark:text-gray-400 font-semibold mb-1">Prévia das parcelas</div>
                                <div class="grid grid-cols-12 gap-2 items-end">
                                    <template x-for="i in Math.min(pay.installments, 24)" :key="i">
                                        <div class="col-span-4 flex items-center space-x-2 mb-2">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 w-8">#<span x-text="i"></span></span>
                                            <input type="date" :name="`schedule[`+i+`][due_date]`" class="border rounded p-1 text-sm" :value="computeDueDate(i)" />
                                            <input type="number" step="0.01" :name="`schedule[`+i+`][amount]`" class="border rounded p-1 text-sm w-28" :value="computeInstallmentAmount(i)" />
                                        </div>
                                    </template>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Você pode ajustar valores e datas antes de finalizar. A soma das parcelas deve bater com o valor <span x-text="remainingText()"></span>.</div>
                            </div>
                        </template>
                    </div>
                    <div class="col-span-12 flex items-center justify-between mt-2">
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        @if(!empty($canIssueNfe) && $canIssueNfe)
                            <span class="text-emerald-700">NF-e liberada para emissão.</span>
                        @else
                            <span class="text-amber-700">NF-e bloqueada até definir pagamento e finalizar pedido.</span>
                        @endif
                    </div>
                        @if(empty($order->nfe_issued_at))
                            <button type="submit" class="px-4 py-2 bg-blue-700 text-white rounded hover:bg-blue-800">Finalizar pedido</button>
                        @else
                            <button type="button" class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded cursor-not-allowed" title="Pedido finalizado. Para editar, estorne o pedido." disabled>Finalizar pedido</button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Seção de Auditoria -->
            <div class="mt-10">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="p-2 bg-indigo-100 rounded-lg">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Auditoria</h3>
                        <p class="text-sm text-gray-500">Registros financeiros e operacionais do pedido</p>
                    </div>
                </div>
                
                <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-white">Registros de Auditoria</h4>
                                <p class="text-indigo-100 text-sm">Contas a receber, estoque, NF-e e devoluções</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="p-2 bg-green-100 rounded-lg">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                        </svg>
                                    </div>
                                    <h4 class="font-semibold text-gray-900">Títulos (Contas a Receber)</h4>
                                </div>
                        @php $receivables = \App\Models\Receivable::where('tenant_id', auth()->user()->tenant_id)->where('order_id', $order->id)->orderByDesc('id')->limit(50)->get(); @endphp
                        @if($receivables->count())
                                    <div class="text-sm space-y-3 max-h-64 overflow-auto">
                                @foreach($receivables as $r)
                                            <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-100">
                                        <div>
                                                    <div class="font-medium text-gray-900">{{ $r->description }}</div>
                                                    <div class="text-gray-600 text-xs">{{ ['paid'=>'Pago','open'=>'Em aberto','partial'=>'Parcial','canceled'=>'Cancelado'][$r->status] ?? $r->status }} • R$ {{ number_format($r->amount,2,',','.') }}</div>
                                        </div>
                                                <div class="text-gray-500 text-xs">{{ optional($r->due_date)->format('d/m/Y') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                                    <div class="text-sm text-gray-500 p-4 text-center">Sem registros.</div>
                        @endif
                    </div>
                            
                            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="p-2 bg-blue-100 rounded-lg">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <h4 class="font-semibold text-gray-900">Movimentos de Estoque</h4>
                                </div>
                        @php $stock = \App\Models\StockMovement::where('tenant_id', auth()->user()->tenant_id)->where('document','like', '%Pedido #'.$order->number.'%')->orderByDesc('id')->limit(100)->get(); @endphp
                        @if($stock->count())
                                    <div class="text-sm space-y-3 max-h-64 overflow-auto">
                                @foreach($stock as $s)
                                            <div class="p-3 bg-white rounded-lg border border-gray-100">
                                                <div class="font-medium text-gray-900">{{ $s->type === 'entry' ? 'Entrada' : 'Saída' }} • {{ number_format($s->quantity,3,',','.') }} un</div>
                                                <div class="text-gray-600 text-xs">{{ $s->document }} — {{ $s->note }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                                    <div class="text-sm text-gray-500 p-4 text-center">Sem registros.</div>
                        @endif
                    </div>
                            
                            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="p-2 bg-purple-100 rounded-lg">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <h4 class="font-semibold text-gray-900">NF-e Relacionadas</h4>
                                </div>
                        @php 
                            $nfes = collect();
                            try {
                                $hasOrderId = \Illuminate\Support\Facades\Schema::hasColumn('nfe_notes', 'order_id');
                                $q = \App\Models\NfeNote::where('tenant_id', auth()->user()->tenant_id);
                                if ($hasOrderId) {
                                    $q->where(function($qq) use($order){ $qq->where('order_id',$order->id)->orWhere('numero_pedido',(string)$order->number); });
                                } else {
                                    $q->where('numero_pedido', (string)$order->number);
                                }
                                $nfes = $q->orderByDesc('id')->limit(50)->get();
                            } catch (\Throwable $e) { $nfes = collect(); }
                            // Funções de fallback
                            $extractNum = function($note) {
                                $num = $note->numero_nfe ?? null;
                                if ($num) return $num;
                                $resp = (array)($note->response_received ?? []);
                                return $resp['nNF'] ?? $resp['numero'] ?? null;
                            };
                            $extractKey = function($note) {
                                $k = $note->chave_acesso ?? $note->chave_nfe ?? '';
                                if ($k) return $k;
                                $resp = (array)($note->response_received ?? []);
                                $cand = $resp['chave_acesso'] ?? $resp['chNFe'] ?? $resp['chave'] ?? '';
                                if ($cand) return $cand;
                                $p = (string)($note->xml_path ?? $note->arquivo_xml ?? '');
                                if ($p !== '' && preg_match('/(\d{44})-nfe\.xml$/', $p, $m)) { return $m[1]; }
                                return '';
                            };
                        @endphp
                        @if($nfes->count())
                                    <div class="text-sm space-y-3 max-h-64 overflow-auto">
                                @foreach($nfes as $n)
                                            <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-100">
                                        <div>
                                                    <div class="font-medium text-gray-900">NFe {{ $extractNum($n) ?: '—' }} — {{ ['emitted'=>'Transmitida','pending'=>'Pendente','error'=>'Erro','cancelled'=>'Cancelada'][$n->status] ?? ucfirst($n->status) }}</div>
                                            @php $ch = $extractKey($n); @endphp
                                                    <div class="text-gray-600 text-xs font-mono">{{ $ch ?: '—' }}</div>
                                        </div>
                                                <a class="text-blue-600 hover:text-blue-800 hover:underline text-sm font-medium" href="{{ route('nfe.show', $n) }}">Abrir</a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                                    <div class="text-sm text-gray-500 p-4 text-center">Sem registros.</div>
                        @endif
                    </div>
                            
                            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="p-2 bg-orange-100 rounded-lg">
                                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m5 3v6a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h2m0 0V3a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                        </svg>
                                    </div>
                                    <h4 class="font-semibold text-gray-900">Devoluções</h4>
                                </div>
                        @php $returns = \App\Models\ReturnModel::where('tenant_id', auth()->user()->tenant_id)->where('order_id', $order->id)->orderByDesc('id')->limit(50)->get(); @endphp
                        @if($returns->count())
                                    <div class="text-sm space-y-3 max-h-64 overflow-auto">
                                @foreach($returns as $rv)
                                            <div class="p-3 bg-white rounded-lg border border-gray-100">
                                                <div class="font-medium text-gray-900">#{{ $rv->id }} • R$ {{ number_format($rv->total_refund,2,',','.') }}</div>
                                                <div class="text-gray-600 text-xs">{{ optional($rv->created_at)->format('d/m/Y H:i') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                                    <div class="text-sm text-gray-500 p-4 text-center">Sem registros.</div>
                        @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seção de Histórico de Atividades -->
            @php
                $activities = [];
                $filterUser = request('act_user');
                $filterText = request('act_q');
                $filterFrom = request('act_from');
                $filterTo = request('act_to');
                $perPage = (int) request('act_per_page', 10);
                if ($perPage < 5) { $perPage = 5; }
                if ($perPage > 100) { $perPage = 100; }
                try {
                    // Usar nossa auditoria personalizada (OrderAudit)
                    $q = \App\Models\OrderAudit::where('order_id', $order->id)
                        ->where('action', '!=', 'canceled'); // Não mostrar cancelamentos na edição
                    if ($filterUser) { $q->where('user_id', (int) $filterUser); }
                    if ($filterText) { $q->where('notes','like','%'.$filterText.'%'); }
                    if ($filterFrom) { $q->whereDate('created_at', '>=', $filterFrom); }
                    if ($filterTo) { $q->whereDate('created_at', '<=', $filterTo); }
                    $activities = $q->with('user')->orderByDesc('id')->paginate($perPage)->appends(request()->only(['act_user','act_q','act_from','act_to','act_per_page']));
                } catch (\Throwable $e) { $activities = []; }
                // Carrega usuários possíveis
                $actUsers = collect();
                try {
                    $actUsers = \App\Models\User::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get(['id','name']);
                } catch (\Throwable $e) {}
            @endphp
            <div class="mt-10">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="p-2 bg-teal-100 rounded-lg">
                        <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Histórico de Atividades</h3>
                        <p class="text-sm text-gray-500">Log de ações e alterações realizadas no pedido</p>
                    </div>
                </div>
                
                <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                    <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-white">Log de Atividades</h4>
                                <p class="text-teal-100 text-sm">Acompanhe todas as ações realizadas no pedido</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <form method="GET" class="mb-6">
                    <input type="hidden" name="_tab" value="history">
                            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="p-2 bg-gray-100 rounded-lg">
                                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"/>
                                        </svg>
                                    </div>
                                    <h5 class="font-semibold text-gray-900">Filtros de Busca</h5>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Usuário</label>
                                        <select name="act_user" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors">
                            <option value="">Todos</option>
                            @foreach($actUsers as $u)
                                <option value="{{ $u->id }}" @selected((string)request('act_user')===(string)$u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                                        <input type="text" name="act_q" value="{{ request('act_q') }}" placeholder="Descrição, palavra-chave" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors">
                    </div>
                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">De</label>
                                        <input type="date" name="act_from" value="{{ request('act_from') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors">
                    </div>
                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Até</label>
                                        <input type="date" name="act_to" value="{{ request('act_to') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors">
                    </div>
                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Por página</label>
                                        <select name="act_per_page" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors">
                            @foreach([10,20,30,50,100] as $pp)
                                <option value="{{ $pp }}" @selected((int)request('act_per_page',10)===$pp)>{{ $pp }}</option>
                            @endforeach
                        </select>
                    </div>
                                </div>
                                
                                <div class="flex justify-end mt-4">
                                    <button class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-lg hover:from-teal-600 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition-all transform hover:scale-105">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"/>
                                        </svg>
                                        Filtrar
                                    </button>
                                </div>
                    </div>
                </form>
                @if($activities && $activities->count())
                            <div class="bg-gray-50 rounded-xl border border-gray-200">
                                <div class="divide-y divide-gray-200">
                            @foreach($activities as $act)
                                        <div class="p-6 hover:bg-white transition-colors">
                                            <div class="flex items-start space-x-4">
                                                <div class="p-2 bg-teal-100 rounded-lg flex-shrink-0">
                                                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                        </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="font-semibold text-gray-900 mb-1">
                                                        @php
                                                            $actionLabels = [
                                                                'created' => 'Pedido criado',
                                                                'updated' => 'Pedido atualizado',
                                                                'canceled' => 'Pedido cancelado',
                                                                'fulfilled' => 'Pedido finalizado',
                                                                'reopened' => 'Pedido reaberto',
                                                                'returned' => 'Devolução registrada'
                                                            ];
                                                        @endphp
                                                        {{ $actionLabels[$act->action] ?? ucfirst($act->action) }}
                                                        @if($act->notes)
                                                            - {{ $act->notes }}
                                                        @endif
                                                    </div>
                                                    <div class="text-sm text-gray-600 mb-2">
                                                        <span class="inline-flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                            {{ optional($act->created_at)->format('d/m/Y H:i') }}
                                                        </span>
                                                        <span class="mx-2">•</span>
                                                        <span class="inline-flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                            </svg>
                                                            {{ optional($act->user)->name ?? 'Sistema' }}
                                                        </span>
                                    </div>
                                    @if(!empty($act->changes))
                                        <div class="mt-3 bg-white p-4 rounded-lg border border-gray-200">
                                            <div class="text-sm space-y-2">
                                                @if(isset($act->changes['field_changes']))
                                                    @foreach($act->changes['field_changes'] as $field => $change)
                                                        @if($field === 'title')
                                                            <div><span class="font-medium text-gray-700">Título:</span> <span class="text-gray-600">"{{ $change['old'] }}" → "{{ $change['new'] }}"</span></div>
                                                        @elseif($field === 'client_id')
                                                            <div><span class="font-medium text-gray-700">Cliente:</span> <span class="text-gray-600">ID {{ $change['old'] }} → ID {{ $change['new'] }}</span></div>
                                                        @elseif($field === 'status')
                                                            <div><span class="font-medium text-gray-700">Status:</span> <span class="text-gray-600">{{ ucfirst($change['old']) }} → {{ ucfirst($change['new']) }}</span></div>
                                                        @elseif($field === 'discount_total')
                                                            <div><span class="font-medium text-gray-700">Desconto total:</span> <span class="text-gray-600">R$ {{ number_format($change['old'], 2, ',', '.') }} → R$ {{ number_format($change['new'], 2, ',', '.') }}</span></div>
                                                        @elseif($field === 'total_amount')
                                                            <div><span class="font-medium text-gray-700">Valor total:</span> <span class="text-gray-600">R$ {{ number_format($change['old'], 2, ',', '.') }} → R$ {{ number_format($change['new'], 2, ',', '.') }}</span></div>
                                                        @endif
                                                    @endforeach
                                                @endif
                                                @if(isset($act->changes['action_type']))
                                                    @if($act->changes['action_type'] === 'add_item')
                                                        <div><span class="font-medium text-gray-700">Ação:</span> <span class="text-gray-600">Item adicionado - {{ $act->changes['product_name'] ?? 'Produto' }}</span></div>
                                                    @elseif($act->changes['action_type'] === 'remove_item')
                                                        <div><span class="font-medium text-gray-700">Ação:</span> <span class="text-gray-600">Item removido - {{ $act->changes['product_name'] ?? 'Produto' }}</span></div>
                                                    @endif
                                                @endif
                                                @if(isset($act->changes['discount_total']) && !isset($act->changes['field_changes']))
                                                    @if(is_array($act->changes['discount_total']))
                                                        <div><span class="font-medium text-gray-700">Desconto total:</span> <span class="text-gray-600">R$ {{ number_format($act->changes['discount_total']['old'], 2, ',', '.') }} → R$ {{ number_format($act->changes['discount_total']['new'], 2, ',', '.') }}</span></div>
                                                    @else
                                                        <div><span class="font-medium text-gray-700">Desconto total:</span> <span class="text-gray-600">R$ {{ number_format($act->changes['discount_total'], 2, ',', '.') }}</span></div>
                                                    @endif
                                                @endif
                                                @if(isset($act->changes['total_amount']) && !isset($act->changes['field_changes']))
                                                    @if(is_array($act->changes['total_amount']))
                                                        <div><span class="font-medium text-gray-700">Valor total:</span> <span class="text-gray-600">R$ {{ number_format($act->changes['total_amount']['old'], 2, ',', '.') }} → R$ {{ number_format($act->changes['total_amount']['new'], 2, ',', '.') }}</span></div>
                                                    @else
                                                        <div><span class="font-medium text-gray-700">Valor total:</span> <span class="text-gray-600">R$ {{ number_format($act->changes['total_amount'], 2, ',', '.') }}</span></div>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                                </div>
                                            </div>
                                        </div>
                            @endforeach
                                </div>
                                
                                @if(method_exists($activities,'links'))
                                    <div class="p-6 border-t border-gray-200">
                                        {{ $activities->links() }}
                                    </div>
                                @endif
                    </div>
                @else
                            <div class="text-center py-12">
                                <div class="p-4 bg-gray-100 rounded-full w-16 h-16 mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <p class="text-gray-500 text-lg font-medium">Sem atividades registradas</p>
                                <p class="text-gray-400 text-sm">Nenhuma ação foi registrada para este pedido ainda.</p>
                            </div>
                @endif
                    </div>
                </div>
            </div>

            @if($order->status!=='fulfilled' && empty($order->nfe_issued_at))
                <div class="mt-4 flex items-center justify-between">
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        @if(!empty($canIssueNfe) && $canIssueNfe)
                            <span class="text-emerald-700">Pedido pronto para faturar (NF-e).</span>
                        @endif
                    </div>
                    <div class="text-right space-x-2">
                        @php
                            $client = $order->client;
                            $clientOk = $client && $client->name && $client->cpf_cnpj && $client->address && $client->number && $client->neighborhood && $client->city && $client->state && $client->zip_code && ($client->codigo_municipio || $client->codigo_ibge);
                            $alreadyIssued = !empty($order->nfe_issued_at);
                            $latestNfe = $order->latestNfeNoteCompat;
                            $nfeStatus = strtolower((string) ($latestNfe->status ?? ''));
                            $canRetry = in_array($nfeStatus, ['error','rejeitada','rejected']);
                        @endphp
                        @if(!empty($canIssueNfe) && $canIssueNfe)
                            @if(!$alreadyIssued && !$latestNfe)
                                @php 
                                    $isCanceled = strtolower((string)$order->status) === 'canceled'; 
                                    $disableEmit = $isCanceled || (isset($hadCancelledNfe) ? $hadCancelledNfe : (bool)$order->has_cancelled_nfe);
                                @endphp
                                @if($disableEmit)
                                    <button type="button" class="px-4 py-2 rounded text-white bg-gray-400 cursor-not-allowed" disabled title="Reabra o pedido para emitir NF-e">Emitir NF-e</button>
                                @else
                                    <button type="button" @click="window.openNfeModalGuarded && window.openNfeModalGuarded($event)" class="px-4 py-2 rounded text-white {{ (!$clientOk) ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' }}" {{ (!$clientOk) ? 'disabled' : '' }}>Emitir NF-e</button>
                                @endif
                            @elseif($latestNfe && $canRetry)
                                <button type="button" @click="window.openNfeModalGuarded && window.openNfeModalGuarded($event)" class="px-4 py-2 rounded text-white bg-amber-600 hover:bg-amber-700">Corrigir e reemitir NF-e</button>
                                <a href="{{ route('nfe.show', $latestNfe) }}" class="px-4 py-2 rounded border text-gray-700 bg-gray-100 hover:bg-gray-200">Ver detalhes</a>
                            @elseif($latestNfe)
                                <a href="{{ route('nfe.show', $latestNfe) }}" class="px-4 py-2 rounded border text-gray-700 bg-gray-100 hover:bg-gray-200">Gerenciar NF-e</a>
                            @endif
                        @endif
                        @php
                            $hasServices = \App\Models\OrderItem::where('order_id',$order->id)->whereNotNull('product_id')->whereIn('product_id', function($q){
                                $q->select('id')->from('products')->where('type','service');
                            })->exists();
                        @endphp
                        @if($order->status==='fulfilled' && $hasServices)
                            <form action="{{ route('service_orders.issue_nfse', ['service_order' => $order->id]) }}" method="POST" class="inline">
                                @csrf
                                <button class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700" onclick="return confirm('Emitir NFS-e apenas dos serviços deste pedido?')">Emitir NFS-e (Serviços)</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
<script>
function orderEditItems(){
    return {
        newSearch: '',
        newProductId: '',
        newQty: 1,
        newUnit: '',
        newPrice: '',
        newDiscount: 0,
        suggestions: [],
        async search(){
            const term = this.newSearch;
            if (!term || term.length < 2) { this.suggestions = []; return; }
            const url = `{{ route('products.search') }}?term=${encodeURIComponent(term)}`;
            try {
                if (window.fetch) {
                    const res = await fetch(url, { headers: {'Accept':'application/json'} });
                    const data = await res.json();
                    // Igual ao /orders/create: usar balance para saldo real
                    this.suggestions = Array.isArray(data) ? data.map(p => ({
                        id: p.id,
                        name: p.name,
                        unit: p.unit,
                        price: p.price,
                        type: (p.type ?? p.product_type ?? (p.is_service ? 'service' : 'product')),
                        balance: Number(p.balance ?? p.stock_balance ?? p.available ?? p.stock ?? p.quantity ?? 0)
                    })) : [];
                } else {
                    // Fallback Edge legado
                    this.suggestions = await new Promise((resolve) => {
                        const xhr = new XMLHttpRequest();
                        xhr.open('GET', url, true);
                        xhr.setRequestHeader('Accept','application/json');
                        xhr.onreadystatechange = function(){ if (xhr.readyState === 4) { try { resolve(JSON.parse(xhr.responseText||'[]')); } catch(e){ resolve([]); } } };
                        xhr.send();
                    });
                }
            } catch(e){ this.suggestions = []; }
        },
        choose(s){
            this.newProductId = s.id;
            this.newSearch = s.name;
            this.newUnit = s.unit || '';
            this.newPrice = s.price || '';
            this.suggestions = [];
            // Ajustar quantidade inicial baseada na unidade
            this.newQty = this.getInitialQuantity();
        },
        getQuantityStep(){
            if (!this.newUnit) return 0.001;
            const unit = this.newUnit.toUpperCase();
            // Unidades inteiras
            if (['UN', 'PAR', 'PC', 'DZ', 'CX', 'UNID', 'UNIDADE', 'UNIDADES', 'PÇ', 'PECA', 'PEÇA', 'PEÇAS'].includes(unit)) {
                return 1;
            }
            // Unidades decimais (peso/volume)
            if (['KG', 'MG', 'GR', 'G', 'GRAMAS', 'GRAM', 'KILO', 'QUILO', 'QUILOS', 'L', 'ML', 'LITRO', 'LITROS', 'MILILITRO', 'MILILITROS'].includes(unit)) {
                return 0.001;
            }
            // Unidades decimais menores (para produtos mais precisos)
            if (['M', 'CM', 'MM', 'METRO', 'METROS', 'CENTIMETRO', 'CENTIMETROS', 'MILIMETRO', 'MILIMETROS'].includes(unit)) {
                return 0.01;
            }
            // Padrão: decimal pequeno
            return 0.001;
        },
        getQuantityMin(){
            if (!this.newUnit) return 0.001;
            const unit = this.newUnit.toUpperCase();
            // Unidades inteiras
            if (['UN', 'PAR', 'PC', 'DZ', 'CX', 'UNID', 'UNIDADE', 'UNIDADES', 'PÇ', 'PECA', 'PEÇA', 'PEÇAS'].includes(unit)) {
                return 1;
            }
            // Unidades decimais
            return 0.001;
        },
        getInitialQuantity(){
            if (!this.newUnit) return 1;
            const unit = this.newUnit.toUpperCase();
            // Unidades inteiras começam com 1
            if (['UN', 'PAR', 'PC', 'DZ', 'CX', 'UNID', 'UNIDADE', 'UNIDADES', 'PÇ', 'PECA', 'PEÇA', 'PEÇAS'].includes(unit)) {
                return 1;
            }
            // Unidades decimais começam com 0.001
            return 0.001;
        },
        addItem(){ /* não usado; envio via submit */ }
    }
}
function totalsInline(){
    return {
        headerDiscount: {{ number_format((float)($order->discount_total ?? 0), 2, '.', '') }},
        gross: {{ number_format((float)($items->sum(function($item) { return $item->quantity * $item->unit_price; }) ?? 0), 2, '.', '') }},
        itemsDiscount: {{ number_format((float)($items->sum('discount_value') ?? 0), 2, '.', '') }},
        grossText(){ return this.gross.toFixed(2).replace('.', ','); },
        itemsDiscountText(){ return this.itemsDiscount.toFixed(2).replace('.', ','); },
        headerDiscountText(){ return (this.headerDiscount||0).toFixed(2).replace('.', ','); },
        net(){
            const v = Math.max(0, (this.gross - this.itemsDiscount - (this.headerDiscount||0))
                + ({{ number_format((float)($order->addition_total ?? 0), 2, '.', '') }})
                + ({{ number_format((float)($order->freight_cost ?? 0), 2, '.', '') }})
                + ({{ number_format((float)($order->valor_seguro ?? 0), 2, '.', '') }})
                + ({{ number_format((float)($order->outras_despesas ?? 0), 2, '.', '') }}));
            return v;
        },
        netText(){ return this.net().toFixed(2).replace('.', ','); }
    }
}
function freightForm(){
    return {
        mode: {{ (int)($order->freight_mode ?? 9) }},
        payer: '{{ $order->freight_payer ?? 'company' }}',
        pay: { 
            type: '{{ $paymentPreset['type'] ?? 'immediate' }}',
            entry: {{ $paymentPreset['entry'] !== null ? json_encode((float)$paymentPreset['entry']) : 'null' }},
            installments: {{ (int)($paymentPreset['installments'] ?? 1) }},
            immediateMethod: '{{ $paymentPreset['immediate_method'] ?? 'cash' }}',
            entryMethod: '{{ $paymentPreset['entry_method'] ?? 'cash' }}',
            installmentMethod: '{{ $paymentPreset['installment_method'] ?? 'boleto' }}',
        },
        get requiresCarrier(){ return this.mode === 0 || this.mode === 2; },
        get requiresFreightValue(){ return this.mode === 0 || this.mode === 2; },
        computeInstallmentAmount(i){
            const n = Math.max(1, Math.min(Number(this.pay.installments||1), 24));
            if (i>n) return '';
            const total = {{ (float) $order->total_amount }};
            const entry = Number(this.pay.entry||0);
            const remaining = this.pay.type==='immediate' ? total : Math.max(0, total - entry);
            const base = Math.floor((remaining / n) * 100) / 100;
            const remainder = Number((remaining - base*n).toFixed(2));
            const val = (i===n) ? (base + remainder) : base;
            return val.toFixed(2);
        },
        computeDueDate(i){
            // 1ª parcela padrão para o mês seguinte
            const base = new Date();
            if (i===1) { base.setMonth(base.getMonth()+1); }
            const days = (i-1) * 30;
            const dt = new Date(base.getTime() + days*24*60*60*1000);
            const m = (dt.getMonth()+1).toString().padStart(2,'0');
            const d = dt.getDate().toString().padStart(2,'0');
            return `${dt.getFullYear()}-${m}-${d}`;
        },
        remainingText(){
            const total = {{ (float) $order->total_amount }};
            const entry = Number(this.pay.entry||0);
            if (this.pay.type==='immediate') return `total de R$ ${total.toFixed(2)}`;
            const rem = Math.max(0, total - entry);
            return `restante de R$ ${rem.toFixed(2)}`;
        },
        onSubmit(ev){
            if (this.mode !== 9 && !this.payer) { alert('Selecione o responsável pelo frete.'); return; }
            if ((this.mode === 0 || this.mode === 2)){
                // exige valor e transportadora
                const form = ev.target;
                const cost = form.querySelector('input[name="freight_cost"]').value;
                const carrier = form.querySelector('select[name="carrier_id"]').value;
                if (!carrier) { alert('Selecione a transportadora.'); return; }
                if (!cost || parseFloat(cost) < 0) { alert('Informe o valor do frete.'); return; }
            }
            if (this.mode === 1){
                // FOB opcional: ok prosseguir
            }
            if (this.mode === 9){
                // Sem frete: limpa campos
                const form = ev.target;
                form.querySelector('input[name="freight_cost"]').value = '';
                const sel = form.querySelector('select[name="carrier_id"]');
                if (sel) sel.value = '';
            }
            // Pagamento básico de validação no front
            const type = this.pay.type;
            if (type === 'mixed'){
                if (!this.pay.entry || Number(this.pay.entry) <= 0){ alert('Informe o valor de entrada.'); return; }
            }
            if (type !== 'immediate'){
                const inst = Number(this.pay.installments||0);
                if (!inst || inst < 1){ alert('Informe o número de parcelas.'); return; }
                if (!this.pay.firstDue){ alert('Informe a data do primeiro vencimento.'); return; }
            }
            ev.target.submit();
        }
    }
}
</script>

<!-- Modal simples de emissão de NF-e -->
@if(!empty($canIssueNfe) && $canIssueNfe && empty($order->nfe_issued_at))
<div id="nfeModal" class="hidden fixed inset-0 z-40 flex items-center justify-center" data-return-to-index-when-closed="{{ $order->status==='fulfilled' ? '1' : '0' }}">
    <div class="absolute inset-0 bg-black bg-opacity-40" onclick="window.closeNfeModalAndMaybeRedirect()"></div>
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-3xl mx-4 flex flex-col h-[90vh] max-h-[90vh] min-h-0">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="text-lg font-semibold">Emitir NF-e do Pedido #{{ $order->number }}</h3>
            <button class="text-gray-500 hover:text-gray-700" onclick="window.closeNfeModalAndMaybeRedirect()">✕</button>
        </div>
        <form method="POST" action="{{ route('nfe.emitir') }}" onsubmit="return validateNfeForm(this);" class="flex flex-col flex-1 min-h-0" id="nfeEmitForm">
            @csrf
            <input type="hidden" name="type" value="products">
            <input type="hidden" name="numero_pedido" value="{{ $order->number }}">
            <input type="hidden" name="client_id" value="{{ $order->client_id ?? '' }}">
            @if(empty($order->client_id))
            <div class="mb-3 p-3 border rounded bg-amber-50 border-amber-200">
                <div class="text-sm font-semibold text-amber-900 mb-1">CPF do Destinatário (obrigatório para NF-e)</div>
                <div class="grid grid-cols-12 gap-2 items-end">
                    <div class="col-span-6">
                        <label class="block text-xs text-amber-800">CPF (somente números)</label>
                        <input type="text" name="cpf_modal" maxlength="11" pattern="[0-9]{11}" class="w-full border border-amber-300 bg-white rounded p-2" placeholder="Ex.: 12345678909" required>
                    </div>
                    <div class="col-span-6 text-xs text-amber-800">
                        Informe o CPF quando o pedido não tiver cliente cadastrado. Usaremos "Consumidor Final" automaticamente.
                    </div>
                </div>
            </div>
            @endif
            @php
                $c = $order->client;
                $missing = [];
                if ($c) {
                    if (!$c->name) $missing[] = 'Nome do cliente';
                    if (!$c->cpf_cnpj) $missing[] = 'CPF/CNPJ';
                    if (!$c->address) $missing[] = 'Endereço';
                    if (!$c->number) $missing[] = 'Número';
                    if (!$c->neighborhood) $missing[] = 'Bairro';
                    if (!$c->city) $missing[] = 'Cidade';
                    if (!$c->state) $missing[] = 'UF';
                    if (!$c->zip_code) $missing[] = 'CEP';
                    if (!($c->codigo_municipio || $c->codigo_ibge)) $missing[] = 'Código do Município (IBGE)';
                }
            @endphp
            @if($c && count($missing) > 0)
                <div class="m-4 p-3 rounded border border-red-200 bg-red-50 text-sm text-red-800">
                    Para emitir a NF-e é necessário completar os dados do cliente:
                    <ul class="list-disc ml-6">
                        @foreach($missing as $m)
                            <li>{{ $m }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="px-6 py-5 space-y-4 flex-1 overflow-y-auto min-h-0">
                <div id="nfeModalErrors" class="hidden bg-red-50 border border-red-200 text-red-800 text-sm rounded p-3"></div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-6">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Tipo de Operação</label>
                        <select name="operation_type" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2">
                            <option value="venda">Venda</option>
                            <option value="devolucao_venda">Devolução de venda (entrada)</option>
                            <option value="devolucao_compra">Devolução a fornecedor (saída)</option>
                            <option value="transferencia">Transferência entre filiais</option>
                            <option value="remessa_industrializacao">Remessa p/ industrialização</option>
                            <option value="retorno_industrializacao">Retorno de industrialização</option>
                            <option value="complementar_valor">Complementar de valor</option>
                            <option value="complementar_tributo">Complementar de tributo</option>
                            <option value="export_producao">Exportação - produção do estabelecimento</option>
                            <option value="export_terceiros">Exportação - mercadoria de terceiros</option>
                        </select>
                    </div>
                    <div class="col-span-3">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">tpNF</label>
                        <select name="tpNF" id="modal_tpNF" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2">
                            <option value="1">1 - Saída</option>
                            <option value="0">0 - Entrada</option>
                        </select>
                    </div>
                    <div class="col-span-3">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">finNFe</label>
                        <select name="finNFe" id="modal_finNFe" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2">
                            <option value="1">1 - Normal</option>
                            <option value="2">2 - Complementar (valor)</option>
                            <option value="3">3 - Ajuste/Complementar (tributo)</option>
                            <option value="4">4 - Devolução</option>
                        </select>
                    </div>
                    <div class="col-span-4">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">idDest</label>
                        <select name="idDest" id="modal_idDest" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2">
                            <option value="1">1 - Operação interna (mesmo estado)</option>
                            <option value="2">2 - Interestadual</option>
                            <option value="3">3 - Exterior</option>
                        </select>
                    </div>
                    <div class="col-span-4">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">CFOP</label>
                        <input type="text" name="cfop" id="modal_cfop" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" value="5102">
                    </div>
                    <div class="col-span-4">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Natureza da Operação (natOp)</label>
                        <input type="text" name="natOp" id="modal_natop" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" value="Venda de mercadoria">
                    </div>
                    <div class="col-span-12">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Chave NF-e Referenciada (opcional)</label>
                        <input type="text" name="reference_key" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" placeholder="44 dígitos" value="{{ session('nfe_reference_key') }}">
                    </div>
                </div>

                <div class="border rounded p-3 mb-3">
                    <div class="text-sm font-semibold mb-2">Resumo dos Itens - Ajustar Descontos</div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm whitespace-nowrap">
                            <thead>
                                <tr class="text-left border-b">
                                    <th class="py-1 px-3">Item</th>
                                    <th class="px-3">Qtd</th>
                                    <th class="px-3">UN</th>
                                    <th class="px-3">V.Unit</th>
                                    <th class="px-3">Subtotal</th>
                                    <th class="px-3">Desc. R$</th>
                                    <th class="px-3">Base</th>
                                    <th class="px-3">ICMS (%)</th>
                                    <th class="px-3">ICMS (R$)</th>
                                    <th class="px-3">PIS (%)</th>
                                    <th class="px-3">PIS (R$)</th>
                                    <th class="px-3">COFINS (%)</th>
                                    <th class="px-3">COFINS (R$)</th>
                                    <th class="px-3">Total c/ Desc.</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($items as $it)
                                @php
                                    $produto = $it->product;
                                    $ncm = (string) ($produto->ncm ?? '');
                                    $cfop = (string) ($produto->cfop ?? '');
                                    $taxQ = \App\Models\TaxRate::where('tenant_id', auth()->user()->tenant_id)
                                        ->where('tipo_nota', 'produto')
                                        ->where('ativo', 1);
                                    if ($ncm !== '' || $cfop !== '') {
                                        $taxQ->where(function($q) use ($ncm, $cfop) {
                                            if ($ncm !== '') { $q->orWhere('ncm', $ncm); }
                                            if ($cfop !== '') { $q->orWhere('cfop', $cfop); }
                                        });
                                        $taxQ->orderByRaw("CASE WHEN ncm = ? AND cfop = ? THEN 0 WHEN ncm = ? THEN 1 WHEN cfop = ? THEN 2 ELSE 3 END", [$ncm, $cfop, $ncm, $cfop]);
                                    }
                                    $taxRate = $taxQ->first();
                                    $aliqIcms = (float) ($produto?->aliquota_icms ?? $taxRate?->icms_aliquota ?? 18.0);
                                    $aliqPis = (float) ($produto?->aliquota_pis ?? $taxRate?->pis_aliquota ?? 1.65);
                                    $aliqCofins = (float) ($produto?->aliquota_cofins ?? $taxRate?->cofins_aliquota ?? 7.6);
                                    $baseCalc = (float) $it->line_total; // line_total já é líquido
                                    $valorIcms = $baseCalc * ($aliqIcms/100);
                                    $valorPis = $baseCalc * ($aliqPis/100);
                                    $valorCofins = $baseCalc * ($aliqCofins/100);
                                @endphp
                                <tr class="border-b tax-item-row-preview" data-item-id="{{ $it->id }}" data-base-calc="{{ $baseCalc }}" data-aliq-icms="{{ $aliqIcms }}" data-aliq-pis="{{ $aliqPis }}" data-aliq-cofins="{{ $aliqCofins }}">
                                    <td class="py-1 px-3">{{ $it->name }}</td>
                                    <td class="px-3">{{ number_format($it->quantity, 3, ',', '.') }}</td>
                                    <td class="px-3">{{ $it->unit }}</td>
                                    <td class="px-3">R$ {{ number_format($it->unit_price, 2, ',', '.') }}</td>
                                    <td class="px-3">R$ {{ number_format($it->line_total, 2, ',', '.') }}</td>
                                    <td class="px-3">
                                        <input type="number" 
                                               step="0.01" 
                                               min="0" 
                                               max="{{ $it->line_total }}"
                                               name="item_discounts[{{ $it->id }}]" 
                                               class="border rounded p-1 w-20 text-sm item-discount" 
                                               value="{{ $it->discount_value ?? 0 }}"
                                               data-line-total="{{ $it->line_total }}"
                                               data-item-id="{{ $it->id }}">
                                        <div class="text-xs text-gray-500 mt-1 allocated-hint" data-item-id="{{ $it->id }}"></div>
                                    </td>
                                    <td class="px-3 item-base-calc">R$ {{ number_format($baseCalc, 2, ',', '.') }}</td>
                                    <td class="px-3">{{ number_format($aliqIcms, 2) }}%</td>
                                    <td class="px-3 item-icms-valor">R$ {{ number_format($valorIcms, 2, ',', '.') }}</td>
                                    <td class="px-3">{{ number_format($aliqPis, 2) }}%</td>
                                    <td class="px-3 item-pis-valor">R$ {{ number_format($valorPis, 2, ',', '.') }}</td>
                                    <td class="px-3">{{ number_format($aliqCofins, 2) }}%</td>
                                    <td class="px-3 item-cofins-valor">R$ {{ number_format($valorCofins, 2, ',', '.') }}</td>
                                    <td class="px-3 item-total-with-discount" data-item-id="{{ $it->id }}">
                                        R$ {{ number_format($it->line_total - ($it->discount_value ?? 0), 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t font-semibold">
                                    <td colspan="4" class="py-2 px-3 text-right">Totais:</td>
                                    <td class="py-2 px-3" id="total-without-discount">R$ {{ number_format($items->sum('line_total'), 2, ',', '.') }}</td>
                                    <td class="py-2 px-3" id="total-discount">R$ {{ number_format($items->sum('discount_value'), 2, ',', '.') }}</td>
                                    <td class="py-2 px-3" id="total-with-discount">R$ {{ number_format($items->sum('line_total') - $items->sum('discount_value'), 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Seção de Impostos baseada no Regime Tributário -->
                @php
                    $taxConfig = \App\Models\TenantTaxConfig::where('tenant_id', auth()->user()->tenant_id)->first();
                    $regimeTributario = $taxConfig?->regime_tributario ?? 'simples_nacional';
                    $isLucroReal = $regimeTributario === 'lucro_real';
                    $isLucroPresumido = $regimeTributario === 'lucro_presumido';
                    $isRegimeNormal = $isLucroReal || $isLucroPresumido;
                @endphp
                
                @if($isRegimeNormal)
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-green-900 dark:text-green-100">Impostos ({{ ucfirst(str_replace('_', ' ', $regimeTributario)) }})</h3>
                        <span class="ml-auto text-xs text-green-700 dark:text-green-300">Calculado automaticamente</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                        <div class="bg-white dark:bg-gray-800 rounded p-3 border">
                            <div class="text-gray-600 dark:text-gray-400 text-xs mb-1">ICMS Total</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100" id="icms-total">R$ 0,00</div>
                            <div class="text-xs text-gray-500 mt-1">Base: R$ <span id="icms-base">0,00</span></div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded p-3 border">
                            <div class="text-gray-600 dark:text-gray-400 text-xs mb-1">PIS Total</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100" id="pis-total">R$ 0,00</div>
                            <div class="text-xs text-gray-500 mt-1">Base: R$ <span id="pis-base">0,00</span></div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded p-3 border">
                            <div class="text-gray-600 dark:text-gray-400 text-xs mb-1">COFINS Total</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100" id="cofins-total">R$ 0,00</div>
                            <div class="text-xs text-gray-500 mt-1">Base: R$ <span id="cofins-base">0,00</span></div>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded p-3 border border-blue-200 dark:border-blue-700">
                            <div class="text-blue-700 dark:text-blue-300 text-xs mb-1">Total Impostos</div>
                            <div class="font-bold text-blue-900 dark:text-blue-100" id="total-impostos">R$ 0,00</div>
                            <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">ICMS + PIS + COFINS</div>
                        </div>
                    </div>
                    
                    <!-- Detalhamento por item -->
                    <div class="mt-4 border-t pt-3">
                        <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">Detalhamento por Item:</div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="text-left border-b">
                                        <th class="py-1 px-2">Item</th>
                                        <th class="px-2">Base Cálc.</th>
                                        <th class="px-2">ICMS (%)</th>
                                        <th class="px-2">ICMS (R$)</th>
                                        <th class="px-2">PIS (%)</th>
                                        <th class="px-2">PIS (R$)</th>
                                        <th class="px-2">COFINS (%)</th>
                                        <th class="px-2">COFINS (R$)</th>
                                    </tr>
                                </thead>
                                <tbody id="tax-details-tbody">
                                    @foreach($items as $it)
                                    @php
                                        $produto = $it->product;
                                        if (!$produto) continue;
                                        
                                        // Buscar regra tributária (opcional)
                                        $ncm = (string) ($produto->ncm ?? '');
                                        $cfop = (string) ($produto->cfop ?? '');
                                        $taxQ = \App\Models\TaxRate::where('tenant_id', auth()->user()->tenant_id)
                                            ->where('tipo_nota', 'produto')
                                            ->where('ativo', 1);
                                        if ($ncm !== '' || $cfop !== '') {
                                            $taxQ->where(function($q) use ($ncm, $cfop) {
                                                if ($ncm !== '') { $q->orWhere('ncm', $ncm); }
                                                if ($cfop !== '') { $q->orWhere('cfop', $cfop); }
                                            });
                                            $taxQ->orderByRaw("CASE WHEN ncm = ? AND cfop = ? THEN 0 WHEN ncm = ? THEN 1 WHEN cfop = ? THEN 2 ELSE 3 END", [$ncm, $cfop, $ncm, $cfop]);
                                        }
                                        $taxRate = $taxQ->first();
                                        
                                        // Alíquotas (produto > regra tributária > padrão)
                                        $aliqIcms = (float) ($produto?->aliquota_icms ?? $taxRate?->icms_aliquota ?? 18.0);
                                        $aliqPis = (float) ($produto?->aliquota_pis ?? $taxRate?->pis_aliquota ?? 1.65);
                                        $aliqCofins = (float) ($produto?->aliquota_cofins ?? $taxRate?->cofins_aliquota ?? 7.6);
                                        
                                        // Base de cálculo (valor líquido do item)
                                        $baseCalc = $it->line_total - ($it->discount_value ?? 0);
                                        
                                        // Valores dos impostos
                                        $valorIcms = $baseCalc * ($aliqIcms / 100);
                                        $valorPis = $baseCalc * ($aliqPis / 100);
                                        $valorCofins = $baseCalc * ($aliqCofins / 100);
                                    @endphp
                                    <tr class="border-b tax-item-row-detail" 
                                        data-item-id="{{ $it->id }}"
                                        data-base-calc="{{ $baseCalc }}"
                                        data-aliq-icms="{{ $aliqIcms }}"
                                        data-aliq-pis="{{ $aliqPis }}"
                                        data-aliq-cofins="{{ $aliqCofins }}"
                                        data-valor-icms="{{ $valorIcms }}"
                                        data-valor-pis="{{ $valorPis }}"
                                        data-valor-cofins="{{ $valorCofins }}">
                                        <td class="py-1 px-2">{{ $it->name }}</td>
                                        <td class="px-2 item-base-calc">R$ {{ number_format($baseCalc, 2, ',', '.') }}</td>
                                        <td class="px-2">{{ number_format($aliqIcms, 2) }}%</td>
                                        <td class="px-2 item-icms-valor">R$ {{ number_format($valorIcms, 2, ',', '.') }}</td>
                                        <td class="px-2">{{ number_format($aliqPis, 2) }}%</td>
                                        <td class="px-2 item-pis-valor">R$ {{ number_format($valorPis, 2, ',', '.') }}</td>
                                        <td class="px-2">{{ number_format($aliqCofins, 2) }}%</td>
                                        <td class="px-2 item-cofins-valor">R$ {{ number_format($valorCofins, 2, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 shadow-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-yellow-900 dark:text-yellow-100">Regime Tributário: {{ ucfirst(str_replace('_', ' ', $regimeTributario)) }}</h3>
                    </div>
                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-2">
                        Para o Simples Nacional, os impostos são calculados de forma unificada. 
                        Configure as alíquotas em <a href="{{ route('settings.edit') }}" class="underline">Configurações</a>.
                    </p>
                </div>
                @endif

                <!-- Seção de Resumo Financeiro Completo -->
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-purple-900 dark:text-purple-100">Resumo Financeiro da Nota</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                        <!-- Valores dos Produtos -->
                        <div class="bg-white dark:bg-gray-800 rounded p-3 border">
                            <div class="text-gray-600 dark:text-gray-400 text-xs mb-1">Valor dos Produtos</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">R$ {{ number_format($items->sum('line_total'), 2, ',', '.') }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $items->count() }} item(ns)</div>
                        </div>
                        
                        <!-- Descontos -->
                        <div class="bg-white dark:bg-gray-800 rounded p-3 border">
                            <div class="text-gray-600 dark:text-gray-400 text-xs mb-1">Descontos</div>
                            <div class="font-semibold text-red-600 dark:text-red-400">- R$ {{ number_format($items->sum('discount_value') + ($order->discount_total ?? 0), 2, ',', '.') }}</div>
                            <div class="text-xs text-gray-500 mt-1">Itens + Total</div>
                        </div>
                        
                        <!-- Frete -->
                        <div class="bg-white dark:bg-gray-800 rounded p-3 border">
                            <div class="text-gray-600 dark:text-gray-400 text-xs mb-1">Frete</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">R$ {{ number_format($order->freight_cost ?? 0, 2, ',', '.') }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $order->freight_mode ? 'Modalidade: ' . $order->freight_mode : 'Sem frete' }}</div>
                        </div>
                        
                        <!-- Seguro -->
                        <div class="bg-white dark:bg-gray-800 rounded p-3 border">
                            <div class="text-gray-600 dark:text-gray-400 text-xs mb-1">Seguro</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">R$ {{ number_format($order->valor_seguro ?? 0, 2, ',', '.') }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $order->valor_seguro ? 'Valor do seguro' : 'Sem seguro' }}</div>
                        </div>
                        
                        <!-- Outras Despesas -->
                        <div class="bg-white dark:bg-gray-800 rounded p-3 border">
                            <div class="text-gray-600 dark:text-gray-400 text-xs mb-1">Outras Despesas</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">R$ {{ number_format($order->outras_despesas ?? 0, 2, ',', '.') }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $order->outras_despesas ? 'Taxas e outros' : 'Sem despesas' }}</div>
                        </div>
                        
                        <!-- Total da Nota -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded p-3 border border-blue-200 dark:border-blue-700">
                            <div class="text-blue-700 dark:text-blue-300 text-xs mb-1">Total da Nota</div>
                            <div class="font-bold text-blue-900 dark:text-blue-100 text-lg">
                                R$ {{ number_format(
                                    $items->sum('line_total') - 
                                    $items->sum('discount_value') - 
                                    ($order->discount_total ?? 0) + 
                                    ($order->freight_cost ?? 0) + 
                                    ($order->valor_seguro ?? 0) + 
                                    ($order->outras_despesas ?? 0), 
                                    2, ',', '.'
                                ) }}
                            </div>
                            <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">Valor líquido final</div>
                        </div>
                    </div>
                    
                    <!-- Forma de Pagamento -->
                    @if($order->receivables && $order->receivables->count() > 0)
                    <div class="mt-4 border-t pt-3">
                        <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">Forma de Pagamento:</div>
                        <div class="space-y-1">
                            @foreach($order->receivables as $receivable)
                            <div class="flex justify-between items-center text-xs bg-gray-50 dark:bg-gray-800 rounded p-2">
                                <span class="text-gray-700 dark:text-gray-300">{{ $receivable->payment_method ?? 'Não informado' }}</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">R$ {{ number_format($receivable->amount, 2, ',', '.') }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @else
                    <div class="mt-4 border-t pt-3">
                        <div class="text-xs text-yellow-600 dark:text-yellow-400">⚠️ Forma de pagamento não definida</div>
                        <div class="text-xs text-gray-500 mt-1">Configure a forma de pagamento no pedido antes de emitir a NFe</div>
                    </div>
                    @endif
                </div>

                <div class="border rounded p-3 hidden" id="sec-return">
                    <div class="text-sm font-semibold mb-2">Itens para retorno ao estoque (use em Devolução de venda)</div>
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left border-b"><th class="py-1">Item</th><th>Qtd vendida</th><th>Devolver</th></tr></thead>
                        <tbody>
                        @foreach($items as $it)
                            <tr class="border-b">
                                <td class="py-1">{{ $it->name }}</td>
                                <td>{{ number_format($it->quantity, 3, ',', '.') }}</td>
                                <td>
                                    <input type="number" step="0.001" min="0" max="{{ (float) $it->quantity }}" name="return_qty[{{ $it->id }}]" class="border rounded p-1 w-28" value="0">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">Se a operação for Devolução de venda, as quantidades informadas serão lançadas como entrada de estoque após a emissão com sucesso.</p>
                
                </div>


                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4 shadow-sm" id="sec-frete">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100">Frete e Pagamento</h3>
                    </div>
                    <div class="grid grid-cols-12 gap-3 items-end">
                        <div class="col-span-3">
                            <label class="block text-xs text-gray-600 dark:text-gray-400">Modalidade de Frete</label>
                            <select name="freight_mode" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2">
                                <option value="9" @selected((string)($order->freight_mode ?? '9')==='9')>9 - Sem frete</option>
                                <option value="0" @selected((string)$order->freight_mode==='0')>0 - Emitente</option>
                                <option value="1" @selected((string)$order->freight_mode==='1')>1 - Destinatário</option>
                                <option value="2" @selected((string)$order->freight_mode==='2')>2 - Terceiros</option>
                                <option value="3" @selected((string)$order->freight_mode==='3')>3 - Próprio remetente</option>
                                <option value="4" @selected((string)$order->freight_mode==='4')>4 - Próprio destinatário</option>
                                <option value="5" @selected((string)$order->freight_mode==='5')>5 - Sem ocorrência de transporte</option>
                            </select>
                        </div>
                        @php $carriers = \App\Models\Carrier::where('tenant_id', auth()->user()->tenant_id)->where('active',1)->orderBy('name')->get(); @endphp
                        <div class="col-span-3 hidden" id="modal_carrier_wrap">
                            <label class="block text-xs text-gray-600 dark:text-gray-400">Transportadora</label>
                            <select name="carrier_id" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2">
                                <option value="">— Selecionar —</option>
                                @foreach($carriers as $c)
                                    <option value="{{ $c->id }}" @selected($order->carrier_id===$c->id)>{{ $c->name }} ({{ $c->cnpj }})</option>
                                @endforeach
                            </select>
                        </div>
                        @php
                            $presetForm = ($paymentPreset['type'] ?? 'immediate') === 'immediate' ? 'avista' : 'aprazo';
                            $presetMethod = strtoupper((string) ($paymentPreset['type'] ?? 'immediate')) === 'IMMEDIATE'
                                ? strtoupper((string)($paymentPreset['immediate_method'] ?? 'DINHEIRO'))
                                : strtoupper((string)($paymentPreset['installment_method'] ?? 'BOLETO'));
                        @endphp
                        <div class="col-span-3">
                            <label class="block text-xs text-gray-600 dark:text-gray-400">Forma de Pagamento</label>
                            <select name="payment_form" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2">
                                <option value="avista" @selected((old('payment_form',$presetForm))==='avista')>À vista</option>
                                <option value="aprazo" @selected((old('payment_form',$presetForm))==='aprazo')>A prazo</option>
                            </select>
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs text-gray-600 dark:text-gray-400">Meio de Pagamento</label>
                            <select name="payment_method" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2">
                                <option value="DINHEIRO" @selected((old('payment_method',$presetMethod))==='DINHEIRO')>Dinheiro</option>
                                <option value="PIX" @selected((old('payment_method',$presetMethod))==='PIX')>PIX</option>
                                <option value="BOLETO" @selected((old('payment_method',$presetMethod))==='BOLETO')>Boleto</option>
                                <option value="CARTAO_CREDITO" @selected((old('payment_method',$presetMethod))==='CARTAO_CREDITO')>Cartão crédito</option>
                                <option value="CARTAO_DEBITO" @selected((old('payment_method',$presetMethod))==='CARTAO_DEBITO')>Cartão débito</option>
                                <option value="OUTROS" @selected((old('payment_method',$presetMethod))==='OUTROS')>Outros</option>
                            </select>
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs text-gray-600 dark:text-gray-400">Valor do Frete (R$)</label>
                        <input type="number" step="0.01" min="0" name="freight_cost" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" placeholder="0,00" value="{{ $order->freight_cost }}" @disabled($order->status==='fulfilled' || !empty($order->nfe_issued_at))>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4 shadow-sm" id="sec-volumes">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-green-900 dark:text-green-100">Volumes e Pesos</h3>
                    </div>
                    <div class="grid grid-cols-12 gap-3 items-end">
                        <div class="col-span-3">
                            <label class="block text-xs text-gray-600 dark:text-gray-400">Qtd. Volumes</label>
                            <input type="number" min="0" step="1" name="volume_qtd" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" placeholder="0" value="{{ $order->volume_qtd }}" @disabled($order->status==='fulfilled' || !empty($order->nfe_issued_at))>
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs text-gray-600 dark:text-gray-400">Espécie</label>
                            <input type="text" name="volume_especie" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" placeholder="Caixa, Palete, etc." value="{{ $order->volume_especie }}" @disabled($order->status==='fulfilled' || !empty($order->nfe_issued_at))>
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs text-gray-600 dark:text-gray-400">Peso Bruto (kg)</label>
                            <input type="number" step="0.001" min="0" name="peso_bruto" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" placeholder="0,000" value="{{ $order->peso_bruto }}" @disabled($order->status==='fulfilled' || !empty($order->nfe_issued_at))>
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs text-gray-600 dark:text-gray-400">Peso Líquido (kg)</label>
                            <input type="number" step="0.001" min="0" name="peso_liquido" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" placeholder="0,000" value="{{ $order->peso_liquido }}" @disabled($order->status==='fulfilled' || !empty($order->nfe_issued_at))>
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs text-gray-600 dark:text-gray-400">Seguro (R$)</label>
                            <input type="number" step="0.01" min="0" name="valor_seguro" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" placeholder="0,00" value="{{ $order->valor_seguro }}" @disabled($order->status==='fulfilled' || !empty($order->nfe_issued_at))>
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs text-gray-600 dark:text-gray-400">Outras Despesas (R$)</label>
                            <input type="number" step="0.01" min="0" name="outras_despesas" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" placeholder="0,00" value="{{ $order->outras_despesas }}" @disabled($order->status==='fulfilled' || !empty($order->nfe_issued_at))>
                        </div>
                    </div>
                </div>

                @php
                    $itemsSubtotal = collect($items)->sum(function($i){ return (float)($i->line_total ?? 0); });
                    $itemsDiscount = collect($items)->sum(function($i){ return (float)($i->discount_value ?? 0); });
                    $headerDiscount = (float)($order->discount_total ?? 0);
                    $headerAddition = (float)($order->addition_total ?? 0);
                @endphp
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4 shadow-sm" id="sec-resumo-nota">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-purple-900 dark:text-purple-100">Resumo da Nota</h3>
                    </div>
                    <div class="space-y-3">
                        <!-- Primeira linha -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-600 dark:text-gray-300">Itens:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">R$ <span id="nota_itens">{{ number_format($itemsSubtotal, 2, ',', '.') }}</span></span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-600 dark:text-gray-300">Desc. itens:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">R$ <span id="nota_desc_itens">{{ number_format($itemsDiscount, 2, ',', '.') }}</span></span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-600 dark:text-gray-300">Acréscimos:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">R$ <span id="nota_acrescimos">{{ number_format($headerAddition, 2, ',', '.') }}</span></span>
                            </div>
                        </div>
                        
                        <!-- Segunda linha - Desconto total editável -->
                        <div class="bg-gray-50 dark:bg-gray-800 rounded p-2">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 justify-center">
                                <label for="discount_total_override" class="text-xs text-gray-600 dark:text-gray-300 font-medium text-center">Desconto Total</label>
                                <div class="relative w-full max-w-xs">
                                    <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-xs">R$</span>
                                    </div>
                                    <input type="number" step="0.01" min="0" name="discount_total_override" id="discount_total_override" 
                                           class="w-full pl-6 pr-2 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-right"
                                           value="{{ number_format($headerDiscount, 2, '.', '') }}">
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">= R$ <span id="nota_desc_total">{{ number_format($headerDiscount, 2, ',', '.') }}</span></span>
                            </div>
                        </div>
                        
                        <!-- Terceira linha -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-600 dark:text-gray-300">Frete:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">R$ <span id="nota_frete">{{ number_format((float)($order->freight_cost ?? 0), 2, ',', '.') }}</span></span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-600 dark:text-gray-300">Seguro:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">R$ <span id="nota_seguro">{{ number_format((float)($order->valor_seguro ?? 0), 2, ',', '.') }}</span></span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-600 dark:text-gray-300">Outras:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">R$ <span id="nota_outras">{{ number_format((float)($order->outras_despesas ?? 0), 2, ',', '.') }}</span></span>
                            </div>
                        </div>
                        
                        <!-- Total -->
                        <div class="border-t border-gray-200 dark:border-gray-600 pt-2">
                            <div class="flex justify-between items-center">
                                <span class="text-base font-semibold text-gray-900 dark:text-gray-100">Total da Nota:</span>
                                <span class="text-lg font-bold text-blue-600 dark:text-blue-400">R$ <span id="nota_total">0,00</span></span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4 shadow-sm" id="sec-complementares">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-100">Informações ao Fisco e Complementares</h3>
                    </div>
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-12 md:col-span-6">
                            <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">Informações complementares (infCpl)</label>
                            <textarea name="additional_info" rows="3" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" placeholder="Texto livre para DANFe/infCpl">{{ old('additional_info') }}</textarea>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">Informações ao Fisco (infAdFisco)</label>
                            <textarea name="fiscal_info" rows="3" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" placeholder="Mensagens formais ao fisco">{{ old('fiscal_info') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- seção de impostos removida: moveu para dentro de Resumo da Nota -->

                @if(auth()->user()->hasPermission('nfe.override_icms'))
                <div class="border rounded p-3">
                    <div class="text-sm font-semibold mb-2">Ajuste Manual de ICMS (opcional)</div>
                    <div class="grid grid-cols-12 gap-3 items-end">
                        <div class="col-span-4">
                            <label class="block text-xs text-gray-600 dark:text-gray-400">ICMS total (override)</label>
                            <input type="number" step="0.01" min="0" name="icms_override_total" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded p-2" placeholder="Ex.: 123,45">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Deixe em branco para usar o cálculo automático com créditos fiscais.</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            <div class="px-6 py-4 border-t text-right">
                @php
                    $lm = (bool) config('app.limited_mode', false);
                    $isFree = optional(auth()->user()->tenant?->plan)->slug === 'free';
                    $emitDisabled = ($lm || $isFree);
                @endphp
                @if(strtolower((string) (config('app.env'))) !== 'production')
                <div id="toast-discount-fusion" class="inline-block mr-3 align-middle text-xs px-3 py-2 rounded border border-amber-300 bg-amber-50 text-amber-900" style="display:none;">
                    Observação: descontos podem ser ajustados automaticamente para evitar rejeições.
                </div>
                <script>
                (function(){
                    try{
                        var t = document.getElementById('toast-discount-fusion');
                        if (t) { t.style.display = 'inline-block'; setTimeout(function(){ try{ t.style.display='none'; }catch(e){} }, 6000); }
                    }catch(e){}
                })();
                </script>
                @endif
                <button type="button" class="px-4 py-2 mr-2 border rounded" onclick="document.getElementById('nfeModal').classList.add('hidden')">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 {{ $emitDisabled ? 'opacity-60 cursor-not-allowed' : '' }}" {{ $emitDisabled ? 'disabled' : '' }} title="{{ $emitDisabled ? 'Indisponível no seu plano' : '' }}">Emitir NF-e</button>
                @if($emitDisabled)
                    <a href="{{ route('plans.upgrade') }}" class="ml-2 text-sm text-green-700 hover:underline">Fazer upgrade</a>
                @endif
            </div>
        </form>
    </div>
</div>
@endif

@php
    $tenantUf = optional($order->tenant)->state;
    $clientUf = optional($order->client)->state;
    $queryAutoOpen = request('auto_open_nfe');
    $autoOpenFlag = session('nfe_auto_open') || !empty($queryAutoOpen);
    $presetFromQuery = $queryAutoOpen === 'devolucao' ? 'devolucao_venda' : '';
    $presetCombined = session('nfe_preset_operation') ?? $presetFromQuery;
@endphp
<script>
// Presets de CFOP/natOp conforme operação e UF (idDest)
(function(){
    const modal = document.getElementById('nfeModal');
    if (!modal) return;
    const opSel = modal.querySelector('select[name="operation_type"]');
    const idDestSel = modal.querySelector('#modal_idDest');
    const cfopInp = modal.querySelector('#modal_cfop');
    const natOpInp = modal.querySelector('#modal_natop');
    const tpNFSel = modal.querySelector('#modal_tpNF');
    const finNFeSel = modal.querySelector('#modal_finNFe');
    const autoOpen = {{ $autoOpenFlag ? 'true' : 'false' }};
    const presetOp = '{{ $presetCombined }}';
    // Totais/Resumo da Nota
    const elItens = document.getElementById('nota_itens');
    const elDescItens = document.getElementById('nota_desc_itens');
    const elDescTotal = document.getElementById('nota_desc_total');
    const inpDescTotal = document.getElementById('discount_total_override');
    const elAcresc = document.getElementById('nota_acrescimos');
    const elFrete = document.getElementById('nota_frete');
    const elSeg = document.getElementById('nota_seguro');
    const elOutras = document.getElementById('nota_outras');
    const elTotal = document.getElementById('nota_total');

    function isIntra(){
        const tUF = ("{{ $tenantUf }}"||'').trim().toUpperCase();
        const cUF = ("{{ $clientUf }}"||'').trim().toUpperCase();
        return !!tUF && !!cUF && tUF === cUF;
    }
    function isInter(){ return !isIntra(); }

    function applyPresets(){
        const op = (opSel.value||'venda');
        // idDest default by UF
        if (!idDestSel.dataset.userChanged){ idDestSel.value = (op === 'export_producao' || op === 'export_terceiros') ? '3' : (isIntra() ? '1' : '2'); }
        const intra = idDestSel.value === '1';
        const inter = idDestSel.value === '2';
        // tpNF
        if (!tpNFSel.dataset.userChanged){
            const saidaOps = ['venda','devolucao_compra','transferencia','remessa_industrializacao','complementar_valor','complementar_tributo','export_producao','export_terceiros'];
            tpNFSel.value = saidaOps.includes(op) ? '1' : '0';
        }
        // finNFe
        if (!finNFeSel.dataset.userChanged){
            if (op === 'devolucao_venda' || op === 'devolucao_compra') finNFeSel.value = '4';
            else if (op === 'complementar_valor') finNFeSel.value = '2';
            else if (op === 'complementar_tributo') finNFeSel.value = '3';
            else finNFeSel.value = '1';
        }
        // CFOP
        const cfopMap = {
            'venda': intra ? '5102' : (inter ? '6102' : '7102'),
            'devolucao_venda': intra ? '1202' : (inter ? '2202' : '7202'),
            'devolucao_compra': intra ? '5202' : (inter ? '6202' : '7202'),
            'transferencia': intra ? '5152' : (inter ? '6152' : '7152'),
            'remessa_industrializacao': intra ? '5901' : (inter ? '6901' : '7901'),
            'retorno_industrializacao': intra ? '1902' : (inter ? '2902' : '7902'),
            'complementar_valor': intra ? '5102' : (inter ? '6102' : '7102'),
            'complementar_tributo': intra ? '5102' : (inter ? '6102' : '7102'),
            'export_producao': '7101',
            'export_terceiros': '7102',
        };
        if (!cfopInp.dataset.userChanged){ cfopInp.value = cfopMap[op] || '5102'; }
        // natOp
        const natMap = {
            'venda':'Venda de mercadoria',
            'devolucao_venda':'Devolução de venda',
            'devolucao_compra':'Devolução a fornecedor',
            'transferencia':'Transferência entre filiais',
            'remessa_industrializacao':'Remessa para industrialização',
            'retorno_industrializacao':'Retorno de industrialização',
            'complementar_valor':'Nota complementar de valor',
            'complementar_tributo':'Nota complementar de tributo',
            'export_producao':'Exportação de mercadoria (produção do estabelecimento)',
            'export_terceiros':'Exportação de mercadoria (terceiros)',
        };
        if (!natOpInp.dataset.userChanged){ natOpInp.value = natMap[op] || 'Venda de mercadoria'; }

        // Mostrar seção de devolução somente quando operação for devolução de venda
        const secReturn = modal.querySelector('#sec-return');
        if (secReturn){
            if (op === 'devolucao_venda') secReturn.classList.remove('hidden');
            else secReturn.classList.add('hidden');
        }

        // Atualiza total da Nota (itens - descs + acresc + frete + seguro + outras)
        try{
            const vItens = parseMoney(elItens?.textContent) || 0;
            const vDescItens = parseMoney(elDescItens?.textContent) || 0;
            // Captura do input (valor em ponto) tem prioridade; fallback: span formatado
            let vDescTotal = 0;
            if (inpDescTotal && inpDescTotal.value !== '') {
                vDescTotal = parseFloat(inpDescTotal.value) || 0;
            } else {
                vDescTotal = parseMoney(elDescTotal?.textContent) || 0;
            }
            const vAcresc = parseMoney(elAcresc?.textContent) || 0;
            const vFrete = parseMoney(elFrete?.textContent) || 0;
            const vSeg = parseMoney(elSeg?.textContent) || 0;
            const vOutras = parseMoney(elOutras?.textContent) || 0;
            const soma = Math.max(0, (vItens - vDescItens - vDescTotal) + vAcresc + vFrete + vSeg + vOutras);
            if (elTotal) { elTotal.textContent = soma.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2}); }
        }catch(e){}
    }

    function parseMoney(txt){
        if (!txt) return 0;
        // Remove símbolos e mantém apenas dígitos, vírgula, ponto e sinal
        const cleaned = String(txt)
            .replace(/\u00A0/g, ' ')
            .replace(/[^0-9,.-]/g, '')
            .trim();
        // Normaliza milhar/decimal no formato pt-BR
        const normalized = cleaned.replace(/\./g, '').replace(',', '.');
        const n = Number(normalized);
        return isNaN(n) ? 0 : n;
    }

    // Atualiza preview de desconto total (nota) ao digitar
    if (inpDescTotal) {
        inpDescTotal.addEventListener('input', function(){
            if (elDescTotal) {
                const v = parseMoney(this.value);
                elDescTotal.textContent = (v||0).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
            }
            applyPresets();
        });
    }

    opSel && opSel.addEventListener('change', applyPresets);
    idDestSel && idDestSel.addEventListener('change', function(){ this.dataset.userChanged = '1'; applyPresets(); });
    cfopInp && cfopInp.addEventListener('input', function(){ this.dataset.userChanged = '1'; });
    natOpInp && natOpInp.addEventListener('input', function(){ this.dataset.userChanged = '1'; });
    tpNFSel && tpNFSel.addEventListener('change', function(){ this.dataset.userChanged = '1'; });
    finNFeSel && finNFeSel.addEventListener('change', function(){ this.dataset.userChanged = '1'; });

    // Mostrar/ocultar transportadora conforme modalidade (0/2 exige)
    const freightModeSel = modal.querySelector('select[name="freight_mode"]');
    const carrierWrap = modal.querySelector('#modal_carrier_wrap');
    function toggleCarrier(){
        if (!freightModeSel || !carrierWrap) return;
        const val = String(freightModeSel.value||'9');
        if (val === '0' || val === '2') carrierWrap.classList.remove('hidden');
        else carrierWrap.classList.add('hidden');
    }
    freightModeSel && freightModeSel.addEventListener('change', toggleCarrier);
    toggleCarrier();

    // Aplicar presets ao abrir modal
    const openBtn = document.querySelector('button[onclick*="nfeModal"]') || document.querySelector('button[class*="Emitir NF-e"]');
    modal.addEventListener('transitionstart', applyPresets);
    applyPresets();

    // Guard para abrir o modal somente se itens estiverem completos
    window.openNfeModalGuarded = function(){
        try {
            // Seletor mais específico: tabela principal de itens (a primeira com divide-y)
            const table = document.querySelector('table.min-w-full.divide-y');
            const body = table ? table.tBodies && table.tBodies[0] : null;
            const rows = body ? Array.from(body.rows) : [];
            let hasIncomplete = rows.some(row => {
                const tds = row.querySelectorAll('td');
                if (tds.length < 6) return true;
                const qtdText = tds[1]?.textContent || '';
                const unText = tds[2]?.textContent || '';
                const vUnitText = tds[3]?.textContent || '';
                const totalText = tds[5]?.textContent || '';

                const toNum = (txt) => parseFloat(String(txt).replace(/[^0-9,.-]/g,'').replace(/\./g,'').replace(',', '.')) || 0;
                const qtd = toNum(qtdText);
                const vUnit = toNum(vUnitText);
                const total = toNum(totalText);
                const unOk = String(unText).trim().length > 0;

                return (qtd <= 0) || !unOk || (vUnit <= 0) || (total <= 0);
            });

            // Nova checagem: produto com cadastro incompleto (ex.: NCM/CFOP/CST/aliquotas ausentes)
            if (!hasIncomplete) {
                const productCells = Array.from(document.querySelectorAll('[data-product-in-row]'));
                hasIncomplete = productCells.some(cell => cell.getAttribute('data-incomplete') === '1');
            }

            if (hasIncomplete) {
                if (typeof showToast === 'function') {
                    showToast('⚠️ Produto com cadastro incompleto. Complete o cadastro em Produtos antes de emitir.', 'error');
                } else { alert('Verifique os itens incompletos antes de continuar.'); }
                return;
            }

            document.getElementById('nfeModal').classList.remove('hidden');
        } catch(e) {
            try { console.log('[NFe] openNfeModalGuarded error', e); } catch(_){}
            document.getElementById('nfeModal').classList.remove('hidden');
        }
    }

    // Se veio da devolução com pedido: forçar presets e abrir modal
    if (presetOp) {
        if (opSel) { opSel.value = presetOp; opSel.dispatchEvent(new Event('change')); }
    }
    if (autoOpen) {
        modal.classList.remove('hidden');
        // Garante aplicação após render
        setTimeout(applyPresets, 0);
        setTimeout(toggleCarrier, 0);
    }

    // Preenche quantidades de retorno quando aplicável
    try {
        const returnQty = @json(session('nfe_return_qty'));
        if (returnQty && typeof returnQty === 'object') {
            Object.keys(returnQty).forEach(function(k){
                const inp = modal.querySelector('input[name="return_qty['+k+']"]');
                if (inp) { inp.value = returnQty[k]; }
            });
        }
    } catch(e){}
})();

// Fecha modal e, se pedido finalizado, redireciona para /orders
window.closeNfeModalAndMaybeRedirect = function(){
    try {
        const modal = document.getElementById('nfeModal');
        if (!modal) return;
        modal.classList.add('hidden');
        const shouldGoIndex = modal.getAttribute('data-return-to-index-when-closed') === '1';
        if (shouldGoIndex) {
            window.location.href = "{{ route('orders.index') }}";
        }
    } catch(e) {}
}

// Adicionar script para calcular totais automaticamente
document.addEventListener('DOMContentLoaded', function() {
    const discountInputs = document.querySelectorAll('.item-discount');
        const headerInput = document.getElementById('discount_total_override');
    
    discountInputs.forEach(input => {
        input.addEventListener('input', function() {
            const itemId = this.dataset.itemId;
            const lineTotal = parseFloat(this.dataset.lineTotal);
            const discount = parseFloat(this.value) || 0;
            const totalWithDiscount = lineTotal - discount;
            
            // Atualizar total do item
            const itemTotalCell = document.querySelector(`[data-item-id="${itemId}"].item-total-with-discount`);
            if (itemTotalCell) {
                itemTotalCell.textContent = 'R$ ' + totalWithDiscount.toLocaleString('pt-BR', {minimumFractionDigits: 2});
            }
            
            // Recalcular totais gerais
            updateTotals();
                // Atualizar dica de rateio
                updateAllocationHints();
        });
    });
    
        if (headerInput) {
            headerInput.addEventListener('input', function(){ updateAllocationHints(); });
        }

    function updateTotals() {
        let totalWithoutDiscount = 0;
        let totalDiscount = 0;
        
        discountInputs.forEach(input => {
            const lineTotal = parseFloat(input.dataset.lineTotal);
            const discount = parseFloat(input.value) || 0;
            totalWithoutDiscount += lineTotal;
            totalDiscount += discount;
        });
        // Incluir desconto de cabeçalho na prévia dos totais
        const headerVal = parseFloat(headerInput?.value||'0') || 0;
        totalDiscount += headerVal;
        
        const totalWithDiscount = totalWithoutDiscount - totalDiscount;
        
        document.getElementById('total-without-discount').textContent = 'R$ ' + totalWithoutDiscount.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        document.getElementById('total-discount').textContent = 'R$ ' + totalDiscount.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        document.getElementById('total-with-discount').textContent = 'R$ ' + totalWithDiscount.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        
        // Recalcular impostos se estiver no regime normal
        updateTaxCalculations();
    }
    
        // Apenas dica visual: rateio proporcional do desconto total entre os itens (não altera campos)
        function updateAllocationHints(){
            try{
                const header = parseFloat(headerInput?.value||'0');
                const weights = Array.from(discountInputs).map(inp => {
                    const line = parseFloat(inp.dataset.lineTotal)||0;
                    const disc = parseFloat(inp.value)||0; // se quiser ignorar, use 0 para apenas header
                    const net = Math.max(0, line - disc);
                    return net;
                });
                const sum = weights.reduce((a,b)=>a+b,0);
                const hints = document.querySelectorAll('.allocated-hint');
                if (!hints || hints.length===0) return;
                if (header > 0 && sum > 0){
                    // aloca com arredondamento simples
                    const alloc = weights.map(w => (header * (w/sum)));
                    let rem = +(header - alloc.reduce((a,b)=>a+Math.floor(b*100)/100,0)).toFixed(2);
                    let cents = Math.round(rem*100);
                    // distribuir centavos
                    const order = [...alloc.keys()].sort((a,b)=> (alloc[b]%1) - (alloc[a]%1));
                    const out = alloc.map(v => Math.floor(v*100)/100);
                    for(let i=0;i<order.length && cents>0;i++){ out[order[i]] = +(out[order[i]] + 0.01).toFixed(2); cents--; }
                    Array.from(hints).forEach((h,i)=>{ h.textContent = 'Rateio do total: R$ '+ out[i].toLocaleString('pt-BR', {minimumFractionDigits: 2}); });
                } else {
                    Array.from(hints).forEach(h=>{ h.textContent = ''; });
                }
            }catch(e){}
        }

    function updateTaxCalculations() {
        try { console.debug('updateTaxCalculations: start'); } catch(e){}
        const taxRows = document.querySelectorAll('.tax-item-row-detail');
        if (taxRows.length === 0) { try{ console.debug('updateTaxCalculations: no tax rows found'); }catch(e){} return; }
        
        let totalIcms = 0;
        let totalPis = 0;
        let totalCofins = 0;
        let totalBase = 0;

        // Preparar rateio proporcional do desconto de cabeçalho para prévia visual
        const headerVal = parseFloat(headerInput?.value||'0') || 0;
        const discountByItem = Array.from(discountInputs).map(inp => parseFloat(inp.value)||0);
        const lineByItem = Array.from(discountInputs).map(inp => parseFloat(inp.dataset.lineTotal)||0);
        const weights = lineByItem.map((line, idx) => Math.max(0, line - discountByItem[idx]));
        const sumWeights = weights.reduce((a,b)=>a+b,0);
        let allocHeader = weights.map(()=>0);
        if (headerVal > 0 && sumWeights > 0) {
            // Alocação com ajuste de centavos
            const raw = weights.map(w => (headerVal * (w/sumWeights)));
            const floored = raw.map(v => Math.floor(v*100)/100);
            let remainderCents = Math.round((headerVal - floored.reduce((a,b)=>a+b,0))*100);
            const order = raw.map((v,i)=>({i,f:v - floored[i]})).sort((a,b)=>b.f - a.f).map(x=>x.i);
            allocHeader = floored.slice();
            for (let k=0; k<order.length && remainderCents>0; k++) { allocHeader[order[k]] = +(allocHeader[order[k]] + 0.01).toFixed(2); remainderCents--; }
        }
        
        taxRows.forEach((row, idx) => {
            const itemId = row.dataset.itemId;
            const discountInput = document.querySelector(`input[name="item_discounts[${itemId}]"]`);
            const originalDiscount = parseFloat(discountInput?.value) || 0;
            const headerAlloc = allocHeader[idx] || 0;
            
            // Recalcular base de cálculo com desconto atualizado (sem depender de parseMoney)
            const itemTotalCell = document.querySelector(`[data-item-id="${itemId}"].item-total-with-discount`);
            const lineTotalFromInput = parseFloat(discountInput?.dataset.lineTotal || '0');
            const baseFromDataset = parseFloat(row.dataset.baseCalc || '0');
            const lineTotal = (isNaN(lineTotalFromInput) || lineTotalFromInput === 0) ? baseFromDataset : lineTotalFromInput;
            const baseCalc = Math.max(0, lineTotal - originalDiscount - headerAlloc);
            
            const aliqIcms = parseFloat(row.dataset.aliqIcms);
            const aliqPis = parseFloat(row.dataset.aliqPis);
            const aliqCofins = parseFloat(row.dataset.aliqCofins);
            
            // Calcular valores dos impostos
            const valorIcms = baseCalc * (aliqIcms / 100);
            const valorPis = baseCalc * (aliqPis / 100);
            const valorCofins = baseCalc * (aliqCofins / 100);
            
            // Atualizar valores na linha
            const baseCellItem = row.querySelector('.item-base-calc');
            const icmsCell = row.querySelector('.item-icms-valor');
            const pisCell = row.querySelector('.item-pis-valor');
            const cofinsCell = row.querySelector('.item-cofins-valor');
            
            if (baseCellItem) baseCellItem.textContent = 'R$ ' + baseCalc.toLocaleString('pt-BR', {minimumFractionDigits: 2});
            if (icmsCell) icmsCell.textContent = 'R$ ' + valorIcms.toLocaleString('pt-BR', {minimumFractionDigits: 2});
            if (pisCell) pisCell.textContent = 'R$ ' + valorPis.toLocaleString('pt-BR', {minimumFractionDigits: 2});
            if (cofinsCell) cofinsCell.textContent = 'R$ ' + valorCofins.toLocaleString('pt-BR', {minimumFractionDigits: 2});
            if (itemTotalCell) itemTotalCell.textContent = 'R$ ' + baseCalc.toLocaleString('pt-BR', {minimumFractionDigits: 2});
            
            // Somar aos totais
            totalBase += baseCalc;
            totalIcms += valorIcms;
            totalPis += valorPis;
            totalCofins += valorCofins;
        });
        
        // Atualizar totais gerais dos impostos
        const icmsTotalEl = document.getElementById('icms-total');
        const icmsBaseEl = document.getElementById('icms-base');
        const pisTotalEl = document.getElementById('pis-total');
        const pisBaseEl = document.getElementById('pis-base');
        const cofinsTotalEl = document.getElementById('cofins-total');
        const cofinsBaseEl = document.getElementById('cofins-base');
        const totalImpostosEl = document.getElementById('total-impostos');
        
        if (icmsTotalEl) icmsTotalEl.textContent = 'R$ ' + totalIcms.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        if (icmsBaseEl) icmsBaseEl.textContent = totalBase.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        if (pisTotalEl) pisTotalEl.textContent = 'R$ ' + totalPis.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        if (pisBaseEl) pisBaseEl.textContent = totalBase.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        if (cofinsTotalEl) cofinsTotalEl.textContent = 'R$ ' + totalCofins.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        if (cofinsBaseEl) cofinsBaseEl.textContent = totalBase.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        if (totalImpostosEl) totalImpostosEl.textContent = 'R$ ' + (totalIcms + totalPis + totalCofins).toLocaleString('pt-BR', {minimumFractionDigits: 2});
        try { console.debug('updateTaxCalculations: totals', { totalBase, totalIcms, totalPis, totalCofins }); } catch(e){}
    }
    
    // Calcular impostos inicialmente e ao abrir o modal
    console.log('[NFe] script loaded: scheduling initial tax calc');
    setTimeout(() => { try{ updateTaxCalculations(); }catch(e){ console.log('[NFe] updateTaxCalculations init error', e); } }, 150);
    try {
        // Modal id real
        const modal = document.getElementById('nfeModal');
        if (modal) {
            const startTaxRecalcPoller = () => {
                try {
                    let tries = 0;
                    const maxTries = 20; // ~5s
                    const timer = setInterval(() => {
                        tries++;
                        try { updateTaxCalculations(); } catch(e) { console.log('[NFe] tax poller error', e); }
                        if (tries >= maxTries) clearInterval(timer);
                    }, 250);
                } catch(e) { console.log('[NFe] startTaxRecalcPoller error', e); }
            };
            const recalc = () => { try{ setTimeout(() => { updateTaxCalculations(); startTaxRecalcPoller(); }, 50); }catch(e){ console.log('[NFe] updateTaxCalculations modal error', e); } };
            modal.addEventListener('transitionend', recalc);
            modal.addEventListener('transitionstart', recalc);
            // fallback quando mostrar/ocultar sem transição
            const observer = new MutationObserver(recalc);
            observer.observe(modal, { attributes: true, attributeFilter: ['class', 'style'] });

            // Botões que abrem o modal
            try {
                const openers = document.querySelectorAll('button[onclick*="nfeModal"]');
                openers.forEach(btn => btn.addEventListener('click', () => setTimeout(() => { updateTaxCalculations(); startTaxRecalcPoller(); }, 50)));
            } catch(e) { console.log('[NFe] openers bind error', e); }
        }
    } catch(e){}
});

function showNfeErrors(errors){
    const box = document.getElementById('nfeModalErrors');
    if(!box) return;
    box.innerHTML = Array.isArray(errors) ? ('<ul class="list-disc ml-5">'+errors.map(e=>'<li>'+e+'</li>').join('')+'</ul>') : (''+errors);
    box.classList.remove('hidden');
    box.scrollIntoView({behavior:'smooth', block:'start'});
}

function hideNfeErrors(){
    const box = document.getElementById('nfeModalErrors');
    if(!box) return; box.classList.add('hidden'); box.innerHTML='';
}

function validateNfeForm(form){
    hideNfeErrors();
    const errs = [];
    // Bloqueia emissão se algum produto estiver marcado como incompleto
    try {
        const anyIncomplete = document.querySelector('[data-product-in-row][data-incomplete="1"]');
        if (anyIncomplete) {
            if (typeof showToast === 'function') {
                showToast('⚠️ Produto com cadastro incompleto. Complete o cadastro em Produtos antes de emitir.', 'error');
            } else {
                alert('Produto com cadastro incompleto. Complete o cadastro em Produtos antes de emitir.');
            }
            return false;
        }
    } catch(e) {}
    try {
        const natOp = form.querySelector('input[name="natOp"]');
        const finNFeSel = form.querySelector('#modal_finNFe');
        if (natOp && !String(natOp.value||'').trim()) errs.push('Informe a Natureza da Operação.');
        if (finNFeSel && !String(finNFeSel.value||'').trim()) errs.push('Selecione a Finalidade da NF-e.');
    } catch(e){}
    const fmEl = form.querySelector('select[name="freight_mode"]');
    const fm = parseInt(fmEl ? (fmEl.value||'9') : '9',10);
    const finEl = form.querySelector('#modal_finNFe');
    const fin = parseInt(finEl ? (finEl.value||'1') : '1',10);
    const opEl = form.querySelector('select[name="operation_type"]');
    const op = opEl ? (opEl.value||'venda') : 'venda';
    const refEl = form.querySelector('input[name="reference_key"]');
    const ref = refEl ? (refEl.value||'').trim() : '';

    // Volumes: requer mínimos quando fm != 9 ou quando usuário explicitou algum campo
    const qtdEl = form.querySelector('input[name="volume_qtd"]');
    const pbEl = form.querySelector('input[name="peso_bruto"]');
    const plEl = form.querySelector('input[name="peso_liquido"]');
    const qtd = qtdEl ? qtdEl.value : '';
    const pb = pbEl ? pbEl.value : '';
    const pl = plEl ? plEl.value : '';
    const explicit = (qtd!=='' && qtd!=null) || (pb!=='' && pb!=null) || (pl!=='' && pl!=null);
    if (fm !== 9 || explicit){
        const qtdVal = Math.max(1, parseInt(qtd||'1',10));
        const pbVal = Math.max(0.1, parseFloat(pb||'0.1'));
        const plVal = Math.max(0.1, parseFloat(pl||'0.1'));
        if (qtd && parseInt(qtd,10) < 1) errs.push('Qtd. de volumes deve ser pelo menos 1.');
        if (pb && parseFloat(pb) <= 0) errs.push('Peso bruto deve ser > 0 (ex.: 0,100 kg).');
        if (pl && parseFloat(pl) <= 0) errs.push('Peso líquido deve ser > 0 (ex.: 0,100 kg).');
        if (qtdEl && !qtd) qtdEl.value = qtdVal;
        if (pbEl && (!pb || parseFloat(pb)<=0)) pbEl.value = pbVal.toFixed(3);
        if (plEl && (!pl || parseFloat(pl)<=0)) plEl.value = plVal.toFixed(3);
    }

    // Transportadora obrigatória para fm 0/2
    if (fm===0 || fm===2){
        const carrierEl = form.querySelector('select[name="carrier_id"]');
        const carrier = carrierEl ? carrierEl.value : '';
        if (!carrier) errs.push('Selecione a transportadora para a modalidade de frete.');
    }

    // Devolução exige chave
    if (fin===4 || op==='devolucao_venda' || op==='devolucao_compra'){
        if (ref.length !== 44) errs.push('Informe a chave NF-e referenciada (44 dígitos) para devolução.');
    }

    if (errs.length>0){ showNfeErrors(errs); return false; }

    // Rateio do desconto de cabeçalho é feito no backend com base no banco de dados
    // O modal é apenas visualização/ajuste. Não forçar alterações aqui.
    return confirm('Confirmar emissão da NF-e?');
}
document.addEventListener('submit', function(ev){
    const form = ev.target;
    if (form && form.id === 'nfeEmitForm' && form.method && form.method.toLowerCase() === 'post'){
        // mensagens amigáveis são tratadas no backend via flash
    }
});
</script>

<!-- Modal de Cancelamento -->
<div id="cancelOrderModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Cancelar Pedido #{{ $order->number }}</h3>
            <button type="button" onclick="closeCancelModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded">
            <p class="text-sm text-amber-800">{{ $strategyExplanation }}</p>
        </div>
        
        <form id="cancelOrderForm" method="POST" action="{{ route('orders.destroy', $order) }}">
            @csrf 
            <input type="hidden" name="_method" value="DELETE">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Justificativa <span class="text-red-500">*</span>
                </label>
                <textarea name="cancel_reason" id="cancelReason" rows="4" required minlength="15" maxlength="500"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                    placeholder="Descreva o motivo do cancelamento (mínimo 15 caracteres)"></textarea>
                <p class="text-xs text-gray-500 mt-1">Este motivo será registrado permanentemente no histórico do pedido.</p>
            </div>
            
            <div class="flex space-x-3">
                <button type="button" onclick="closeCancelModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Voltar
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    Confirmar Cancelamento
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCancelModal() {
    console.log('Abrindo modal de cancelamento...');
    const modal = document.getElementById('cancelOrderModal');
    console.log('Modal encontrado:', modal);
    
    if (modal) {
        modal.classList.remove('hidden');
        document.getElementById('cancelReason').focus();
        console.log('Modal aberto com sucesso!');
    } else {
        console.error('Modal não encontrado!');
        alert('Erro: Modal de cancelamento não encontrado!');
    }
}

function closeCancelModal() {
    document.getElementById('cancelOrderModal').classList.add('hidden');
    document.getElementById('cancelReason').value = '';
}

// Fechar ao clicar fora
document.getElementById('cancelOrderModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});

// Validação no submit
document.getElementById('cancelOrderForm')?.addEventListener('submit', function(e) {
    const reason = document.getElementById('cancelReason').value.trim();
    console.log('Justificativa:', reason, 'Tamanho:', reason.length);
    
    if (reason.length < 15) {
        e.preventDefault();
        alert('A justificativa deve ter pelo menos 15 caracteres.');
        document.getElementById('cancelReason').focus();
        return false;
    }
    
    // Confirmar cancelamento
    if (!confirm('Tem certeza que deseja cancelar este pedido? Esta ação não pode ser desfeita.')) {
        e.preventDefault();
        return false;
    }
    
    // Debug: verificar todos os campos do formulário
    const formData = new FormData(this);
    console.log('Dados do formulário:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ':', value);
    }
    
    console.log('Formulário sendo enviado com justificativa:', reason);
    return true;
});
</script>

</x-app-layout>

