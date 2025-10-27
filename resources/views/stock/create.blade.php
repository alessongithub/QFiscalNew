<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Novo Movimento de Estoque
            </h2>
            <a href="{{ route('stock.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        Informações do Movimento
                    </h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('stock.store') }}" class="space-y-6">
                        @csrf
                        
                        <!-- Produto -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Produto</label>
                            <div class="relative">
                                <input type="hidden" name="product_id" id="product_id" required>
                                <input type="text" id="product_search" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Digite o nome do produto" autocomplete="off">
                                <div id="product_dropdown" class="absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-auto hidden mt-1"></div>
                            </div>
                            <div id="product_hint" class="text-xs text-gray-500 mt-2"></div>
                        </div>

                        <!-- Tipo, Quantidade e Preço -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Movimento</label>
                                <select name="type" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                                    <option value="entry">📥 Entrada</option>
                                    <option value="exit">📤 Saída</option>
                                    <option value="adjustment">⚖️ Ajuste</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade</label>
                                <input type="number" step="0.001" name="quantity" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Preço Unitário (R$)</label>
                                <input type="number" step="0.01" name="unit_price" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="0,00" />
                            </div>
                        </div>

                        <!-- Documento e Observação -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Documento (opcional)</label>
                                <input type="text" name="document" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Ex.: NF 123, Pedido 456" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observação</label>
                                <input type="text" name="note" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Observações adicionais" />
                            </div>
                        </div>

                        <!-- Botões -->
                        <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                            <a href="{{ route('stock.index') }}" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-150 ease-in-out">
                                Cancelar
                            </a>
                            <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Salvar Movimento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
(function(){
function initStockAutocomplete(){
    try { console.log('[stock/create] autocomplete script loaded'); } catch(e) {}
    const input = document.getElementById('product_search');
    const hidden = document.getElementById('product_id');
    const hint = document.getElementById('product_hint');
    const dropdown = document.getElementById('product_dropdown');
    let debounceTimer = null;
    let items = [];
    let selectedIndex = -1;
    let loading = false;

    function formatLabel(p){
        const unit = p.unit ? ` ${p.unit}` : '';
        const price = (typeof p.price === 'number') ? ` — R$ ${p.price.toFixed(2).replace('.',',')}` : '';
        return `${p.name}${unit}${price}`;
    }

    function highlight(text, term){
        if (!term) return text;
        const esc = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const re = new RegExp(esc, 'ig');
        return text.replace(re, m => `<span class="bg-yellow-100">${m}</span>`);
    }

    async function searchProducts(term){
        const url = `/api/products/search?term=${encodeURIComponent(term)}`;
        const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
        if (!res.ok) return [];
        return await res.json();
    }

    function renderDropdown(term){
        dropdown.innerHTML = '';
        if (loading) {
            const div = document.createElement('div');
            div.className = 'px-3 py-2 text-sm text-gray-500';
            div.textContent = 'Buscando...';
            dropdown.appendChild(div);
            dropdown.classList.remove('hidden');
            return;
        }
        if (!items || items.length === 0) {
            const div = document.createElement('div');
            div.className = 'px-3 py-2 text-sm text-gray-500';
            div.textContent = 'Nenhum produto encontrado';
            dropdown.appendChild(div);
            dropdown.classList.remove('hidden');
            return;
        }
        items.forEach((p, idx) => {
            const div = document.createElement('div');
            div.className = `px-3 py-2 cursor-pointer text-sm ${idx===selectedIndex ? 'bg-gray-100' : 'bg-white hover:bg-gray-50'}`;
            div.dataset.id = String(p.id);
            div.innerHTML = highlight(formatLabel(p), term);
            div.addEventListener('mouseenter', () => { selectedIndex = idx; renderDropdown(term); });
            div.addEventListener('mousedown', (e) => { e.preventDefault(); selectIndex(idx); });
            dropdown.appendChild(div);
        });
        dropdown.classList.remove('hidden');
    }

    function selectIndex(idx){
        if (idx < 0 || idx >= items.length) return;
        const p = items[idx];
        hidden.value = String(p.id);
        input.value = p.name;
        hint.textContent = 'Selecionado: ' + formatLabel(p);
        hint.classList.remove('text-red-600');
        hint.classList.add('text-gray-500');
        dropdown.classList.add('hidden');
    }

    function moveSelection(delta){
        if (!items || items.length === 0) return;
        selectedIndex += delta;
        if (selectedIndex < 0) selectedIndex = items.length - 1;
        if (selectedIndex >= items.length) selectedIndex = 0;
        renderDropdown(input.value.trim());
        const child = dropdown.children[selectedIndex];
        if (child && typeof child.scrollIntoView === 'function') child.scrollIntoView({ block: 'nearest' });
    }

    input.addEventListener('input', function(){
        const term = this.value.trim();
        hidden.value = '';
        selectedIndex = -1;
        try { console.log('[stock/create] input:', term); } catch(e) {}
        if (debounceTimer) clearTimeout(debounceTimer);
        if (term.length < 2) {
            items = [];
            dropdown.innerHTML = '';
            dropdown.classList.add('hidden');
            hint.textContent = 'Digite pelo menos 2 caracteres';
            hint.classList.remove('text-red-600');
            hint.classList.add('text-gray-500');
            return;
        }
        debounceTimer = setTimeout(async () => {
            try {
                loading = true;
                hint.textContent = 'Buscando...';
                renderDropdown(term);
                items = await searchProducts(term);
                loading = false;
                renderDropdown(term);
                hint.textContent = items && items.length ? `${items.length} resultado(s)` : 'Nenhum produto encontrado';
            } catch(_){
                loading = false;
                items = [];
                dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-red-600">Erro ao buscar produtos</div>';
                dropdown.classList.remove('hidden');
                hint.textContent = 'Erro ao buscar';
            }
        }, 200);
    });

    input.addEventListener('keydown', function(e){
        if (dropdown.classList.contains('hidden')) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); moveSelection(1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); moveSelection(-1); }
        else if (e.key === 'Enter') { e.preventDefault(); selectIndex(selectedIndex === -1 ? 0 : selectedIndex); }
        else if (e.key === 'Escape') { dropdown.classList.add('hidden'); }
    });

    input.addEventListener('blur', function(){ setTimeout(() => dropdown.classList.add('hidden'), 120); });
    input.addEventListener('focus', function(){ if (items.length) dropdown.classList.remove('hidden'); });

    const form = input.closest('form');
    if (form) {
        form.addEventListener('submit', function(e){
            if (!hidden.value) {
                e.preventDefault();
                hint.textContent = 'Selecione um produto na lista';
                hint.classList.add('text-red-600');
                input.focus();
            }
        });
    }
}
if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', initStockAutocomplete); } else { initStockAutocomplete(); }
})();
</script>

