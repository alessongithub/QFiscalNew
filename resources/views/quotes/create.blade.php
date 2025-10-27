<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Novo Orçamento
            </h2>
            <a href="{{ route('quotes.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('quotes.store') }}" method="POST" class="space-y-6" x-data='quoteForm()'>
                @csrf
                
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
                                    <option value="">Selecione um cliente</option>
                                    @foreach($clients as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Número</label>
                                <input type="text" name="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Gerado automaticamente">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Título</label>
                                <input type="text" name="title" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Título do orçamento" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Validade do Orçamento</label>
                                <input type="date" name="validity_date" min="{{ date('Y-m-d') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" @change="validateValidityDate()">
                                <div x-show="validity_date_error" class="text-red-500 text-xs mt-1" x-text="validity_date_error"></div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                            <textarea name="notes" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Observações gerais do orçamento"></textarea>
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
                            <button type="button" @click="add()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Adicionar Item
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <template x-for="(it, i) in items" :key="i">
                            <div class="bg-gray-50 rounded-lg p-4 mb-4" @click.away="clearSuggestions(i)">
                                <div class="grid grid-cols-12 gap-4">
                                    <div class="col-span-5 relative">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Produto/Serviço</label>
                                        <input type="hidden" :name="`items[${i}][product_id]`" x-model="it.product_id">
                                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Digite para buscar..." x-model="it.search" @input.debounce.300ms="search(i)">
                                        <div class="absolute z-10 bg-white border border-gray-300 rounded-lg mt-1 w-full max-h-48 overflow-auto shadow-lg" x-show="getSuggestions(i).length">
                                            <template x-for="s in getSuggestions(i)" :key="s.id">
                                                <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0" @click="choose(i, s)">
                                                    <div class="font-medium text-gray-900" x-text="s.name"></div>
                                                    <div class="text-xs text-gray-500" x-text="`${s.unit || ''} • R$ ${Number(s.price).toFixed(2)}${s.type==='product' ? ' • Saldo: '+Number(s.balance??0).toFixed(3) : ''}`"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade</label>
                                        <input type="number" :step="getQuantityStep(it.unit)" :min="getQuantityMin(it.unit)" inputmode="decimal" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" x-model="it.quantity" :name="`items[${i}][quantity]`" @input="validateQty(i)">
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
                                    <div class="col-span-2 flex flex-col">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">V.Total</label>
                                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 font-semibold text-right" :value="((Number(it.quantity||0)*Number(it.unit_price||0)) - Number(it.discount_value||0)).toFixed(2)" readonly>
                                    </div>
                                    <div class="col-span-3 flex flex-col">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Prazo de Entrega</label>
                                        <input type="date" :min="getTodayDate()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-right" x-model="it.delivery_date" :name="`items[${i}][delivery_date]`" @change="validateDeliveryDate(i)">
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
                                <input type="number" step="0.01" min="0" name="discount_total" x-model="discount_total" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors" placeholder="0,00">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Total Final</label>
                                <div class="text-right font-bold text-2xl text-gray-900 py-3">
                                    R$ <span x-text="total().toFixed(2)"></span>
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
                                        <input type="checkbox" name="payment_methods[]" value="cash" class="mr-3 h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                                        <span class="text-sm text-gray-700">À Vista (Dinheiro)</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="payment_methods[]" value="pix" class="mr-3 h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                                        <span class="text-sm text-gray-700">PIX</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="payment_methods[]" value="boleto" class="mr-3 h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                                        <span class="text-sm text-gray-700">Boleto Bancário</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="payment_methods[]" value="card" class="mr-3 h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                                        <span class="text-sm text-gray-700">Cartão de Crédito</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Parcelamento no Cartão (até quantas vezes)</label>
                                <select name="card_installments" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                                    <option value="1">1x (À vista)</option>
                                    <option value="2">2x</option>
                                    <option value="3">3x</option>
                                    <option value="6">6x</option>
                                    <option value="12">12x</option>
                                    <option value="24">24x</option>
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
                        Salvar Orçamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

<script>
    function quoteForm(){
        return {
            items: [{ product_id:'', search:'', quantity:1, unit:'', unit_price:'', discount_value:0, description:'', delivery_date:'', delivery_date_error:'', type:'product', balance:0, name:'' }],
            suggestionsByIndex: {},
            discount_total: 0,
            validity_date_error: '',
            add(){ this.items.push({ product_id:'', search:'', quantity:1, unit:'', unit_price:'', discount_value:0, description:'', delivery_date:'', delivery_date_error:'' }); },
            remove(i){ this.items.splice(i,1); this.suggestionsByIndex = {}; },
            clearSuggestions(i){ this.suggestionsByIndex[i] = []; },
            getSuggestions(i){ return this.suggestionsByIndex[i] || []; },
            async search(i){
                const term = this.items[i].search;
                if (!term || term.length < 2) { this.suggestionsByIndex[i] = []; return; }
                const url = `{{ route('products.search') }}?term=${encodeURIComponent(term)}`;
                const acceptHeader = { headers: {'Accept':'application/json'} };
                try {
                    if (window.fetch) {
                        const res = await fetch(url, acceptHeader);
                        this.suggestionsByIndex[i] = await res.json();
                    } else {
                        // Fallback para navegadores sem fetch (Edge legado)
                        this.suggestionsByIndex[i] = await new Promise((resolve) => {
                            const xhr = new XMLHttpRequest();
                            xhr.open('GET', url, true);
                            xhr.setRequestHeader('Accept','application/json');
                            xhr.onreadystatechange = function(){
                                if (xhr.readyState === 4) {
                                    try { resolve(JSON.parse(xhr.responseText || '[]')); } catch(e){ resolve([]); }
                                }
                            };
                            xhr.send();
                        });
                    }
                } catch(e){ this.suggestionsByIndex[i] = []; }
            },
            choose(i, s){
                const it = this.items[i];
                it.product_id = s.id;
                it.search = s.name;
                it.unit = s.unit || '';
                it.unit_price = s.price || '';
                it.type = s.type || 'product';
                it.balance = Number(s.balance ?? 0);
                it.name = s.name;
                // Ajustar quantidade inicial baseada na unidade
                it.quantity = this.getInitialQuantity(s.unit);
                this.suggestionsByIndex[i] = [];
                this.validateQty(i);
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
            validateValidityDate(){
                const validityInput = document.querySelector('input[name="validity_date"]');
                const today = new Date().toISOString().split('T')[0];
                if (validityInput.value && validityInput.value < today) {
                    this.validity_date_error = 'Validade do orçamento não pode ser menor que hoje';
                } else {
                    this.validity_date_error = '';
                }
            },
            validateQty(i){
                const allowNegative = '{{ \App\Models\Setting::get('stock.allow_negative','0') }}'==='1';
                const it = this.items[i];
                if (!allowNegative && it && it.type==='product'){
                    const balance = Number(it.balance ?? 0);
                    const q = Number(it.quantity || 0);
                    if (q > balance + 1e-6){
                        alert(`Estoque insuficiente para ${it.name || 'produto'}. Saldo: ${balance.toFixed(3)}`);
                        it.quantity = balance > 0 ? balance : 0;
                    }
                    if (balance <= 0 && q > 0){
                        alert(`Item sem estoque disponível.`);
                        it.quantity = 0;
                    }
                }
            },
            subtotal(){ 
                return this.items.reduce((s,it)=> {
                    const itemTotal = (Number(it.quantity||0) * Number(it.unit_price||0)) - Number(it.discount_value||0);
                    return s + itemTotal;
                }, 0); 
            },
            total(){ return this.subtotal() - this.discount_total; }
        }
    }
</script>


