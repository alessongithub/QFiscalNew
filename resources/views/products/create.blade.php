<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2h-3.5a2 2 0 01-1.6-.8L11.1 2.8A2 2 0 009.5 2H6a2 2 0 00-2 2v9m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0H4"/>
            </svg>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo Produto</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Ficha: Identificação -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b flex items-center space-x-2">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6m2 8a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v8a2 2 0 002 2h10z"/></svg>
                    <h3 class="font-semibold text-gray-800">Identificação</h3>
                </div>
                <div class="p-6">
                    <form id="productForm" method="POST" action="{{ route('products.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        @csrf
                        <div class="md:col-span-2">
                            <label class="text-sm text-gray-700 mb-1 block">Nome</label>
                            <div class="flex items-center border rounded px-3">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6"/></svg>
                                <input type="text" name="name" class="p-2 w-full focus:outline-none" placeholder="Ex.: Camiseta Polo" required />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">Categoria</label>
                            <div class="flex items-center border rounded px-3">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                <select name="category_id" class="p-2 w-full focus:outline-none" required>
                                    <option value="">Selecione</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->parent_id ? '— ' : '' }}{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">SKU</label>
                            <div class="flex items-center border rounded px-3">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
                                <input type="text" name="sku" class="p-2 w-full focus:outline-none" placeholder="Código interno" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">Fornecedor</label>
                            <div class="flex items-center border rounded px-3">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 01-8 0 4 4 0 118 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <select name="supplier_id" class="p-2 w-full focus:outline-none">
                                    <option value="">Selecione</option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">GTIN / Código de Barras</label>
                            <div class="flex items-center border rounded px-3">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h2M7 7h2m2 0h2m2 0h2m2 0h2M3 17h2m2 0h2m2 0h2m2 0h2m2 0h2"/></svg>
                                <input id="ean" type="text" name="ean" class="p-2 w-full focus:outline-none" placeholder="Sem GTIN" />
                            </div>
                            <div class="flex items-center mt-2 space-x-2">
                                <input id="no_gtin" type="checkbox" class="rounded" />
                                <label for="no_gtin" class="text-sm text-gray-700">Sem GTIN</label>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">GTIN válido: 8, 12, 13 ou 14 dígitos. Se não possuir, marque "Sem GTIN".</div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">Unidade</label>
                            <div class="flex items-center border rounded px-3">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                <input list="unidades" type="text" name="unit" class="p-2 w-full focus:outline-none" placeholder="Ex.: UN, KG" required />
                                <datalist id="unidades">
                                    <option value="UN">Unidade</option>
                                    <option value="KG">Quilograma</option>
                                    <option value="G">Grama</option>
                                    <option value="L">Litro</option>
                                    <option value="ML">Mililitro</option>
                                    <option value="M">Metro</option>
                                    <option value="M²">Metro Quadrado</option>
                                    <option value="M³">Metro Cúbico</option>
                                    <option value="CX">Caixa</option>
                                    <option value="PC">Peça</option>
                                    <option value="DZ">Dúzia</option>
                                    <option value="PAR">Par</option>
                                    <option value="HR">Hora</option>
                                    <option value="DIA">Dia</option>
                                </datalist>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">Preço</label>
                            <div class="flex items-center border rounded px-3">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/></svg>
                                <input type="number" step="0.01" name="price" class="p-2 w-full focus:outline-none" placeholder="0,00" required />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">Tipo</label>
                            <div class="flex items-center border rounded px-3">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2h-3.5a2 2 0 01-1.6-.8"/></svg>
                                <select name="type" class="p-2 w-full focus:outline-none">
                                    <option value="product">Produto</option>
                                    <option value="service">Serviço</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" name="active" value="1" checked class="rounded" />
                            <span class="text-sm text-gray-700">Ativo</span>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Ficha: Dados Fiscais -->
            <div id="fiscal-fields" class="bg-white shadow rounded-lg overflow-hidden" style="display: none;">
                <div class="px-6 py-4 border-b flex items-center space-x-2">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6M4 7h16M4 17h16"/></svg>
                    <h3 class="font-semibold text-gray-800">Dados Fiscais</h3>
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">Apenas para produtos</span>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="md:col-span-3 flex items-center justify-between mb-2">

                            <div class="flex items-center space-x-2">
                                <input id="taxRateFilter" type="text" class="border rounded px-2 py-1 text-sm" placeholder="Filtrar (#id, nome, NCM, CFOP)">
                                <select id="taxRateSelect" class="border rounded px-8 py-1 text-sm">
                                    <option value="">Aplicar configuração tributária…</option>
                                    @foreach(\App\Models\TaxRate::where('tenant_id', auth()->user()->tenant_id)->where('ativo',1)->orderByDesc('id')->limit(50)->get() as $tr)
                                        <option value="{{ $tr->id }}">{{ $tr->name ? ("#{$tr->id} — " . $tr->name . ($tr->ncm ? " (NCM {$tr->ncm})" : ($tr->codigo_servico ? " (Serviço {$tr->codigo_servico})" : ''))) : ("#{$tr->id} — " . strtoupper($tr->tipo_nota) . ' ' . ($tr->ncm ?: $tr->codigo_servico ?: '') . ' ' . ($tr->cfop ?: '')) }}</option>
                                    @endforeach
                                </select>
                                <button type="button" id="applyTaxRateBtn" class="text-xs bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded">Aplicar</button>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">NCM <span class="text-red-500">*</span> <span class="text-xs text-gray-500">(Obrigatório para produtos)</span></label>
                            <div class="flex items-center border rounded px-3">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
                                <input form="productForm" type="text" name="ncm" class="p-2 w-full focus:outline-none" placeholder="8 dígitos" />
                                <div class="ncm-feedback text-xs text-gray-500 ml-2"></div>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">CEST</label>
                            <div class="flex items-center border rounded px-3">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
                                <input form="productForm" type="text" name="cest" class="p-2 w-full focus:outline-none" placeholder="7 dígitos" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">CFOP</label>
                            <div class="flex items-center border rounded px-3">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
                                <input form="productForm" type="text" name="cfop" class="p-2 w-full focus:outline-none" placeholder="Ex.: 5102" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">Origem da Mercadoria</label>
                            <div class="flex items-center border rounded px-3">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2"/></svg>
                                <select form="productForm" name="origin" class="p-2 w-full focus:outline-none">
                                    <option value="">Selecione a origem</option>
                                    <option value="0" @selected(old('origin')==='0')>0 - Nacional</option>
                                    <option value="1" @selected(old('origin')==='1')>1 - Estrangeira - Importação direta</option>
                                    <option value="2" @selected(old('origin')==='2')>2 - Estrangeira - Adquirida no mercado interno</option>
                                    <option value="3" @selected(old('origin')==='3')>3 - Nacional - Mercadoria com >40% importação</option>
                                    <option value="4" @selected(old('origin')==='4')>4 - Nacional - Produção conforme processo produtivo</option>
                                    <option value="5" @selected(old('origin')==='5')>5 - Nacional - Mercadoria com <40% importação</option>
                                    <option value="6" @selected(old('origin')==='6')>6 - Estrangeira - Importação direta sem similar nacional</option>
                                    <option value="7" @selected(old('origin')==='7')>7 - Estrangeira - Mercado interno sem similar nacional</option>
                                    <option value="8" @selected(old('origin')==='8')>8 - Nacional - Mercadoria com >70% importação</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ficha: Tributação -->
            <div id="tributacao-fields" class="bg-white shadow rounded-lg overflow-hidden" style="display: none;">
                <div class="px-6 py-4 border-b flex items-center space-x-2">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2M12 8V7m0 1v8"/></svg>
                    <h3 class="font-semibold text-gray-800">Tributação</h3>
                    <div class="ml-auto flex space-x-2">
                        <button type="button" onclick="applyTaxTemplate('simples')" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200">📋 Simples Nacional</button>
                        <button type="button" onclick="applyTaxTemplate('lucro')" class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded hover:bg-green-200">📋 Lucro Presumido</button>
                        <button type="button" onclick="applyTaxTemplate('real')" class="text-xs bg-purple-100 text-purple-700 px-3 py-1 rounded hover:bg-purple-200">📋 Lucro Real</button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">CSOSN</label>
                            <div class="flex items-center border rounded px-3">
                                <input form="productForm" type="text" name="csosn" class="p-2 w-full focus:outline-none" placeholder="Ex.: 102, 201" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">CST ICMS</label>
                            <div class="flex items-center border rounded px-3">
                                <input form="productForm" type="text" name="cst_icms" class="p-2 w-full focus:outline-none" placeholder="Ex.: 00, 20, 40" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">CST PIS</label>
                            <div class="flex items-center border rounded px-3">
                                <input form="productForm" type="text" name="cst_pis" class="p-2 w-full focus:outline-none" placeholder="Ex.: 01, 06, 49" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">CST COFINS</label>
                            <div class="flex items-center border rounded px-3">
                                <input form="productForm" type="text" name="cst_cofins" class="p-2 w-full focus:outline-none" placeholder="Ex.: 01, 06, 49" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">Alíquota ICMS (%)</label>
                            <div class="flex items-center border rounded px-3">
                                <input form="productForm" type="number" step="0.01" name="aliquota_icms" class="p-2 w-full focus:outline-none" placeholder="0,00" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">Alíquota PIS (%)</label>
                            <div class="flex items-center border rounded px-3">
                                <input form="productForm" type="number" step="0.01" name="aliquota_pis" class="p-2 w-full focus:outline-none" placeholder="0,00" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-700 mb-1 block">Alíquota COFINS (%)</label>
                            <div class="flex items-center border rounded px-3">
                                <input form="productForm" type="number" step="0.01" name="aliquota_cofins" class="p-2 w-full focus:outline-none" placeholder="0,00" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações -->
            <div class="flex justify-end">
                <a href="{{ route('products.index') }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded mr-2">Cancelar</a>
                <button form="productForm" type="submit" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Salvar Produto
                </button>
            </div>
        </div>
    </div>
</x-app-layout>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('select[name="type"]');
    const fiscalFields = document.getElementById('fiscal-fields');
    const tributacaoFields = document.getElementById('tributacao-fields');
    const ncmInput = document.querySelector('input[name="ncm"]');
    const cfopInput = document.querySelector('input[name="cfop"]');
    const cestInput = document.querySelector('input[name="cest"]');
    const categorySelect = document.querySelector('select[name="category_id"]');
    const eanInput = document.getElementById('ean');
    const noGtinCheckbox = document.getElementById('no_gtin');
    const ncmAlertId = 'ncm-gtin-alert';
    const taxRateSelect = document.getElementById('taxRateSelect');
    const applyTaxRateBtn = document.getElementById('applyTaxRateBtn');
    const taxRateFilter = document.getElementById('taxRateFilter');

    function toggleFiscal(required) {
        if (required) {
            fiscalFields.style.display = 'block';
            tributacaoFields.style.display = 'block';
            document.querySelectorAll('#fiscal-fields input').forEach(i => i.required = true);
        } else {
            fiscalFields.style.display = 'none';
            tributacaoFields.style.display = 'none';
            document.querySelectorAll('#fiscal-fields input').forEach(i => i.required = false);
        }
    }
    toggleFiscal(typeSelect.value === 'product');
    typeSelect.addEventListener('change', function(){
        toggleFiscal(this.value === 'product');
    });

    // Prefill taxation based on tenant regime if fields are blank
    const regime = @json(($taxConfig->regime_tributario ?? null));
    if (typeSelect.value === 'product' && regime) {
        const fields = ['csosn','cst_icms','cst_pis','cst_cofins','aliquota_icms','aliquota_pis','aliquota_cofins'];
        const allBlank = fields.every(n => {
            const el = document.querySelector(`input[name="${n}"]`);
            return !el || el.value === '';
        });
        if (allBlank) {
            if (regime === 'simples_nacional') { applyTaxTemplate('simples'); }
            else if (regime === 'lucro_presumido') { applyTaxTemplate('lucro'); }
            else if (regime === 'lucro_real') { applyTaxTemplate('real'); }
        }
    }

    // Validações com formatação automática
    if (ncmInput) {
        ncmInput.addEventListener('input', function(){
            // Permitir entrada com pontos, mas processar apenas números
            let value = this.value.replace(/[^0-9.]/g, '');
            
            // Se tem pontos, formatar visualmente mas manter apenas números internamente
            if (value.includes('.')) {
                const numbersOnly = value.replace(/\./g, '');
                if (numbersOnly.length > 8) {
                    value = numbersOnly.substring(0, 8);
                }
                // Formatar visualmente: 1234.56.78
                if (numbersOnly.length >= 4) {
                    const formatted = numbersOnly.substring(0, 4) + 
                        (numbersOnly.length > 4 ? '.' + numbersOnly.substring(4, 6) : '') +
                        (numbersOnly.length > 6 ? '.' + numbersOnly.substring(6, 8) : '');
                    this.value = formatted;
                } else {
                    this.value = numbersOnly;
                }
            } else {
                // Sem pontos, apenas números
                if (value.length > 8) { 
                    value = value.substring(0, 8); 
                }
                this.value = value;
            }
            
            const numbersOnly = this.value.replace(/[^0-9]/g, '');
            const feedback = this.parentNode.querySelector('.ncm-feedback');
            if (feedback) {
                // Só mostrar feedback se não estiver vazio
                if (numbersOnly.length === 0) {
                    feedback.textContent = '';
                    this.classList.remove('border-red-500');
                } else {
                    feedback.textContent = numbersOnly.length === 8 ? '✅ NCM válido' : `⚠️ ${numbersOnly.length}/8`;
                    this.classList.toggle('border-red-500', numbersOnly.length !== 8);
                }
            }
            
            // Checar se NCM exige GTIN e alertar (apenas se não estiver vazio)
            if (numbersOnly.length === 8) {
                fetch(`/api/ncm/${numbersOnly}/requires-gtin`, { headers: { 'Accept': 'application/json' }})
                    .then(r => r.ok ? r.json() : null)
                    .then(data => {
                        let alert = document.getElementById(ncmAlertId);
                        if (data && data.requires_gtin) {
                            if (!alert) {
                                alert = document.createElement('div');
                                alert.id = ncmAlertId;
                                alert.className = 'mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1';
                                this.parentNode.parentNode.appendChild(alert);
                            }
                            alert.textContent = `Atenção: o NCM ${numbersOnly} requer GTIN para emissão. ${data.note ? '('+data.note+')' : ''}`;
                        } else if (alert) {
                            alert.remove();
                        }
                    })
                    .catch(() => {});
            } else if (numbersOnly.length === 0) {
                // Remover alerta quando campo estiver vazio
                let alert = document.getElementById(ncmAlertId);
                if (alert) {
                    alert.remove();
                }
            }
        });
    }
    if (cfopInput) {
        cfopInput.addEventListener('input', function(){
            const value = this.value.replace(/\D/g, '');
            if (value.length > 4) { this.value = value.substring(0,4); } else { this.value = value; }
        });
    }
    if (cestInput) {
        cestInput.addEventListener('input', function(){
            // Permitir entrada com pontos, mas processar apenas números
            let value = this.value.replace(/[^0-9.]/g, '');
            
            if (value.includes('.')) {
                const numbersOnly = value.replace(/\./g, '');
                if (numbersOnly.length > 7) {
                    value = numbersOnly.substring(0, 7);
                }
                // Formatar visualmente: 1234.567
                if (numbersOnly.length >= 4) {
                    const formatted = numbersOnly.substring(0, 4) + 
                        (numbersOnly.length > 4 ? '.' + numbersOnly.substring(4, 7) : '');
                    this.value = formatted;
                } else {
                    this.value = numbersOnly;
                }
            } else {
                if (value.length > 7) { 
                    value = value.substring(0, 7); 
                }
                this.value = value;
            }
        });
    }

    // Toggle Sem GTIN
    if (noGtinCheckbox && eanInput) {
        const syncNoGtin = () => {
            if (noGtinCheckbox.checked) {
                eanInput.value = 'Sem GTIN';
                eanInput.readOnly = true;
                eanInput.classList.add('bg-gray-50');
            } else {
                if (eanInput.value === 'Sem GTIN') { eanInput.value = ''; }
                eanInput.readOnly = false;
                eanInput.classList.remove('bg-gray-50');
            }
        };
        noGtinCheckbox.addEventListener('change', syncNoGtin);
        // Inicial
        syncNoGtin();
    }

    // Validação GTIN client-side (8/12/13/14 dígitos ou 'Sem GTIN')
    if (eanInput) {
        eanInput.addEventListener('input', function(){
            if (this.value === 'Sem GTIN') return;
            const digits = this.value.replace(/\D/g,'');
            this.value = digits;
            const ok = digits.length === 0 || [8,12,13,14].includes(digits.length);
            this.classList.toggle('border-red-500', !ok);
        });
    }

    // Validação de alíquotas (máximo 999.99%)
    function validateAliquota(input) {
        const value = parseFloat(input.value);
        // Só validar se não estiver vazio
        if (input.value.trim() === '') {
            return true; // Campo vazio é válido
        }
        if (!isNaN(value) && value > 999.99) {
            input.value = '999.99';
            showFieldError(input.name, 'Alíquota muito alta. Máximo permitido: 999.99%');
            return false;
        }
        return true;
    }

    document.querySelectorAll('input[name^="aliquota_"]').forEach(input => {
        // Validar ao carregar a página
        validateAliquota(input);
        
        // Validar ao digitar
        input.addEventListener('input', function(){
            validateAliquota(this);
        });
    });

    // Validação de preço (máximo 99.999.999,99)
    function validatePrice(input) {
        const value = parseFloat(input.value);
        // Só validar se não estiver vazio
        if (input.value.trim() === '') {
            return true; // Campo vazio é válido
        }
        if (!isNaN(value) && value > 99999999.99) {
            input.value = '99999999.99';
            showFieldError(input.name, 'Preço muito alto. Máximo permitido: R$ 99.999.999,99');
            return false;
        }
        return true;
    }

    const priceInput = document.querySelector('input[name="price"]');
    if (priceInput) {
        // Validar ao carregar a página
        validatePrice(priceInput);
        
        // Validar ao digitar
        priceInput.addEventListener('input', function(){
            validatePrice(this);
        });
    }

    // Auto-preencher CFOP pela categoria (endpoint minimalista)
    if (categorySelect) {
        categorySelect.addEventListener('change', function(){
            const id = this.value;
            if (!id) return;
            fetch(`/api/categories/${id}/default-cfop`, { headers: { 'Accept': 'application/json' }})
                .then(r => r.ok ? r.json() : null)
                .then(data => { if (data && data.cfop && cfopInput) { cfopInput.value = data.cfop; showNotification(`CFOP ${data.cfop} aplicado automaticamente! ✅`, 'info'); }})
                .catch(() => {});
        });
    }

    // Aplicar configuração tributária a partir de tax_rates
    if (applyTaxRateBtn && taxRateSelect) {
        applyTaxRateBtn.addEventListener('click', function(){
            const id = taxRateSelect.value;
            if (!id) return;
            fetch(`/tax_rates/${id}/json`, { headers: { 'Accept': 'application/json' }})
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (!data) return;
                    if (data.tipo_nota === 'produto') {
                        if (data.ncm) setField('ncm', data.ncm);
                        if (data.cfop) setField('cfop', data.cfop);
                        if (data.icms_aliquota != null) setField('aliquota_icms', percentFromDecimal(data.icms_aliquota));
                        if (data.pis_aliquota != null) setField('aliquota_pis', percentFromDecimal(data.pis_aliquota));
                        if (data.cofins_aliquota != null) setField('aliquota_cofins', percentFromDecimal(data.cofins_aliquota));
                        showNotification('Tributação de produto aplicada a partir de tax_rates.','success');
                    } else if (data.tipo_nota === 'servico') {
                        // Para serviços, só aplica PIS/COFINS e ISS; mantém CFOP/NCM intactos
                        if (data.pis_aliquota != null) setField('aliquota_pis', percentFromDecimal(data.pis_aliquota));
                        if (data.cofins_aliquota != null) setField('aliquota_cofins', percentFromDecimal(data.cofins_aliquota));
                        const issField = document.querySelector('input[name="iss_aliquota"]');
                        if (issField && data.iss_aliquota != null) { issField.value = percentFromDecimal(data.iss_aliquota); }
                        showNotification('Tributação de serviço aplicada a partir de tax_rates.','success');
                    }
                })
                .catch(() => {});
        });
    }

    // Filtro + ordenação por correspondência no select de tax_rates
    if (taxRateFilter && taxRateSelect) {
        const placeholder = taxRateSelect.options[0];
        taxRateFilter.addEventListener('input', function(){
            const termRaw = this.value || '';
            const term = termRaw.toLowerCase().trim();
            const opts = Array.from(taxRateSelect.options).slice(1); // ignora placeholder

            function scoreOption(opt) {
                if (!term) return 0;
                const text = (opt.textContent || '').toLowerCase();
                // Extrair possíveis campos
                const nameMatch = text.match(/—\s+([^()#]+?)(\(|$)/); // parte entre — e (algo)
                const name = nameMatch ? nameMatch[1].trim() : '';
                let score = 0;
                if (text.includes(term)) score += 1;
                if (text.startsWith(term)) score += 2;
                const idMatch = (opt.textContent || '').match(/^#(\d+)/);
                if (idMatch && ('#' + idMatch[1]).toLowerCase() === term) score += 6;
                const digits = term.replace(/\D/g,'');
                if (digits.length === 8 && text.includes(digits)) score += 4; // NCM
                if (digits.length === 4 && text.includes(digits)) score += 3; // CFOP
                if (name && name.includes(term)) score += 5; // prioriza nome
                return score;
            }

            // Ordena por score desc e reanexa. Oculta opções com score 0 quando há termo.
            const scored = opts.map(o => ({ opt: o, s: scoreOption(o) }));
            scored.sort((a,b) => b.s - a.s);

            const frag = document.createDocumentFragment();
            frag.appendChild(placeholder); // mantém no topo
            for (const {opt, s} of scored) {
                opt.hidden = !!term && s === 0;
                frag.appendChild(opt);
            }
            taxRateSelect.innerHTML = '';
            taxRateSelect.appendChild(frag);
        });
    }
});

function applyTaxTemplate(type) {
    if (type === 'simples') {
        setField('csosn','102');
        setField('cst_icms','');
        setField('cst_pis','01');
        setField('cst_cofins','01');
        setField('aliquota_icms','0');
        setField('aliquota_pis','0');
        setField('aliquota_cofins','0');
        showNotification('Template Simples Nacional aplicado! ✅','success');
    } else if (type === 'lucro') {
        setField('csosn','');
        setField('cst_icms','00');
        setField('cst_pis','01');
        setField('cst_cofins','01');
        setField('aliquota_icms','18');
        setField('aliquota_pis','1.65');
        setField('aliquota_cofins','7.6');
        showNotification('Template Lucro Presumido aplicado! ✅','success');
    } else if (type === 'real') {
        setField('csosn','');
        setField('cst_icms','00');
        setField('cst_pis','01');
        setField('cst_cofins','01');
        setField('aliquota_icms','18');
        setField('aliquota_pis','1.65');
        setField('aliquota_cofins','7.6');
        showNotification('Template Lucro Real aplicado! ✅','success');
    }
}
function setField(name, value){ const el = document.querySelector(`input[name="${name}"]`); if (el) el.value = value; }
function percentFromDecimal(dec){ if (dec == null) return ''; const p = Math.round(dec * 10000) / 100; return p.toFixed(2); }
function showNotification(message, type){
    const n = document.createElement('div');
    n.className = `fixed top-4 right-4 px-4 py-2 rounded shadow-lg z-50 ${type==='success'?'bg-green-600 text-white': (type==='info'?'bg-blue-600 text-white':'bg-red-600 text-white')}`;
    n.textContent = message;
    document.body.appendChild(n);
    setTimeout(()=> n.remove(), 2500);
}

// Sistema de Toast para erros de validação
function showValidationErrors(errors) {
    errors.forEach(error => {
        showNotification(error, 'error');
    });
}

// Mostrar erro específico de campo
function showFieldError(fieldName, message) {
    const field = document.querySelector(`[name="${fieldName}"]`);
    if (field) {
        field.classList.add('border-red-500');
        // Adicionar tooltip ou mensagem próxima ao campo
        let errorDiv = field.parentNode.querySelector('.field-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'field-error text-xs text-red-600 mt-1';
            field.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }
    showNotification(message, 'error');
}

// Interceptar submissão do formulário para mostrar erros
document.getElementById('productForm').addEventListener('submit', function(e) {
    // Limpar toasts anteriores
    document.querySelectorAll('.fixed.top-4.right-4').forEach(toast => toast.remove());
    // Limpar erros de campo
    document.querySelectorAll('.field-error').forEach(error => error.remove());
    document.querySelectorAll('.border-red-500').forEach(field => field.classList.remove('border-red-500'));
    
    // Verificar campos fiscais em branco e avisar
    const fiscalFields = ['ncm', 'cest', 'cfop', 'csosn', 'cst_icms', 'cst_pis', 'cst_cofins', 'aliquota_icms', 'aliquota_pis', 'aliquota_cofins'];
    const emptyFields = [];
    
    fiscalFields.forEach(fieldName => {
        const field = document.querySelector(`input[name="${fieldName}"]`);
        if (field && field.value.trim() === '') {
            emptyFields.push(fieldName);
        }
    });
    
    if (emptyFields.length > 0) {
        showNotification('⚠️ Alguns campos fiscais estão em branco. Produto será salvo mesmo assim.', 'info');
    }
});

// Verificar se há erros de validação na página (do Laravel)
@if($errors->any())
    document.addEventListener('DOMContentLoaded', function() {
        const errors = @json($errors->all());
        const errorKeys = @json($errors->keys());
        const errorMessages = @json($errors->messages());
        
        // Mostrar erros específicos de campos
        errorKeys.forEach(fieldKey => {
            if (errorMessages[fieldKey] && errorMessages[fieldKey].length > 0) {
                showFieldError(fieldKey, errorMessages[fieldKey][0]);
            }
        });
        
        // Mostrar erros gerais
        const generalErrors = errors.filter(error => !errorKeys.some(fieldKey => error.includes(fieldKey)));
        if (generalErrors.length > 0) {
            showValidationErrors(generalErrors);
        }
    });
    // Validação geral ao carregar a página
    function validateAllFields() {
        let hasErrors = false;
        
        // Validar alíquotas
        document.querySelectorAll('input[name^="aliquota_"]').forEach(input => {
            if (!validateAliquota(input)) {
                hasErrors = true;
            }
        });
        
        // Validar preço
        const priceInput = document.querySelector('input[name="price"]');
        if (priceInput && !validatePrice(priceInput)) {
            hasErrors = true;
        }
        
        return hasErrors;
    }

    // Executar validação ao carregar a página
    setTimeout(() => {
        validateAllFields();
        
        // Remover alerta NCM se campo estiver vazio
        if (ncmInput && ncmInput.value.trim() === '') {
            let alert = document.getElementById(ncmAlertId);
            if (alert) {
                alert.remove();
            }
        }
        
        console.log('Página carregada - validação NCM/CEST atualizada:', new Date().toISOString());
    }, 500); // Delay para garantir que todos os campos estejam carregados
@endif
</script>
