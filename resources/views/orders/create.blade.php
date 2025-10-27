<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo Pedido</h2>
                    <p class="text-sm text-gray-500">Crie um novo pedido de venda</p>
                </div>
            </div>
            <a href="{{ route('orders.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
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
                            <p class="text-blue-100 text-sm">Preencha os dados básicos do pedido</p>
                        </div>
                    </div>
                </div>

                <!-- Formulário -->
                <form action="{{ route('orders.store') }}" method="POST" class="p-6 space-y-6" x-data='orderForm()'>
                    @csrf
                    
                    <!-- Informações básicas -->
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
                            <select name="client_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                                <option value="">Selecione um cliente</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
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
                            <input type="text" name="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Gerado automaticamente">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                Título do Pedido
                            </span>
                        </label>
                        <input type="text" name="title" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Digite o título do pedido" required>
                    </div>

                    <!-- Seção de Itens -->
                    <div class="border-t border-gray-200 pt-6" x-data='orderItems()'>
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-green-100 rounded-lg">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">Itens do Pedido</h3>
                                    <p class="text-sm text-gray-500">Adicione produtos ou serviços ao pedido</p>
                                </div>
                            </div>
                            <button type="button" @click="add()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Adicionar Item
                            </button>
                        </div>
                        <!-- Lista de Itens -->
                        <div class="space-y-4">
                            <template x-for="(it, i) in items" :key="i">
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4" @click.away="clearSuggestions(i)">
                                    <div class="grid grid-cols-12 gap-4">
                                        <div class="col-span-4 relative">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                    </svg>
                                                    Produto/Serviço
                                                </span>
                                            </label>
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
                                        <div class="col-span-1">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Qtd</label>
                                            <input type="number" :step="getQuantityStep(it.unit)" :min="getQuantityMin(it.unit)" inputmode="decimal" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" x-model="it.quantity" :name="`items[${i}][quantity]`" @input="validateQty(i)">
                                        </div>
                                        <div class="col-span-1">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">UN</label>
                                            <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" x-model="it.unit" :name="`items[${i}][unit]`" readonly>
                                        </div>
                                        <div class="col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">V.Unit</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 text-sm">R$</span>
                                                </div>
                                                <input type="text" class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-right font-medium" :value="Number(it.unit_price || 0).toFixed(2)" readonly>
                                                <input type="hidden" x-model="it.unit_price" :name="`items[${i}][unit_price]`">
                                            </div>
                                        </div>
                                        <div class="col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Desc.</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 text-sm">R$</span>
                                                </div>
                                                <input type="number" step="0.01" min="0" class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-right" x-model="it.discount_value" :name="`items[${i}][discount_value]`">
                                            </div>
                                        </div>
                                        <div class="col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">V.Total</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 text-sm">R$</span>
                                                </div>
                                                <input type="text" class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg bg-gray-50 font-semibold text-right" :value="(Number(it.quantity || 0) * Number(it.unit_price || 0) - Number(it.discount_value || 0)).toFixed(2)
" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end mt-3">
                                        <button type="button" @click="remove(i)" class="inline-flex items-center px-3 py-1 text-sm text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Remover
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <!-- Resumo dos Valores -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mt-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Subtotal -->
        <div class="text-center">
            <div class="text-sm text-gray-600 mb-1">Subtotal</div>
            <div class="text-xl font-semibold text-gray-800">
                R$ <span x-text="subtotal().toFixed(2)"></span>
            </div>
        </div>

        <!-- Desconto dos Itens -->
        <div class="text-center">
            <div class="text-sm text-gray-600 mb-1">Desc. Itens</div>
            <div class="text-xl font-semibold text-red-600">
                - R$ <span x-text="discountItems().toFixed(2)"></span>
            </div>
        </div>

        <!-- Desconto Total (global + itens) -->
        <div class="text-center">
            <div class="text-sm text-gray-600 mb-1">Desc. Total</div>
            <div class="text-xl font-semibold text-red-600">
                - R$ 
                <span x-text="(Number(discountItems()) + Number(discountTotal || 0)).toFixed(2)"></span>
            </div>
        </div>

        <!-- Total Líquido -->
        <div class="text-center">
            <div class="text-sm text-gray-600 mb-1">Total Líquido</div>
            <div class="text-2xl font-bold text-green-600">
                R$ <span x-text="netTotal().toFixed(2)"></span>
            </div>
        </div>
    </div>

    <!-- Campo para desconto global -->
    <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="flex items-center justify-center">
            <div class="w-full max-w-xs">
                <label class="block text-xs font-medium text-gray-600 mb-2 text-center">
                    Desconto Global
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 text-sm">R$</span>
                    </div>
                    <input 
                        type="number" 
                        step="0.01" 
                        min="0" 
                        name="discount_total"
                        x-model="discountTotal"
                        class="w-full pl-8 pr-3 py-2 text-sm border border-gray-300 rounded-lg 
                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-right" 
                        placeholder="0,00">
                </div>
            </div>
        </div>
    </div>
</div>


                    <!-- Botões de Ação -->
                    <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                        <a href="{{ route('orders.index') }}" class="px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            Cancelar
                        </a>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg text-sm font-medium hover:from-green-600 hover:to-green-700 transition-all transform hover:scale-105 shadow-lg">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Salvar Pedido
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toast para mensagens de sucesso e erro
    const flashSuccess = @json(session('success'));
    const flashError = @json(session('error'));
    const validationErrors = @json($errors->all());
    
    if (flashSuccess) {
        showToast(flashSuccess, 'success');
    }
    if (flashError) {
        showToast(flashError, 'error');
    }
    if (validationErrors && validationErrors.length > 0) {
        validationErrors.forEach(error => {
            showToast(error, 'error');
        });
    }
    
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 translate-x-full`;
        
        if (type === 'success') {
            toast.className += ' bg-green-500 text-white';
            toast.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    ${message}
                </div>
            `;
        } else {
            toast.className += ' bg-red-500 text-white';
            toast.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    ${message}
                </div>
            `;
        }
        
        document.body.appendChild(toast);
        
        // Animar entrada
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);
        
        // Remover após 5 segundos
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 5000);
    }
});
</script>

<script>
    function orderForm(){ return {} }
    function orderItems(){
        return {
            items: [{ product_id:'', search:'', quantity:1, unit:'', unit_price:'', discount_value:0, type:'product', balance:0, name:'' }],
            discountTotal: 0,
            suggestionsByIndex: {},
            add(){ this.items.push({ product_id:'', search:'', quantity:1, unit:'', unit_price:'', discount_value:0, type:'product', balance:0, name:'' }); },
            remove(i){ this.items.splice(i,1); this.suggestionsByIndex = {}; },
            clearSuggestions(i){ this.suggestionsByIndex[i] = []; },
            getSuggestions(i){ return this.suggestionsByIndex[i] || []; },
            async search(i){
                const term = this.items[i].search;
                if (!term || term.length < 2) { this.suggestionsByIndex[i] = []; return; }
                const url = `{{ route('products.search') }}?term=${encodeURIComponent(term)}`;
                try { const res = await fetch(url, { headers: {'Accept':'application/json'} }); this.suggestionsByIndex[i] = await res.json(); }
                catch(e){ this.suggestionsByIndex[i] = []; }
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
            subtotal(){ return this.items.reduce((s,it)=> s + (Number(it.quantity||0)*Number(it.unit_price||0)), 0); },
            discountItems(){ return this.items.reduce((s,it)=> s + Number(it.discount_value||0), 0); },
            netTotal(){ return Math.max(0, this.subtotal() - this.discountItems() - Number(this.discountTotal||0)); }
        }
    }
</script>


