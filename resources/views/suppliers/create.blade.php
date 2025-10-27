<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo Fornecedor</h2>
            <a href="{{ route('suppliers.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto">
        <form id="supplierForm" action="{{ route('suppliers.store') }}" method="POST" class="space-y-8">
            @csrf

            <!-- Dados do Fornecedor -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800">Dados do Fornecedor</h3>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="active" value="1" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2" {{ old('active', true) ? 'checked' : '' }}>
                            <span>Ativo</span>
                        </label>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Nome <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('name') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror" 
                                   required>
                            @error('name')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Nome Fantasia <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="trade_name" value="{{ old('trade_name') }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('trade_name') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror" 
                                   required>
                            @error('trade_name')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                CPF/CNPJ <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="cpf_cnpj" value="{{ old('cpf_cnpj') }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('cpf_cnpj') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror" 
                                   placeholder="000.000.000-00 ou 00.000.000/0000-00" inputmode="numeric" required>
                            @error('cpf_cnpj')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">RG/IE</label>
                            <input type="text" name="ie_rg" value="{{ old('ie_rg') }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('ie_rg') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                            @error('ie_rg')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contato -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800">Informações de Contato</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">E-mail</label>
                            <input type="email" name="email" value="{{ old('email') }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('email') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                            @error('email')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Telefone</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('phone') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror" 
                                   placeholder="(00) 00000-0000" inputmode="numeric">
                            @error('phone')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Endereço -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-50 to-violet-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800">Endereço</h3>
                    </div>
                </div>
                <div class="p-6">
                    <!-- CEP e Busca -->
                    <div class="mb-6">
                        <div class="flex items-center gap-4">
                            <div class="flex-1 max-w-xs">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    CEP <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input id="zip_code" type="text" name="zip_code" value="{{ old('zip_code') }}" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('zip_code') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror" 
                                           placeholder="00000-000" inputmode="numeric" required>
                                    <div id="cep-loading" class="absolute right-3 top-1/2 transform -translate-y-1/2 hidden">
                                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                                    </div>
                                </div>
                                @error('zip_code')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mt-6">
                                <button type="button" id="search-cep" class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    Buscar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Campos de Endereço -->
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div class="md:col-span-7 space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Endereço <span class="text-red-500">*</span>
                            </label>
                            <input id="address" type="text" name="address" value="{{ old('address') }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('address') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror" 
                                   required>
                            @error('address')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-4 space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Número</label>
                            <div class="flex items-center gap-2">
                                <input id="number" type="text" name="number" value="{{ old('number') }}" 
                                       class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('number') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror"
                                       placeholder="Número">
                                <label class="flex items-center gap-1 text-sm text-gray-600 cursor-pointer whitespace-nowrap">
                                    <input type="checkbox" id="sem-numero" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                    <span>S/N</span>
                                </label>
                            </div>
                            @error('number')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-3 space-y-2">
                            <label class="block text-sm font-medium text-gray-700">UF</label>
                            <input id="state" type="text" name="state" value="{{ old('state') }}" maxlength="2" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('state') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                            @error('state')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-4 space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Bairro</label>
                            <input id="neighborhood" type="text" name="neighborhood" value="{{ old('neighborhood') }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('neighborhood') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                            @error('neighborhood')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-4 space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Cidade</label>
                            <input id="city" type="text" name="city" value="{{ old('city') }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('city') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                            @error('city')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-4 space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Complemento</label>
                            <input type="text" name="complement" value="{{ old('complement') }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('complement') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                            @error('complement')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="flex items-center justify-end gap-4 pt-6">
                <a href="{{ route('suppliers.index') }}" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200 font-medium">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 font-medium flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Salvar Fornecedor
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // Elementos do formulário
    const form = document.getElementById('supplierForm');
    const cep = document.querySelector('#zip_code');
    const address = document.querySelector('#address');
    const neighborhood = document.querySelector('#neighborhood');
    const city = document.querySelector('#city');
    const state = document.querySelector('#state');
    const numberInput = document.querySelector('#number');
    const cepLoading = document.querySelector('#cep-loading');
    const searchCepBtn = document.querySelector('#search-cep');
    
    // Máscaras e formatação
    function onlyDigits(v){ return (v||'').replace(/\D+/g,''); }
    function maskCep(v){ const d=onlyDigits(v).slice(0,8); return d.length>5? d.slice(0,5)+'-'+d.slice(5): d; }
    function maskPhone(v){ 
        const d = onlyDigits(v).slice(0,11);
        if(d.length <= 10) return d.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        return d.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    }
    function maskCpfCnpj(v){
        const d = onlyDigits(v);
        if(d.length <= 11) return d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        return d.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    }
    
    // Validação de CEP
    function validateCep(cepValue) {
        const digits = onlyDigits(cepValue);
        return digits.length === 8 && /^\d{8}$/.test(digits);
    }
    
    // Busca ViaCEP
    async function viaCepLookup(digits, showLoading = true, focusNumber = false){
        if(!validateCep(digits)) return;
        
        if(showLoading && cepLoading) cepLoading.classList.remove('hidden');
        
        try{
            const response = await fetch(`https://viacep.com.br/ws/${digits}/json/`);
            if(!response.ok) throw new Error('Erro na requisição');
            
            const data = await response.json();
            
            if(data && !data.erro){
                if(address) address.value = data.logradouro || '';
                if(neighborhood) neighborhood.value = data.bairro || '';
                if(city) city.value = data.localidade || '';
                if(state) state.value = (data.uf||'').toUpperCase();
                
                // Feedback visual de sucesso
                if(cep) {
                    cep.classList.remove('border-red-500');
                    cep.classList.add('border-green-500');
                    setTimeout(() => cep.classList.remove('border-green-500'), 2000);
                }
                
                if(focusNumber && numberInput) {
                    setTimeout(() => numberInput.focus(), 300);
                }
            } else {
                // CEP não encontrado
                if(cep) {
                    cep.classList.add('border-red-500');
                    setTimeout(() => cep.classList.remove('border-red-500'), 2000);
                }
                showNotification('CEP não encontrado', 'error');
            }
        }catch(e){
            console.error('Erro ao buscar CEP:', e);
            showNotification('Erro ao buscar CEP. Tente novamente.', 'error');
        }finally{
            if(cepLoading) cepLoading.classList.add('hidden');
        }
    }
    
    // Notificações
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transition-all duration-300 ${
            type === 'error' ? 'bg-red-500 text-white' : 
            type === 'success' ? 'bg-green-500 text-white' : 
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Validação do formulário
    function validateForm() {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if(!field.value.trim()) {
                field.classList.add('border-red-500');
                isValid = false;
            } else {
                field.classList.remove('border-red-500');
            }
        });
        
        // Validação específica do CEP
        if(cep && cep.value && !validateCep(cep.value)) {
            cep.classList.add('border-red-500');
            isValid = false;
        }
        
        // Validação de email
        const emailField = form.querySelector('input[type="email"]');
        if(emailField && emailField.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
            emailField.classList.add('border-red-500');
            isValid = false;
        }
        
        return isValid;
    }
    
    // Event listeners
    if(cep){
        let lastCep = '';
        
        cep.addEventListener('input', (e) => {
            cep.value = maskCep(cep.value);
            const digits = onlyDigits(cep.value);
            
            if(digits.length === 8 && digits !== lastCep) {
                lastCep = digits;
                viaCepLookup(digits, true, true);
            }
        });
        
        // Busca manual do CEP
        if(searchCepBtn) {
            searchCepBtn.addEventListener('click', () => {
                const digits = onlyDigits(cep.value);
                if(digits.length === 8) {
                    viaCepLookup(digits, true, false);
                } else {
                    showNotification('Digite um CEP válido com 8 dígitos', 'error');
                }
            });
        }
        
        // Carregar dados se CEP já estiver preenchido
        const initialCep = onlyDigits(cep.value);
        if(initialCep.length === 8) {
            viaCepLookup(initialCep, false, false);
        }
    }
    
    // Máscara do telefone
    const phoneField = form.querySelector('input[name="phone"]');
    if(phoneField) {
        phoneField.addEventListener('input', (e) => {
            phoneField.value = maskPhone(e.target.value);
        });
    }
    
    // Máscara do CPF/CNPJ
    const cpfCnpjField = form.querySelector('input[name="cpf_cnpj"]');
    if(cpfCnpjField) {
        cpfCnpjField.addEventListener('input', (e) => {
            cpfCnpjField.value = maskCpfCnpj(e.target.value);
        });
    }
    
    // Validação antes do envio
    form.addEventListener('submit', (e) => {
        if(!validateForm()) {
            e.preventDefault();
            showNotification('Por favor, preencha todos os campos obrigatórios corretamente.', 'error');
            return false;
        }
        
        // Mostrar loading no botão de submit
        const submitBtn = form.querySelector('button[type="submit"]');
        if(submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                Salvando...
            `;
        }
    });
    
    // Limpar classes de erro ao digitar
    form.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', () => {
            input.classList.remove('border-red-500');
        });
    });
    
    // Controle do checkbox S/N
    const semNumeroCheckbox = document.getElementById('sem-numero');
    
    if (semNumeroCheckbox && numberInput) {
        semNumeroCheckbox.addEventListener('change', function() {
            if (this.checked) {
                numberInput.value = 'S/N';
                numberInput.disabled = true;
                numberInput.classList.add('bg-gray-100');
            } else {
                numberInput.value = '';
                numberInput.disabled = false;
                numberInput.classList.remove('bg-gray-100');
            }
        });
        
        // Verificar estado inicial
        if (numberInput.value === 'S/N') {
            semNumeroCheckbox.checked = true;
            numberInput.disabled = true;
            numberInput.classList.add('bg-gray-100');
        }
    }
    
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
});
</script>