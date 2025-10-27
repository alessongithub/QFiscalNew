<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar Orçamento #{{ $quote->number }}
            </h2>
            <div class="flex items-center space-x-3">
                @php($locked = in_array(strtolower(trim((string) $quote->status)), ['approved','customer_notified','canceled']))
                @if(!$locked)
                    @can('quotes.approve')
                    <form action="{{ route('quotes.approve', $quote) }}" method="POST" class="inline" onsubmit="return confirm('Deseja aprovar este orçamento e converter em pedido?');">
                        @csrf
                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Aprovar
                        </button>
                    </form>
                    @endcan
                @endif
                @can('quotes.notify')
                <form action="{{ route('quotes.notify', $quote) }}" method="POST" class="inline">
                    @csrf
                    <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5v-5a7.5 7.5 0 00-15 0v5h5l-5 5-5-5h5v-5a7.5 7.5 0 0115 0v5z"/>
                        </svg>
                        Notificar
                    </button>
                </form>
                @endcan
                @if(!$locked)
                    @can('quotes.reject')
                    <form action="{{ route('quotes.reject', $quote) }}" method="POST" class="inline" onsubmit="return confirm('Deseja reprovar este orçamento?');">
                        @csrf
                        <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Reprovar
                        </button>
                    </form>
                    @endcan
                    @can('quotes.convert')
                    <form action="{{ route('quotes.convert', $quote) }}" method="POST" class="inline" onsubmit="return confirm('Deseja converter este orçamento em pedido?');">
                        @csrf
                        <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Converter em Pedido
                        </button>
                    </form>
                    @endcan
                @endif
                <a href="{{ route('quotes.print', $quote) }}" target="_blank" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimir
                </a>
                @if(auth()->user()->is_admin || auth()->user()->hasPermission('quotes.audit'))
                <a href="{{ route('quotes.audit', $quote) }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Auditoria
                </a>
                @endif
                <a href="{{ route('quotes.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">{{ session('success') }}</div>
    @endif

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('quotes.update', $quote) }}" method="POST" class="space-y-6" x-data='quoteFormEdit({{ json_encode($items->map(fn($it)=>[
            'product_id'=>$it->product_id,
            'search'=>$it->name,
            'quantity'=>$it->quantity,
            'unit'=>$it->unit,
            'unit_price'=>$it->unit_price,
            'discount_value'=>$it->discount_value ?? 0,
            'description'=>$it->description ?? '',
            'delivery_date'=>$it->delivery_date ? $it->delivery_date->format('Y-m-d') : '',
        ])) }})' onsubmit="return confirmStatusSelect()">
            @csrf @method('PUT')
                
                <!-- Card Principal -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Informações Básicas
                        </h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cliente</label>
                                <select name="client_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" @selected($quote->client_id===$c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Número</label>
                                <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50" value="{{ $quote->number }}" readonly>
                </div>
            </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Título</label>
                                <input type="text" name="title" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" value="{{ $quote->title }}" required>
            </div>
                <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" id="statusSelect" data-original="{{ $quote->status }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        @foreach(['awaiting'=>'Aguardando','approved'=>'Aprovado'] as $k=>$v)
                            <option value="{{ $k }}" @selected($quote->status===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Validade do Orçamento</label>
                                <input type="date" name="validity_date" min="{{ date('Y-m-d') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" value="{{ $quote->validity_date ? $quote->validity_date->format('Y-m-d') : '' }}">
                </div>
            </div>
                        
                <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                            <textarea name="notes" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Observações gerais do orçamento">{{ $quote->notes }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Card de Itens -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-50 to-purple-100 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Itens do Orçamento
                            </h3>
                            <div class="flex items-center space-x-3">
                                <button type="button" @click="add()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Adicionar Item
                                </button>
                                <button type="button" @click="clearAll()" class="bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Limpar Itens
                                </button>
                </div>
            </div>
                    </div>
                    <div class="p-6">
                <template x-for="(it, i) in items" :key="i">
                            <div class="bg-gray-50 rounded-lg p-4 mb-4" @click.away="suggestions=[]">
                                <div class="grid grid-cols-12 gap-4">
                                    <div class="col-span-5 relative">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Produto/Serviço</label>
                            <input type="hidden" :name="`items[${i}][product_id]`" x-model="it.product_id">
                                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Digite para buscar..." x-model="it.search" @input.debounce.300ms="search(i)">
                                        <div class="absolute z-10 bg-white border border-gray-300 rounded-lg mt-1 w-full max-h-48 overflow-auto shadow-lg" x-show="suggestions.length">
                                <template x-for="s in suggestions" :key="s.id">
                                                <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0" @click="choose(i, s)">
                                                    <div class="font-medium text-gray-900" x-text="s.name"></div>
                                                    <div class="text-xs text-gray-500" x-text="`${s.unit || ''} • R$ ${Number(s.price).toFixed(2)}${s.type==='product' ? ' • Saldo: '+Number(s.balance??0).toFixed(3) : ''}`"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                                    <div class="col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Quant.</label>
                                        <input type="number" :step="getQuantityStep(it.unit)" :min="getQuantityMin(it.unit)" inputmode="decimal" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" x-model="it.quantity" :name="`items[${i}][quantity]`">
                                    </div>
                                    <div class="col-span-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">UN</label>
                                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" x-model="it.unit" readonly>
                        </div>
                        <div class="col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">V.Unit</label>
                                        <input type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" x-model="it.unit_price" readonly>
                        </div>
                        <div class="col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Desconto (R$)</label>
                                        <input type="number" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" x-model="it.discount_value" :name="`items[${i}][discount_value]`">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">V.Total</label>
                                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 font-semibold text-right" :value="((Number(it.quantity||0)*Number(it.unit_price||0)) - Number(it.discount_value||0)).toFixed(2)" readonly>
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Prazo de Entrega</label>
                                        <input type="date" :min="getTodayDate()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" x-model="it.delivery_date" :name="`items[${i}][delivery_date]`" @change="validateDeliveryDate(i)">
                                        <div x-show="it.delivery_date_error" class="text-red-500 text-xs mt-1" x-text="it.delivery_date_error"></div>
                                    </div>
                                    <div class="col-span-12">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Observações do Item</label>
                                        <textarea rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" x-model="it.description" :name="`items[${i}][description]`" placeholder="Ex: Cor azul, tamanho G, entrega urgente, desconto por pagamento à vista..."></textarea>
                                    </div>
                        </div>
                                <div class="text-right mt-3">
                                    <button type="button" @click="remove(i)" class="inline-flex items-center px-3 py-1 text-sm text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Remover
                                    </button>
                        </div>
                    </div>
                </template>
                        
                        <!-- Resumo dos Itens -->
                        <div class="bg-gray-50 rounded-lg p-4 mt-4">
                <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-600">
                                    <span x-text="items.length"></span> item(ns) adicionado(s)
                                </div>
                    <div class="text-right">
                                    <div class="text-sm text-gray-600">Subtotal</div>
                                    <div class="text-xl font-semibold text-gray-900">R$ <span x-text="subtotal().toFixed(2)"></span></div>
                                </div>
                            </div>
                    </div>
                </div>
            </div>

                <!-- Card de Desconto Global -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-green-50 to-green-100 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                            Desconto Global
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Desconto Global (R$)</label>
                                <input type="number" step="0.01" min="0" name="discount_total" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors" placeholder="0,00" value="{{ $quote->discount_total }}" x-model="globalDiscount">
                            </div>
                            <div>
                                <div class="text-right">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Total Final</label>
                                    <div class="font-bold text-2xl text-gray-900 py-3">
                                        R$ <span x-text="(subtotal() - Number(globalDiscount || 0)).toFixed(2)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card de Formas de Pagamento -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            Formas de Pagamento Aceitas
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">Selecione as formas de pagamento aceitas:</label>
                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="payment_methods[]" value="cash" class="mr-3 h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded" @checked(in_array('cash', $quote->payment_methods ?? []))>
                                        <span class="text-sm text-gray-700">À Vista (Dinheiro)</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="payment_methods[]" value="pix" class="mr-3 h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded" @checked(in_array('pix', $quote->payment_methods ?? []))>
                                        <span class="text-sm text-gray-700">PIX</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="payment_methods[]" value="boleto" class="mr-3 h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded" @checked(in_array('boleto', $quote->payment_methods ?? []))>
                                        <span class="text-sm text-gray-700">Boleto Bancário</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="payment_methods[]" value="card" class="mr-3 h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded" @checked(in_array('card', $quote->payment_methods ?? []))>
                                        <span class="text-sm text-gray-700">Cartão de Crédito</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Parcelamento no Cartão (até quantas vezes)</label>
                                <select name="card_installments" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                                    <option value="1" @selected($quote->card_installments == 1)>1x (À vista)</option>
                                    <option value="2" @selected($quote->card_installments == 2)>2x</option>
                                    <option value="3" @selected($quote->card_installments == 3)>3x</option>
                                    <option value="6" @selected($quote->card_installments == 6)>6x</option>
                                    <option value="12" @selected($quote->card_installments == 12)>12x</option>
                                    <option value="24" @selected($quote->card_installments == 24)>24x</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="flex items-center justify-end space-x-4">
                    <a href="{{ route('quotes.index') }}" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-150 ease-in-out">
                        Cancelar
                    </a>
                    <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Salvar Alterações
                    </button>
                </div>
            </form>
            
            <!-- Informações do Orçamento -->
            <div class="mt-6 bg-white shadow-lg rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        <strong>Total:</strong> R$ {{ number_format($quote->total_amount, 2, ',', '.') }}
                </div>
                    <div class="text-sm text-gray-600">
                        <strong>Status:</strong> 
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            @if($quote->status === 'awaiting') bg-yellow-100 text-yellow-800
                            @elseif($quote->status === 'approved') bg-green-100 text-green-800
                            @elseif($quote->status === 'not_approved') bg-red-100 text-red-800
                            @elseif($quote->status === 'canceled') bg-gray-100 text-gray-800
                            @else bg-blue-100 text-blue-800 @endif">
                            {{ ['awaiting'=>'Aguardando','approved'=>'Aprovado','not_approved'=>'Reprovado','canceled'=>'Cancelado'][$quote->status] ?? $quote->status }}
                        </span>
                </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    function quoteFormEdit(initialItems){
        return {
            items: initialItems && initialItems.length ? initialItems : [{ product_id:'', search:'', quantity:1, unit:'', unit_price:'', discount_value:0, description:'', delivery_date:'', delivery_date_error:'' }],
            suggestions: [],
            globalDiscount: {{ $quote->discount_total ?? 0 }},
            add(){ this.items.push({ product_id:'', search:'', quantity:1, unit:'', unit_price:'', discount_value:0, description:'', delivery_date:'', delivery_date_error:'' }); },
            remove(i){ this.items.splice(i,1); },
            clearAll(){ this.items = []; },
            async search(i){
                const term = this.items[i].search;
                if (!term || term.length < 2) { this.suggestions = []; return; }
                const url = `{{ route('products.search') }}?term=${encodeURIComponent(term)}`;
                try { const res = await fetch(url, { headers: {'Accept':'application/json'} }); this.suggestions = await res.json(); }
                catch(e){ this.suggestions = []; }
            },
            choose(i, s){
                this.items[i].product_id = s.id;
                this.items[i].search = s.name;
                this.items[i].unit = s.unit || '';
                this.items[i].unit_price = s.price || '';
                // Ajustar quantidade inicial baseada na unidade
                this.items[i].quantity = this.getInitialQuantity(s.unit);
                this.suggestions = [];
            },
            getQuantityStep(unit){
                if (!unit) return 0.001;
                const unitUpper = unit.toUpperCase();
                // Unidades inteiras
                if (['UN', 'PAR', 'PC', 'DZ', 'CX', 'UNID', 'UNIDADE', 'UNIDADES', 'PÇ', 'PECA', 'PEÇA', 'PEÇAS'].includes(unitUpper)) {
                    return 1;
                }
                // Unidades decimais (peso/volume)
                if (['KG', 'MG', 'GR', 'G', 'GRAMAS', 'GRAM', 'KILO', 'QUILO', 'QUILOS', 'L', 'ML', 'LITRO', 'LITROS', 'MILILITRO', 'MILILITROS'].includes(unitUpper)) {
                    return 0.001;
                }
                // Unidades decimais menores (para produtos mais precisos)
                if (['M', 'CM', 'MM', 'METRO', 'METROS', 'CENTIMETRO', 'CENTIMETROS', 'MILIMETRO', 'MILIMETROS'].includes(unitUpper)) {
                    return 0.01;
                }
                // Padrão: decimal pequeno
                return 0.001;
            },
            getQuantityMin(unit){
                if (!unit) return 0.001;
                const unitUpper = unit.toUpperCase();
                // Unidades inteiras
                if (['UN', 'PAR', 'PC', 'DZ', 'CX', 'UNID', 'UNIDADE', 'UNIDADES', 'PÇ', 'PECA', 'PEÇA', 'PEÇAS'].includes(unitUpper)) {
                    return 1;
                }
                // Unidades decimais
                return 0.001;
            },
            getInitialQuantity(unit){
                if (!unit) return 1;
                const unitUpper = unit.toUpperCase();
                // Unidades inteiras começam com 1
                if (['UN', 'PAR', 'PC', 'DZ', 'CX', 'UNID', 'UNIDADE', 'UNIDADES', 'PÇ', 'PECA', 'PEÇA', 'PEÇAS'].includes(unitUpper)) {
                    return 1;
                }
                // Unidades decimais começam com 0.001
                return 0.001;
            },
            getTodayDate(){
                return new Date().toISOString().split('T')[0];
            },
            validateDeliveryDate(i){
                const item = this.items[i];
                const today = new Date().toISOString().split('T')[0];
                if (item.delivery_date && item.delivery_date < today) {
                    item.delivery_date_error = 'Prazo de entrega não pode ser menor que hoje';
                } else {
                    item.delivery_date_error = '';
                }
            },
            subtotal(){ 
                return this.items.reduce((s,it)=> {
                    const itemTotal = (Number(it.quantity||0) * Number(it.unit_price||0)) - Number(it.discount_value||0);
                    return s + itemTotal;
                }, 0); 
            },
            total(){ return this.subtotal(); }
        }
    }
    function confirmStatusSelect(){
        var sel = document.getElementById('statusSelect');
        if (!sel) return true;
        var original = (sel.getAttribute('data-original')||'').toLowerCase().trim();
        var target = (sel.value||'').toLowerCase().trim();
        if (original === target) return true;
        if (target === 'approved') return confirm('Deseja aprovar este orçamento e converter em pedido?');
        if (target === 'canceled') return confirm('Deseja cancelar este orçamento?');
        if (target === 'not_approved') return confirm('Deseja reprovar este orçamento?');
        return true;
    }
</script>


