<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDV - Ponto de Venda</title>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50">
    @if(isset($reopenOrder))
        <script>
            window.__REOPEN = {
                order: @json($reopenOrder),
                items: @json($reopenItems ?? []),
                client: @json($reopenClient ?? null)
            };
        </script>
    @endif
    <div class="min-h-screen" x-data="pos('{{ route('products.search') }}','{{ route('clients.search') }}')" x-init="initReopen()" @keydown.window="if($event.key==='F4'){ $event.preventDefault(); window.location='{{ route('pos.sales') }}'; }">
        <!-- Header Moderno -->
        <div class="bg-white shadow-lg border-b-4 border-green-500">
            <div class="max-w-7xl mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="bg-green-100 p-3 rounded-xl">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m-4-4h8M5 7h14a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Ponto de Venda</h1>
                            <p class="text-gray-600">Sistema de vendas rápido e intuitivo</p>
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('pos.sales') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Histórico (F4)
                        </a>
                        <button @click="reset()" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Nova Venda
                        </button>
                    </div>
                </div>
                </div>
            </div>

        <div class="max-w-7xl mx-auto p-6">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Área Principal (3/4) -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- Busca de Produtos -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="bg-blue-100 p-2 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Buscar Produto</h3>
                        </div>
                        <div class="relative">
                            <input 
                                x-model="term" 
                                @keydown.enter.prevent="addBySearch()" 
                                @input.debounce.300ms="search()" 
                                placeholder="Digite o código EAN, nome do produto ou escaneie o QR Code..." 
                                class="w-full text-xl p-4 border-2 border-gray-300 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-100 transition-all font-mono"
                                autofocus
                            >
                            <div class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M12 12h.01M12 12h-4.01M12 12h-.01"/>
                                </svg>
                            </div>
                            
                            <!-- Autocomplete Dropdown -->
                            <div x-show="results.length > 0" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute z-50 w-full mt-2 bg-white border border-gray-200 rounded-xl shadow-2xl max-h-80 overflow-y-auto">
                                <template x-for="result in results" :key="result.id">
                                    <div class="p-4 hover:bg-green-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors" @click="addItem(result)">
                                        <div class="flex justify-between items-center">
                                            <div class="flex-1">
                                                <div class="font-semibold text-gray-900" x-text="result.name"></div>
                                                <div class="text-sm text-gray-600 mt-1">
                                                    <span class="bg-gray-100 px-2 py-1 rounded text-xs mr-2" x-text="'SKU: ' + (result.sku || 'N/A')"></span>
                                                    <span class="bg-blue-100 px-2 py-1 rounded text-xs" x-text="result.unit"></span>
                                                </div>
                                            </div>
                                            <div class="text-right ml-4">
                                                <div class="text-2xl font-bold text-green-600" x-text="formatMoney(result.price)"></div>
                                                <div class="text-xs text-gray-500">por unidade</div>
                                            </div>
                                        </div>
                                </div>
                            </template>
                            </div>
                        </div>
                        <div class="mt-3 text-sm text-gray-500 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Pressione Enter para adicionar o primeiro resultado
                        </div>
                    </div>

                    <!-- Lista de Produtos no Carrinho -->
                    <div class="bg-white rounded-xl shadow-lg">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-purple-100 p-2 rounded-lg">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 6M7 13l-1.5-6m0 0L5.4 5M7 13v6a2 2 0 002 2h6a2 2 0 002-2v-6M7 13H3"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900">Carrinho de Compras</h3>
                                </div>
                                <div class="bg-green-100 px-3 py-1 rounded-full">
                                    <span class="text-green-800 font-medium" x-text="items.length + (items.length === 1 ? ' item' : ' itens')"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <!-- Estado Vazio -->
                            <div x-show="items.length === 0" class="text-center py-12">
                                <div class="text-6xl mb-4">🛒</div>
                                <h4 class="text-xl font-semibold text-gray-700 mb-2">Carrinho Vazio</h4>
                                <p class="text-gray-500">Adicione produtos usando a busca acima</p>
                            </div>
                            
                            <!-- Lista de Produtos -->
                            <div x-show="items.length > 0" class="space-y-4">
                                <template x-for="(item, idx) in items" :key="idx">
                                    <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-900" x-text="item.name"></h4>
                                                <p class="text-sm text-gray-600" x-text="'Unidade: ' + item.unit"></p>
                                            </div>
                                            <div class="flex items-center space-x-4">
                                                <!-- Controles de Quantidade -->
                                                <div class="flex items-center space-x-2">
                                                    <button @click="item.quantity = Math.max(0.001, item.quantity - 1); recalc()" class="w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors">-</button>
                                                    <input type="number" min="0.001" step="0.001" x-model.number="item.quantity" @input="recalc()" class="w-20 text-center border border-gray-300 rounded-lg py-2">
                                                    <button @click="item.quantity += 1; recalc()" class="w-8 h-8 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors">+</button>
                                                </div>
                                                
                                                <!-- Preço Unitário -->
                                                <div class="text-right min-w-0">
                                                    <input type="number" min="0" step="0.01" x-model.number="item.unit_price" @input="recalc()" class="w-28 text-right border border-gray-300 rounded-lg py-2 font-mono">
                                                    <div class="text-xs text-gray-500">por unidade</div>
                                                </div>
                                                
                                                <!-- Total do Item -->
                                                <div class="text-right min-w-0">
                                                    <div class="text-xl font-bold text-green-600" x-text="formatMoney(item.quantity * item.unit_price)"></div>
                                                    <div class="text-xs text-gray-500">total</div>
                    </div>

                                                <!-- Remover -->
                                                <button @click="remove(idx)" class="w-10 h-10 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg flex items-center justify-center transition-colors">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Painel Lateral (1/4) -->
                <div class="space-y-6">
                    <!-- Cliente -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="bg-indigo-100 p-2 rounded-lg">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 01-8 0 4 4 0 118 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Cliente</h3>
                        </div>
                        
                        <div class="relative">
                            <input 
                                x-model="clientSearch" 
                                @input.debounce.300ms="searchClient()" 
                                placeholder="Nome ou CPF/CNPJ..." 
                                class="w-full p-3 border border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all"
                            >
                            
                            <!-- Cliente Selecionado -->
                            <div x-show="clientSelected" class="mt-3 p-3 bg-indigo-50 border border-indigo-200 rounded-lg">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-semibold text-indigo-900" x-text="clientSelected?.name"></div>
                                        <div class="text-sm text-indigo-700" x-text="clientSelected?.cpf_cnpj"></div>
                                    </div>
                                    <button @click="clearClient()" class="text-indigo-600 hover:text-indigo-800">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Autocomplete Cliente -->
                            <div x-show="clientResults.length > 0 && !clientSelected" 
                                 x-transition
                                 class="absolute z-40 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-y-auto">
                                <template x-for="client in clientResults" :key="client.id">
                                    <div class="p-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-100 last:border-b-0" @click="selectClient(client)">
                                        <div class="font-semibold text-gray-900" x-text="client.name"></div>
                                        <div class="text-sm text-gray-600" x-text="client.cpf_cnpj"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Resumo -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Resumo</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-semibold" x-text="formatMoney(subtotal)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Frete:</span>
                                <span class="text-green-600 font-medium">Por conta do cliente</span>
                            </div>
                            <div class="border-t pt-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-xl font-bold text-gray-900">Total:</span>
                                    <span class="text-2xl font-bold text-green-600" x-text="formatMoney(total)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    

                    <!-- Finalizar (abre modal) -->
                    <button 
                        @click="showPaymentModal = true" 
                        :disabled="items.length === 0"
                        :class="items.length === 0 ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700 hover:scale-105'"
                        class="w-full p-6 text-white rounded-xl font-bold text-lg shadow-lg transition-all transform focus:outline-none focus:ring-4 focus:ring-green-300"
                    >
                        <div class="flex items-center justify-center">💳 Pagamento e Finalização — <span class="ml-2" x-text="formatMoney(total)"></span></div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de Pagamento e Finalização (dentro do x-data) -->
        <div x-cloak x-show="showPaymentModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black bg-opacity-50" @click="showPaymentModal=false"></div>
        <div class="relative bg-white w-full max-w-4xl mx-4 rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 flex items-center justify-between">
                <h3 class="text-white text-lg font-semibold">Pagamento e Finalização</h3>
                <button class="text-white hover:text-green-100" @click="showPaymentModal=false">✕</button>
            </div>
            <div class="p-6 space-y-6 max-h-[80vh] overflow-y-auto">
                <div class="bg-gray-50 rounded-xl p-4 flex items-center justify-between">
                    <div class="text-gray-700">Total da venda</div>
                    <div class="text-2xl font-bold text-green-700" x-text="formatMoney(total)"></div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                        <select x-model="payment_type" class="w-full border border-gray-300 rounded-lg p-3 focus:border-green-500 focus:ring-4 focus:ring-green-100">
                            <option value="immediate">💰 À Vista</option>
                            <option value="invoice">📋 Parcelado</option>
                            <option value="mixed">🔀 Misto</option>
                            </select>
                    </div>

                    <!-- À Vista -->
                            <div x-show="payment_type==='immediate'">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Forma</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <button @click="payment_method='cash'" :class="payment_method==='cash' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-white border-gray-300'" class="p-3 border rounded-lg text-center hover:bg-gray-50 transition-colors">💵 Dinheiro</button>
                            <button @click="payment_method='card'" :class="payment_method==='card' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-white border-gray-300'" class="p-3 border rounded-lg text-center hover:bg-gray-50 transition-colors">💳 Cartão</button>
                            <button @click="payment_method='pix'" :class="payment_method==='pix' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-white border-gray-300'" class="p-3 border rounded-lg text-center hover:bg-gray-50 transition-colors">🔲 PIX</button>
                        </div>
                    </div>

                    <!-- Parcelado -->
                    <div x-show="payment_type==='invoice'" class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Parcelas</label>
                                <input type="number" min="1" max="36" x-model.number="installments" class="w-full border border-gray-300 rounded-lg p-3">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Forma das Parcelas</label>
                                <select x-model="installment_method" class="w-full border border-gray-300 rounded-lg p-3">
                                    <option value="boleto">📄 Boleto</option>
                                    <option value="card">💳 Cartão</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center justify-between bg-blue-50 p-3 rounded-lg">
                            <div class="text-sm text-gray-700">
                                <strong>Prévia:</strong> <span x-text="installments + 'x de ' + formatMoney(total/installments)"></span>
                            </div>
                            <label class="inline-flex items-center text-sm text-gray-700">
                                <input type="checkbox" x-model="useManualSchedule" class="mr-2 rounded border-gray-300 text-green-600 focus:ring-green-500"> Definir vencimentos manualmente
                            </label>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 space-y-3" x-show="useManualSchedule">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">1º vencimento</label>
                                    <input type="date" x-model="firstDue" class="w-full border border-gray-300 rounded-lg p-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Intervalo (dias)</label>
                                    <input type="number" min="1" x-model.number="intervalDays" class="w-full border border-gray-300 rounded-lg p-2">
                                </div>
                                <div class="flex items-end">
                                    <button type="button" @click="generateSchedule('invoice')" class="w-full p-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">Gerar parcelas</button>
                                </div>
                            </div>
                            <div x-show="schedule.length>0" class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-600">
                                            <th class="py-2 pr-3">Parcela</th>
                                            <th class="py-2 pr-3">Vencimento</th>
                                            <th class="py-2 pr-3">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(sc, i) in schedule" :key="i">
                                            <tr class="border-t">
                                                <td class="py-2 pr-3">#<span x-text="i+1"></span> / <span x-text="schedule.length"></span></td>
                                                <td class="py-2 pr-3"><input type="date" x-model="sc.due_date" class="border border-gray-300 rounded p-1"></td>
                                                <td class="py-2 pr-3"><input type="number" step="0.01" min="0" x-model.number="sc.amount" class="border border-gray-300 rounded p-1 text-right"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                                <div class="text-right mt-2 text-sm" :class="scheduleSumMismatch ? 'text-red-600' : 'text-green-700'">
                                    Soma das parcelas: <strong x-text="formatMoney(sumSchedule)"></strong>
                                    <span x-show="scheduleSumMismatch">(deve ser igual a <span x-text="formatMoney(total)"></span>)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Misto -->
                    <div x-show="payment_type==='mixed'" class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Entrada</label>
                                <input type="number" min="0" step="0.01" x-model.number="entry_amount" class="w-full border border-gray-300 rounded-lg p-3">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Forma da Entrada</label>
                                <select x-model="entry_method" class="w-full border border-gray-300 rounded-lg p-3">
                                    <option value="cash">💵 Dinheiro</option>
                                    <option value="card">💳 Cartão</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Forma das Parcelas</label>
                                <select x-model="installment_method" class="w-full border border-gray-300 rounded-lg p-3">
                                    <option value="card">💳 Cartão</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Parcelas restantes</label>
                                <input type="number" min="1" max="36" x-model.number="installments" class="w-full border border-gray-300 rounded-lg p-3">
                            </div>
                            <div class="sm:col-span-2 flex items-end justify-between">
                                <div class="text-sm text-gray-700 bg-yellow-50 px-3 py-2 rounded-lg">
                                    Restante: <strong x-text="formatMoney(total - entry_amount)"></strong> — <span x-text="installments + 'x de ' + formatMoney((total - entry_amount)/installments)"></span>
                                </div>
                                <label class="inline-flex items-center text-sm text-gray-700">
                                    <input type="checkbox" x-model="useManualScheduleMixed" class="mr-2 rounded border-gray-300 text-green-600 focus:ring-green-500"> Definir vencimentos manualmente
                                </label>
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 space-y-3" x-show="useManualScheduleMixed">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">1º vencimento</label>
                                    <input type="date" x-model="mixedFirstDue" class="w-full border border-gray-300 rounded-lg p-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Intervalo (dias)</label>
                                    <input type="number" min="1" x-model.number="mixedIntervalDays" class="w-full border border-gray-300 rounded-lg p-2">
                                </div>
                                <div class="flex items-end">
                                    <button type="button" @click="generateSchedule('mixed')" class="w-full p-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">Gerar parcelas</button>
                                </div>
                            </div>
                            <div x-show="mixedSchedule.length>0" class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-600">
                                            <th class="py-2 pr-3">Parcela</th>
                                            <th class="py-2 pr-3">Vencimento</th>
                                            <th class="py-2 pr-3">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(sc, i) in mixedSchedule" :key="i">
                                            <tr class="border-t">
                                                <td class="py-2 pr-3">#<span x-text="i+1"></span> / <span x-text="mixedSchedule.length"></span></td>
                                                <td class="py-2 pr-3"><input type="date" x-model="sc.due_date" class="border border-gray-300 rounded p-1"></td>
                                                <td class="py-2 pr-3"><input type="number" step="0.01" min="0" x-model.number="sc.amount" class="border border-gray-300 rounded p-1 text-right"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                                <div class="text-right mt-2 text-sm" :class="mixedScheduleSumMismatch ? 'text-red-600' : 'text-green-700'">
                                    Soma das parcelas: <strong x-text="formatMoney(sumMixedSchedule)"></strong>
                                    <span x-show="mixedScheduleSumMismatch">(deve ser igual a <span x-text="formatMoney(total - entry_amount)"></span>)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t">
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg" @click="showPaymentModal=false">Cancelar</button>
                    <button 
                        @click="finalize()" 
                        :disabled="items.length === 0 || (payment_type==='invoice' && useManualSchedule && (schedule.length===0 || scheduleSumMismatch)) || (payment_type==='mixed' && useManualScheduleMixed && (mixedSchedule.length===0 || mixedScheduleSumMismatch))"
                        :class="(items.length === 0 || (payment_type==='invoice' && useManualSchedule && (schedule.length===0 || scheduleSumMismatch)) || (payment_type==='mixed' && useManualScheduleMixed && (mixedSchedule.length===0 || mixedScheduleSumMismatch))) ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
                        class="px-6 py-3 text-white font-semibold rounded-lg shadow"
                    >
                        Finalizar venda — <span x-text="formatMoney(total)"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal PIX -->
        <div x-cloak x-show="showPixModal" x-transition.opacity class="fixed inset-0 z-60 flex items-center justify-center">
            <div class="absolute inset-0 bg-black bg-opacity-60"></div>
            <div class="relative bg-white w-full max-w-2xl mx-4 rounded-2xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-white text-lg font-semibold">Pague com PIX</h3>
                    <button class="text-white hover:text-emerald-100" @click="cancelPix()">✕</button>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col items-center justify-center">
                        <template x-if="pix_loading">
                            <div class="w-64 h-64 border rounded-lg flex items-center justify-center text-gray-500">Gerando QR Code...</div>
                        </template>
                        <template x-if="!pix_loading && pix_qr_base64">
                            <img :src="'data:image/png;base64,' + pix_qr_base64" alt="QR Code PIX" class="w-64 h-64 border rounded-lg shadow" />
                        </template>
                        <div class="mt-4 text-sm text-gray-600">Aponte a câmera do celular para o QR Code</div>
                    </div>
                    <div>
                        <div class="text-gray-700 mb-2">Copia e Cola</div>
                        <textarea x-ref="pixCopy" x-text="pix_qr_code" readonly class="w-full h-36 p-3 border rounded-lg text-xs"></textarea>
                        <button class="mt-2 px-3 py-2 bg-gray-800 text-white rounded-lg" @click="copyPix()">Copiar código</button>
                        <div class="mt-6 flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                            <div class="text-gray-700" x-text="'Aguardando (' + formatCountdown() + ')' "></div>
                        </div>
                        <div class="mt-4 text-sm text-gray-500">Total: <strong class="text-gray-900" x-text="formatMoney(total)"></strong></div>
                        <div class="mt-6 flex gap-2">
                            <button class="px-3 py-2 bg-gray-200 rounded" @click="reissuePix()">Reemitir PIX</button>
                            <a :href="'{{ route('pos.sales') }}'" class="px-3 py-2 bg-gray-100 rounded">Abrir vendas (F4)</a>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t bg-gray-50 flex items-center justify-end">
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg" @click="cancelPix()">Cancelar</button>
                </div>
            </div>
        </div>

        <!-- Modal de Impressão (pagamentos não-PIX) -->
        <div x-cloak x-show="showPrintModal" x-transition.opacity class="fixed inset-0 z-60 flex items-center justify-center">
            <div class="absolute inset-0 bg-black bg-opacity-60"></div>
            <div class="relative bg-white w-full max-w-md mx-4 rounded-2xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-white text-lg font-semibold">✅ Venda concluída com sucesso!</h3>
                    <button class="text-white hover:text-green-100" @click="closePrintModal()">✕</button>
                </div>
                <div class="p-6">
                    <div class="text-center mb-6">
                        <div class="text-gray-700 mb-2">Pedido: <strong class="text-gray-900">#<span x-text="printOrderId"></span></strong></div>
                        <div class="text-gray-700">Total: <strong class="text-gray-900 text-xl" x-text="formatMoney(printTotal)"></strong></div>
                    </div>
                    <div class="flex gap-3 justify-center">
                        <button class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium" @click="printReceipt()">
                            🖨️ Imprimir Recibo
                        </button>
                        <button class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium" @click="closePrintModal()">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </div>

    <script>
        function pos(searchUrl, clientUrl) {
        return {
                term: '', 
                results: [], 
                items: [], 
                payment_type: 'immediate', 
                payment_method: 'cash', 
                installments: 3, 
                installment_method: 'card', 
                entry_method: 'cash',
                entry_amount: 0, 
                subtotal: 0, 
                total: 0,
                clientSearch: '', 
                clientResults: [], 
                clientSelected: null,
                // Agendamento manual
                useManualSchedule: false,
                firstDue: new Date(Date.now() + 30*24*60*60*1000).toISOString().slice(0,10),
                intervalDays: 30,
                schedule: [],
                useManualScheduleMixed: false,
                mixedFirstDue: new Date(Date.now() + 30*24*60*60*1000).toISOString().slice(0,10),
                mixedIntervalDays: 30,
                mixedSchedule: [],
                // Modal de pagamento
                showPaymentModal: false,
                // PIX state
                showPixModal: false,
                pix_qr_code: '',
                pix_qr_base64: '',
                pix_status_url: '',
                pix_print_url: '',
                pix_receipt_url: '',
                pix_auto_print: {{ \App\Models\Setting::get('pos.auto_print_on_payment','0')==='1' ? 'true' : 'false' }},
                countdownEndsAt: null,
                pixExpiration: null,
                pollHandle: null,
                tickHandle: null,
                nowTs: Date.now(),
                lastOrderId: null,
                pix_loading: false,
                // Modal de impressão (não-PIX)
                showPrintModal: false,
                printOrderId: null,
                printTotal: 0,
                printUrl: '',
                receiptUrl: '',
                initReopen(){
                    try{
                        const r = window.__REOPEN;
                        if (!r) return;
                        if (Array.isArray(r.items) && r.items.length){
                            this.items = r.items.map(it => ({
                                product_id: it.product_id,
                                name: it.name,
                                unit: it.unit,
                                quantity: parseFloat(it.quantity||1),
                                unit_price: parseFloat(it.unit_price||0)
                            }));
                            this.recalc();
                        }
                        if (r.client){
                            this.clientSelected = r.client;
                            this.clientSearch = r.client.name;
                        }
                        if (r.order && r.order.id){ this.lastOrderId = r.order.id; }
                    }catch(e){}
                },

                formatMoney(v) { 
                    return 'R$ ' + (v || 0).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.'); 
                },

                reset() { 
                    this.term = ''; 
                    this.results = []; 
                    this.items = []; 
                    this.payment_type = 'immediate'; 
                    this.payment_method = 'cash'; 
                    this.installments = 3; 
                    this.installment_method = 'card'; 
                    this.entry_method = 'cash';
                    this.entry_amount = 0; 
                    this.clientSearch = '';
                    this.clientResults = [];
                    this.clientSelected = null;
                    this.recalc(); 
                    this.stopPixPolling();
                    this.showPixModal = false;
                    this.pix_qr_code = '';
                    this.pix_qr_base64 = '';
                    this.pix_status_url = '';
                    this.pix_print_url = '';
                    this.pix_receipt_url = '';
                    this.countdownEndsAt = null;
                    this.pixExpiration = null;
                    this.stopTick();
                    this.lastOrderId = null;
                },

                async search() {
                    if (!this.term || this.term.length < 2) { 
                        this.results = []; 
                        return; 
                    }
                    try {
                        const response = await fetch(searchUrl + '?term=' + encodeURIComponent(this.term));
                        if (response.ok) {
                            this.results = await response.json();
                        } else {
                            console.error('Erro na busca:', response.status);
                            this.results = [];
                        }
                    } catch (error) {
                        console.error('Erro de conexão na busca:', error);
                        this.results = [];
                    }
                },

                async searchClient() {
                    if (!this.clientSearch || this.clientSearch.length < 2) { 
                        this.clientResults = []; 
                        return; 
                    }
                    try {
                        const response = await fetch(clientUrl + '?term=' + encodeURIComponent(this.clientSearch));
                        if (response.ok) {
                            this.clientResults = await response.json();
                        } else {
                            console.error('Erro na busca de cliente:', response.status);
                            this.clientResults = [];
                        }
                    } catch (error) {
                        console.error('Erro de conexão na busca de cliente:', error);
                        this.clientResults = [];
                    }
                },

                selectClient(c) { 
                    this.clientSelected = c; 
                    this.clientResults = []; 
                    this.clientSearch = c.name; 
                },

                clearClient() {
                    this.clientSelected = null;
                    this.clientSearch = '';
                    this.clientResults = [];
                },

                addBySearch() { 
                    if (this.results.length) { 
                        this.addItem(this.results[0]); 
                    } 
                },

                addItem(p) {
                    const existingIndex = this.items.findIndex(item => item.product_id === p.id);
                    if (existingIndex !== -1) {
                        this.items[existingIndex].quantity += 1;
                    } else {
                        this.items.push({ 
                            product_id: p.id, 
                            name: p.name, 
                            unit: p.unit, 
                            quantity: 1, 
                            unit_price: parseFloat(p.price) 
                        });
                    }
                    this.results = []; 
                    this.term = ''; 
                    this.recalc();
                },

                remove(idx) { 
                    this.items.splice(idx, 1); 
                    this.recalc(); 
                },

                recalc() {
                    this.subtotal = this.items.reduce((s, it) => s + (parseFloat(it.quantity || 0) * parseFloat(it.unit_price || 0)), 0);
                this.total = this.subtotal;
            },
            get sumSchedule() {
                return this.schedule.reduce((s, it) => s + (parseFloat(it.amount || 0)), 0);
            },
            get scheduleSumMismatch() {
                return Math.abs(this.sumSchedule - this.total) > 0.01;
            },
            get sumMixedSchedule() {
                const remaining = Math.max(0, (this.total - (parseFloat(this.entry_amount)||0)));
                return this.mixedSchedule.reduce((s, it) => s + (parseFloat(it.amount || 0)), 0);
            },
            get mixedScheduleSumMismatch() {
                const remaining = Math.max(0, (this.total - (parseFloat(this.entry_amount)||0)));
                return Math.abs(this.sumMixedSchedule - remaining) > 0.01;
            },
            generateSchedule(mode){
                const count = Math.max(1, parseInt(this.installments||1));
                const baseTotal = mode==='mixed' ? Math.max(0, (this.total - (parseFloat(this.entry_amount)||0))) : this.total;
                const base = Math.floor((baseTotal / count) * 100) / 100;
                const remainder = Math.round((baseTotal - (base * count)) * 100) / 100;
                const start = new Date(mode==='mixed' ? this.mixedFirstDue : this.firstDue);
                const interval = mode==='mixed' ? parseInt(this.mixedIntervalDays||30) : parseInt(this.intervalDays||30);
                const arr = [];
                for(let i=1;i<=count;i++){
                    const due = new Date(start.getTime());
                    due.setDate(start.getDate() + (i-1)*interval);
                    const value = base + (i===count ? remainder : 0);
                    arr.push({ amount: value, due_date: due.toISOString().slice(0,10) });
                }
                if (mode==='mixed') { this.mixedSchedule = arr; } else { this.schedule = arr; }
            },

                startPixCountdown(expiresAtIso) {
                    try {
                        const exp = expiresAtIso ? new Date(expiresAtIso) : new Date(Date.now() + 5*60*1000);
                        this.pixExpiration = exp;
                        this.countdownEndsAt = new Date(Date.now() + 30*1000);
                        this.startTick();
                    } catch(_){ this.countdownEndsAt = new Date(Date.now() + 5*60*1000); }
                },
                formatCountdown() {
                    if (!this.countdownEndsAt) return '00:30';
                    // estende de 30 em 30 até expirar no MP
                    const now = this.nowTs || Date.now();
                    if (this.pixExpiration && now > this.countdownEndsAt.getTime() && now < this.pixExpiration.getTime()) {
                        this.countdownEndsAt = new Date(Math.min(this.pixExpiration.getTime(), now + 30*1000));
                    }
                    const diff = Math.max(0, Math.floor((this.countdownEndsAt.getTime() - now)/1000));
                    const m = String(Math.floor(diff/60)).padStart(2,'0');
                    const s = String(diff%60).padStart(2,'0');
                    return m+':'+s;
                },
                startTick(){ this.stopTick(); this.tickHandle = setInterval(()=>{ this.nowTs = Date.now(); }, 1000); },
                stopTick(){ if (this.tickHandle){ clearInterval(this.tickHandle); this.tickHandle=null; } },
                startPixPolling() {
                    this.stopPixPolling();
                    this.pollHandle = setInterval(async () => {
                        try {
                            const r = await fetch(this.pix_status_url, { headers: { 'Accept':'application/json' } });
                            if (r.ok) {
                                const j = await r.json();
                                if (j.approved) {
                                    this.stopPixPolling();
                                    this.showPixModal = false;
                                    // Abre comprovante ou imprime direto
                                    if (this.pix_auto_print) {
                                        window.open(this.pix_print_url, '_blank');
                                    } else {
                                        window.open(this.pix_receipt_url, '_blank');
                                    }
                                    this.reset();
                                }
                            }
                        } catch (e) { /* ignore transient errors */ }
                    }, 2000);
                },
                stopPixPolling() { if (this.pollHandle) { clearInterval(this.pollHandle); this.pollHandle = null; } },
                cancelPix() { this.stopPixPolling(); this.showPixModal = false; },
                copyPix() { try { const t=this.$refs.pixCopy; t.select(); document.execCommand('copy'); } catch(_){} },
                printReceipt() {
                    if (this.printUrl) {
                        window.open(this.printUrl, '_blank');
                    }
                    this.closePrintModal();
                },
                closePrintModal() {
                    this.showPrintModal = false;
                    this.printOrderId = null;
                    this.printTotal = 0;
                    this.printUrl = '';
                    this.receiptUrl = '';
                    this.reset();
                },
                async reissuePix(){
                    try{
                        const orderId = this.lastOrderId;
                        if (!orderId) return;
                        const r = await fetch(`/pos/${orderId}/pay-pix`, { method:'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept':'application/json' } });
                        const j = await r.json();
                        if (!j.ok) { alert('❌ ' + (j.error||'Falha reemitindo PIX')); return; }
                        this.pix_qr_code = j.qr_code;
                        this.pix_qr_base64 = j.qr_code_base64;
                        this.pix_status_url = j.status_url;
                        this.pix_print_url = j.print_url;
                        this.pix_receipt_url = j.receipt_url;
                        this.showPixModal = true;
                        this.startPixCountdown(j.expires_at);
                        this.startPixPolling();
                    } catch(e){ alert('❌ ' + (e?.message||'Falha reemitindo PIX')); }
                },

                async finalize() {
                    const requireClient = '{{ \App\Models\Setting::get('pos.require_client','0') }}' === '1';
                    
                    if (requireClient && !this.clientSelected) { 
                        alert('⚠️ Selecione um cliente para continuar.'); 
                        return; 
                    }
                    
                    if (this.items.length === 0) { 
                        alert('⚠️ Adicione produtos ao carrinho para finalizar a venda.'); 
                        return; 
                    }

                    if (this.payment_type === 'mixed' && this.entry_amount >= this.total) {
                        alert('⚠️ O valor da entrada não pode ser maior ou igual ao total.');
                        return;
                    }

                    const payload = { 
                        items: this.items, 
                        payment_method: this.payment_method, 
                        payment_type: this.payment_type, 
                        installments: this.installments, 
                        installment_method: this.installment_method, 
                        entry_method: this.entry_method,
                        entry_amount: this.entry_amount 
                    };
                    if (this.lastOrderId) { payload.order_id = this.lastOrderId; }
                    // Anexa cronograma manual se houver
                    if (this.payment_type==='invoice' && this.useManualSchedule && this.schedule.length>0 && !this.scheduleSumMismatch) {
                        payload.schedule = this.schedule;
                    }
                    if (this.payment_type==='mixed') {
                        const remaining = Math.max(0, (this.total - (parseFloat(this.entry_amount)||0)));
                        if (this.useManualScheduleMixed && this.mixedSchedule.length>0 && !this.mixedScheduleSumMismatch) {
                            payload.schedule = this.mixedSchedule;
                        }
                    }

                    if (this.clientSelected) { 
                        payload.client_id = this.clientSelected.id; 
                    }

                    try {
                        if (this.payment_type==='immediate' && this.payment_method==='pix') {
                            this.pix_loading = true;
                            this.showPaymentModal = false;
                            this.showPixModal = true;
                        }
                        const response = await fetch("{{ route('pos.store') }}", { 
                            method: 'POST', 
                            headers: { 
                                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }, 
                            body: JSON.stringify(payload)
                        });

                        if (!response.ok) {
                            let msg = `HTTP ${response.status}: ${response.statusText}`;
                            try {
                                const err = await response.json();
                                if (err) {
                                    if (err.error || err.message) {
                                        msg = err.error || err.message;
                                    } else if (err.errors) {
                                        const vals = Object.values(err.errors);
                                        if (vals && vals.length) {
                                            const first = Array.isArray(vals[0]) ? vals[0][0] : vals[0];
                                            if (first) msg = first;
                                        }
                                    }
                                }
                            } catch (_) {
                                try { const t = await response.text(); if (t) msg = t; } catch(e) {}
                            }
                            alert('❌ Erro: ' + msg);
                            return;
                        }

                        const data = await response.json();
                        
                        if (data.ok) {
                            this.lastOrderId = data.order_id; // Define o lastOrderId
                            if (data.is_pix) {
                                // Exibir modal PIX
                                this.pix_qr_code = data.qr_code;
                                this.pix_qr_base64 = data.qr_code_base64;
                                this.pix_status_url = data.status_url;
                                this.pix_print_url = data.print_url;
                                this.pix_receipt_url = data.receipt_url;
                                if (typeof data.auto_print === 'boolean') { this.pix_auto_print = data.auto_print; }
                                this.pix_loading = false;
                                this.showPaymentModal = false;
                                this.$nextTick(() => { this.showPixModal = true; });
                                this.startPixCountdown(data.expires_at);
                                this.startPixPolling();
                            } else {
                                // Pagamento não-PIX (dinheiro/cartão)
                                const autoPrint = data.auto_print === true;
                                if (autoPrint) {
                                    // Imprimir automaticamente
                                    if (data.print_url) {
                                        window.open(data.print_url, '_blank');
                                    }
                                    this.reset();
                                } else {
                                    // Mostrar modal com opção de imprimir
                                    this.showPrintModal = true;
                                    this.printOrderId = data.order_id;
                                    this.printTotal = data.total;
                                    this.printUrl = data.print_url || '';
                                    this.receiptUrl = data.receipt_url || '';
                                }
                            }
                        } else { 
                            if (this.pix_loading) { this.pix_loading = false; this.showPixModal = false; }
                            alert('❌ Erro: ' + (data.error || 'Falha desconhecida')); 
                        }
                    } catch (error) {
                        console.error('Erro ao finalizar venda:', error);
                        if (this.pix_loading) { this.pix_loading = false; this.showPixModal = false; }
                        alert('❌ Erro: ' + (error?.message || 'Falha inesperada. Tente novamente.'));
                    }
            }
        }
    }
    </script>
</body>
</html>