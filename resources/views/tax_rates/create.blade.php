<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nova Configuração Tributária
            </h2>
            <a href="{{ route('tax_rates.index') }}" class="text-gray-600 hover:text-gray-800 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                <h3 class="text-white text-lg font-semibold">Configurar Alíquotas Tributárias</h3>
                <p class="text-green-100 text-sm">Configure as alíquotas de impostos para produtos ou serviços</p>
            </div>
            
            @if($errors->any())
                <script>
                document.addEventListener('DOMContentLoaded', function(){
                    const errs = @json($errors->all());
                    errs.forEach(function(msg){
                        const n = document.createElement('div');
                        n.className = 'fixed top-4 right-4 px-4 py-2 mb-2 rounded shadow-lg z-50 bg-red-600 text-white';
                        n.textContent = msg;
                        document.body.appendChild(n);
                        setTimeout(()=> n.remove(), 4000);
                    });
                });
                </script>
            @endif
            @if(session('success'))
                <script>
                document.addEventListener('DOMContentLoaded', function(){
                    const n = document.createElement('div');
                    n.className = 'fixed top-4 right-4 px-4 py-2 rounded shadow-lg z-50 bg-green-600 text-white';
                    n.textContent = @json(session('success'));
                    document.body.appendChild(n);
                    setTimeout(()=> n.remove(), 3000);
                });
                </script>
            @endif
            
            <form method="POST" action="{{ route('tax_rates.store') }}" class="p-6 space-y-6" id="taxForm">
                @csrf
                
                <!-- Nome Amigável -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="name">Nome amigável</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" maxlength="100" placeholder="Ex: Bebidas Alcoólicas">
                    <p class="text-xs text-gray-500 mt-1">Opcional. Facilita a identificação no cadastro de produtos.</p>
                </div>

                <!-- Tipo de Nota -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tipo de Operação *
                        <span class="group relative">
                            <svg class="w-4 h-4 inline ml-1 text-gray-400 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                            </svg>
                            <div class="absolute bottom-6 left-0 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap z-10">
                                Defina se esta configuração é para produtos ou serviços
                            </div>
                        </span>
                    </label>
                    <select name="tipo_nota" id="tipo_nota" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" required>
                        <option value="">Selecione o tipo</option>
                        <option value="produto" {{ old('tipo_nota') === 'produto' ? 'selected' : '' }}>🏷️ Produto</option>
                        <option value="servico" {{ old('tipo_nota') === 'servico' ? 'selected' : '' }}>⚙️ Serviço</option>
                    </select>
                </div>



                <!-- Códigos de Identificação -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        Códigos de Identificação
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- NCM -->
                        <div id="ncm_field">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                NCM
                                <span class="group relative">
                                    <svg class="w-4 h-4 inline ml-1 text-gray-400 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                    </svg>
                                    <div class="absolute bottom-6 left-0 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap z-10">
                                        Nomenclatura Comum do Mercosul (para produtos)
                                    </div>
                                </span>
                            </label>
                            <input type="text" name="ncm" value="{{ old('ncm') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" maxlength="10" placeholder="Ex: 22030000">
                        </div>

                        <!-- CFOP -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                CFOP *
                                <span class="group relative">
                                    <svg class="w-4 h-4 inline ml-1 text-gray-400 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                    </svg>
                                    <div class="absolute bottom-6 left-0 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap z-10">
                                        Código Fiscal de Operações e Prestações
                                    </div>
                                </span>
                            </label>
                            <select name="cfop" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" required>
                                <option value="">Selecione o CFOP</option>
                                <optgroup label="Vendas dentro do Estado">
                                    <option value="5101" {{ old('cfop') === '5101' ? 'selected' : '' }}>5101 - Venda de produção do estabelecimento</option>
                                    <option value="5102" {{ old('cfop') === '5102' ? 'selected' : '' }}>5102 - Venda de mercadoria adquirida ou recebida de terceiros</option>
                                    <option value="5949" {{ old('cfop') === '5949' ? 'selected' : '' }}>5949 - Outra saída de mercadoria ou prestação de serviço não especificado</option>
                                </optgroup>
                                <optgroup label="Vendas para outros Estados">
                                    <option value="6101" {{ old('cfop') === '6101' ? 'selected' : '' }}>6101 - Venda de produção do estabelecimento</option>
                                    <option value="6102" {{ old('cfop') === '6102' ? 'selected' : '' }}>6102 - Venda de mercadoria adquirida ou recebida de terceiros</option>
                                    <option value="6949" {{ old('cfop') === '6949' ? 'selected' : '' }}>6949 - Outra saída de mercadoria ou prestação de serviço não especificado</option>
                                </optgroup>
                            </select>
                        </div>

                        <!-- Código de Serviço -->
                        <div id="codigo_servico_field">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Código de Serviço
                                <span class="group relative">
                                    <svg class="w-4 h-4 inline ml-1 text-gray-400 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                    </svg>
                                    <div class="absolute bottom-6 left-0 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap z-10">
                                        Código do serviço conforme Lei Complementar 116/2003
                                    </div>
                                </span>
                            </label>
                            <input type="text" name="codigo_servico" value="{{ old('codigo_servico') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" maxlength="30" placeholder="Ex: 01.01">
                        </div>
                    </div>
                </div>

                <!-- Alíquotas Tributárias -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Alíquotas Tributárias
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Redução da BC ICMS -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">% Redução da BC ICMS</label>
                            <input type="number" step="0.01" min="0" max="100" name="icms_reducao_bc_percent" value="{{ old('icms_reducao_bc') ? old('icms_reducao_bc') * 100 : '' }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="0,00">
                        </div>
                        <!-- PIS -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                PIS (%)
                                <span class="group relative">
                                    <svg class="w-4 h-4 inline ml-1 text-gray-400 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                    </svg>
                                    <div class="absolute bottom-6 left-0 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap z-10">
                                        Programa de Integração Social
                                    </div>
                                </span>
                            </label>
                            <input type="number" step="0.01" min="0" max="100" name="pis_aliquota_percent" value="{{ old('pis_aliquota') ? old('pis_aliquota') * 100 : '' }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="1,65">
                        </div>

                        <!-- COFINS -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                COFINS (%)
                                <span class="group relative">
                                    <svg class="w-4 h-4 inline ml-1 text-gray-400 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                    </svg>
                                    <div class="absolute bottom-6 left-0 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap z-10">
                                        Contribuição para Financiamento da Seguridade Social
                                    </div>
                                </span>
                            </label>
                            <input type="number" step="0.01" min="0" max="100" name="cofins_aliquota_percent" value="{{ old('cofins_aliquota') ? old('cofins_aliquota') * 100 : '' }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="7,60">
                        </div>

                        <!-- ICMS -->
                        <div id="icms_field">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ICMS (%)
                                <span class="group relative">
                                    <svg class="w-4 h-4 inline ml-1 text-gray-400 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                    </svg>
                                    <div class="absolute bottom-6 left-0 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap z-10">
                                        Imposto sobre Circulação de Mercadorias e Serviços (só produtos)
                                    </div>
                                </span>
                            </label>
                            <input type="number" step="0.01" min="0" max="100" name="icms_aliquota_percent" value="{{ old('icms_aliquota') ? old('icms_aliquota') * 100 : '' }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="18,00">
                        </div>

                        <!-- ISS -->
                        <div id="iss_field">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ISS (%)
                                <span class="group relative">
                                    <svg class="w-4 h-4 inline ml-1 text-gray-400 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                    </svg>
                                    <div class="absolute bottom-6 left-0 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap z-10">
                                        Imposto sobre Serviços (só serviços)
                                    </div>
                                </span>
                            </label>
                            <input type="number" step="0.01" min="0" max="100" name="iss_aliquota_percent" value="{{ old('iss_aliquota') ? old('iss_aliquota') * 100 : '' }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="5,00">
                        </div>

                        <!-- CSLL -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                CSLL (%)
                                <span class="group relative">
                                    <svg class="w-4 h-4 inline ml-1 text-gray-400 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                    </svg>
                                    <div class="absolute bottom-6 left-0 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap z-10">
                                        Contribuição Social sobre o Lucro Líquido
                                    </div>
                                </span>
                            </label>
                            <input type="number" step="0.01" min="0" max="100" name="csll_aliquota_percent" value="{{ old('csll_aliquota') ? old('csll_aliquota') * 100 : '' }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="9,00">
                        </div>

                        <!-- INSS -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                INSS (%)
                                <span class="group relative">
                                    <svg class="w-4 h-4 inline ml-1 text-gray-400 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                    </svg>
                                    <div class="absolute bottom-6 left-0 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap z-10">
                                        Instituto Nacional do Seguro Social
                                    </div>
                                </span>
                            </label>
                            <input type="number" step="0.01" min="0" max="100" name="inss_aliquota_percent" value="{{ old('inss_aliquota') ? old('inss_aliquota') * 100 : '' }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="11,00">
                        </div>

                        <!-- IRRF -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                IRRF (%)
                                <span class="group relative">
                                    <svg class="w-4 h-4 inline ml-1 text-gray-400 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                    </svg>
                                    <div class="absolute bottom-6 left-0 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap z-10">
                                        Imposto de Renda Retido na Fonte
                                    </div>
                                </span>
                            </label>
                            <input type="number" step="0.01" min="0" max="100" name="irrf_aliquota_percent" value="{{ old('irrf_aliquota') ? old('irrf_aliquota') * 100 : '' }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="1,50">
                        </div>
                    </div>
                </div>

                                <!-- ICMS-ST -->
                                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-medium text-gray-800 mb-4">ICMS-ST</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Modalidade ICMS-ST</label>
                            <select name="icmsst_modalidade" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="">Selecione</option>
                                <option value="0" {{ old('icmsst_modalidade')==='0' ? 'selected' : '' }}>0 - Margem de valor agregado (MVA)</option>
                                <option value="1" {{ old('icmsst_modalidade')==='1' ? 'selected' : '' }}>1 - Pauta (valor fixo)</option>
                                <option value="2" {{ old('icmsst_modalidade')==='2' ? 'selected' : '' }}>2 - Preço tabelado máximo</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">% MVA</label>
                            <input type="number" step="0.01" min="0" max="100" name="icmsst_mva_percent" value="{{ old('icmsst_mva') ? old('icmsst_mva') * 100 : '' }}" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="0,00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alíquota ICMS-ST (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="icmsst_aliquota_percent" value="{{ old('icmsst_aliquota') ? old('icmsst_aliquota') * 100 : '' }}" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="0,00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">% Redução da BC ICMS-ST</label>
                            <input type="number" step="0.01" min="0" max="100" name="icmsst_reducao_bc_percent" value="{{ old('icmsst_reducao_bc') ? old('icmsst_reducao_bc') * 100 : '' }}" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="0,00">
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <input type="hidden" name="ativo" value="0">
                        <input type="checkbox" name="ativo" value="1" checked id="ativo" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                        <label for="ativo" class="ml-2 block text-sm text-gray-900 font-medium">
                            Configuração ativa
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Marque para usar esta configuração nos cálculos fiscais</p>
                </div>

                <!-- Botões -->
                <div class="flex justify-end space-x-3 pt-4">
                    <a href="{{ route('tax_rates.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Cancelar
                    </a>
                    <button type="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        💾 Salvar Configuração
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tipoNota = document.getElementById('tipo_nota');
            const ncmField = document.getElementById('ncm_field');
            const icmsField = document.getElementById('icms_field');
            const codigoServicoField = document.getElementById('codigo_servico_field');
            const issField = document.getElementById('iss_field');

            function toggleFields() {
                const tipo = tipoNota.value;
                
                if (tipo === 'produto') {
                    // Mostrar campos para produtos
                    ncmField.style.display = 'block';
                    icmsField.style.display = 'block';
                    // Esconder campos de serviços
                    codigoServicoField.style.display = 'none';
                    issField.style.display = 'none';
                    // Limpar campos de serviços
                    document.querySelector('input[name="codigo_servico"]').value = '';
                    document.querySelector('input[name="iss_aliquota_percent"]').value = '';
                } else if (tipo === 'servico') {
                    // Mostrar campos para serviços
                    codigoServicoField.style.display = 'block';
                    issField.style.display = 'block';
                    // Esconder campos de produtos
                    ncmField.style.display = 'none';
                    icmsField.style.display = 'none';
                    // Limpar campos de produtos
                    document.querySelector('input[name="ncm"]').value = '';
                    document.querySelector('input[name="icms_aliquota_percent"]').value = '';
                } else {
                    // Mostrar todos os campos quando nenhum tipo selecionado
                    ncmField.style.display = 'block';
                    icmsField.style.display = 'block';
                    codigoServicoField.style.display = 'block';
                    issField.style.display = 'block';
                }
            }

            tipoNota.addEventListener('change', toggleFields);
            toggleFields(); // Executa na inicialização

            // Converter percentuais para decimais antes de enviar
            document.getElementById('taxForm').addEventListener('submit', function(e) {
                const percentFields = ['pis_aliquota_percent', 'cofins_aliquota_percent', 'icms_aliquota_percent', 'icms_reducao_bc_percent', 'iss_aliquota_percent', 'csll_aliquota_percent', 'inss_aliquota_percent', 'irrf_aliquota_percent', 'icmsst_mva_percent', 'icmsst_aliquota_percent', 'icmsst_reducao_bc_percent'];
                
                percentFields.forEach(fieldName => {
                    const field = document.querySelector(`input[name="${fieldName}"]`);
                    if (field && field.value) {
                        const decimalName = fieldName.replace('_percent', '');
                        const decimalValue = parseFloat(field.value) / 100;
                        
                        // Criar campo hidden com valor decimal
                        const hiddenField = document.createElement('input');
                        hiddenField.type = 'hidden';
                        hiddenField.name = decimalName;
                        hiddenField.value = decimalValue;
                        
                        this.appendChild(hiddenField);
                        field.disabled = true; // Desabilita o campo de percentual
                    }
                });
            });
        });
    </script>
    @endpush
</x-app-layout>


