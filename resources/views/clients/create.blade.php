<x-app-layout>
    <div class="bg-white shadow-lg rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-green-100">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Novo Cliente</h2>
                    <p class="text-gray-600 text-sm">Cadastre um novo cliente no sistema</p>
                </div>
                <a href="{{ route('clients.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>

        <div class="p-6">
            @if(session('error'))
                <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded">
                    {!! session('error') !!}
                </div>
            @endif
            <form method="POST" action="{{ route('clients.store') }}" class="space-y-6">
                @csrf
                
                <!-- Informações Básicas -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Informações Básicas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
                            <input type="text" name="name" value="{{ old('name') }}" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('name') border-red-500 @enderror" 
                                   required>
                            @error('name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('email') border-red-500 @enderror">
                            @error('email')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="(11) 99999-9999"
                                   inputmode="numeric" pattern="^\(\d{2}\)\s?\d{4,5}-\d{4}$" title="Formato: (00) 00000-0000 ou (00) 0000-0000"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('phone') border-red-500 @enderror">
                            @error('phone')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Pessoa *</label>
                            <select name="type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('type') border-red-500 @enderror" required>
                                <option value="pf" {{ old('type') == 'pf' ? 'selected' : '' }}>Pessoa Física</option>
                                <option value="pj" {{ old('type') == 'pj' ? 'selected' : '' }}>Pessoa Jurídica</option>
                            </select>
                            @error('type')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Documentos -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Documentos</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CPF/CNPJ *</label>
                            <input type="text" name="cpf_cnpj" value="{{ old('cpf_cnpj') }}" placeholder="000.000.000-00 ou 00.000.000/0000-00"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('cpf_cnpj') border-red-500 @enderror" 
                                   pattern="^(\d{3}\.\d{3}\.\d{3}-\d{2}|\d{2}\.\d{3}\.\d{3}/\d{4}-\d{2})$" title="CPF (000.000.000-00) ou CNPJ (00.000.000/0000-00)" required>
                            @error('cpf_cnpj')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">RG/IE</label>
                            <input type="text" name="ie_rg" value="{{ old('ie_rg') }}" placeholder="RG ou Inscrição Estadual"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('ie_rg') border-red-500 @enderror">
                            @error('ie_rg')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Endereço -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Endereço</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                            <input id="zip_code" type="text" name="zip_code" value="{{ old('zip_code') }}" placeholder="00000-000"
                                   inputmode="numeric" pattern="^\d{5}-\d{3}$" title="Formato: 00000-000"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('zip_code') border-red-500 @enderror">
                            @error('zip_code')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <select id="state" name="state" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('state') border-red-500 @enderror">
                                <option value="">Selecione</option>
                                <option value="AC" {{ old('state') == 'AC' ? 'selected' : '' }}>AC - Acre</option>
                                <option value="AL" {{ old('state') == 'AL' ? 'selected' : '' }}>AL - Alagoas</option>
                                <option value="AP" {{ old('state') == 'AP' ? 'selected' : '' }}>AP - Amapá</option>
                                <option value="AM" {{ old('state') == 'AM' ? 'selected' : '' }}>AM - Amazonas</option>
                                <option value="BA" {{ old('state') == 'BA' ? 'selected' : '' }}>BA - Bahia</option>
                                <option value="CE" {{ old('state') == 'CE' ? 'selected' : '' }}>CE - Ceará</option>
                                <option value="DF" {{ old('state') == 'DF' ? 'selected' : '' }}>DF - Distrito Federal</option>
                                <option value="ES" {{ old('state') == 'ES' ? 'selected' : '' }}>ES - Espírito Santo</option>
                                <option value="GO" {{ old('state') == 'GO' ? 'selected' : '' }}>GO - Goiás</option>
                                <option value="MA" {{ old('state') == 'MA' ? 'selected' : '' }}>MA - Maranhão</option>
                                <option value="MT" {{ old('state') == 'MT' ? 'selected' : '' }}>MT - Mato Grosso</option>
                                <option value="MS" {{ old('state') == 'MS' ? 'selected' : '' }}>MS - Mato Grosso do Sul</option>
                                <option value="MG" {{ old('state') == 'MG' ? 'selected' : '' }}>MG - Minas Gerais</option>
                                <option value="PA" {{ old('state') == 'PA' ? 'selected' : '' }}>PA - Pará</option>
                                <option value="PB" {{ old('state') == 'PB' ? 'selected' : '' }}>PB - Paraíba</option>
                                <option value="PR" {{ old('state') == 'PR' ? 'selected' : '' }}>PR - Paraná</option>
                                <option value="PE" {{ old('state') == 'PE' ? 'selected' : '' }}>PE - Pernambuco</option>
                                <option value="PI" {{ old('state') == 'PI' ? 'selected' : '' }}>PI - Piauí</option>
                                <option value="RJ" {{ old('state') == 'RJ' ? 'selected' : '' }}>RJ - Rio de Janeiro</option>
                                <option value="RN" {{ old('state') == 'RN' ? 'selected' : '' }}>RN - Rio Grande do Norte</option>
                                <option value="RS" {{ old('state') == 'RS' ? 'selected' : '' }}>RS - Rio Grande do Sul</option>
                                <option value="RO" {{ old('state') == 'RO' ? 'selected' : '' }}>RO - Rondônia</option>
                                <option value="RR" {{ old('state') == 'RR' ? 'selected' : '' }}>RR - Roraima</option>
                                <option value="SC" {{ old('state') == 'SC' ? 'selected' : '' }}>SC - Santa Catarina</option>
                                <option value="SP" {{ old('state') == 'SP' ? 'selected' : '' }}>SP - São Paulo</option>
                                <option value="SE" {{ old('state') == 'SE' ? 'selected' : '' }}>SE - Sergipe</option>
                                <option value="TO" {{ old('state') == 'TO' ? 'selected' : '' }}>TO - Tocantins</option>
                            </select>
                            @error('state')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                            <input type="text" name="number" value="{{ old('number') }}" placeholder="123"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('number') border-red-500 @enderror">
                            @error('number')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
                            <input id="address" type="text" name="address" value="{{ old('address') }}" placeholder="Rua, Avenida..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('address') border-red-500 @enderror">
                            @error('address')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                            <input type="text" name="complement" value="{{ old('complement') }}" placeholder="Apto, Sala..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('complement') border-red-500 @enderror">
                            @error('complement')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                            <input id="neighborhood" type="text" name="neighborhood" value="{{ old('neighborhood') }}" placeholder="Centro"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('neighborhood') border-red-500 @enderror">
                            @error('neighborhood')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                            <input id="city" type="text" name="city" value="{{ old('city') }}" placeholder="São Paulo"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('city') border-red-500 @enderror">
                            @error('city')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código IBGE</label>
                            <input type="text" name="codigo_ibge" value="{{ old('codigo_ibge') }}" placeholder="4314902"
                                   maxlength="7" pattern="^\d{7}$" title="Código IBGE da cidade (7 dígitos)"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('codigo_ibge') border-red-500 @enderror">
                            @error('codigo_ibge')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Configurações -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Configurações</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                            <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('status') border-red-500 @enderror" required>
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Ativo</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inativo</option>
                            </select>
                            @error('status')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Consumidor Final *</label>
                            <select name="consumidor_final" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('consumidor_final') border-red-500 @enderror" required>
                                <option value="N" {{ old('consumidor_final', 'N') == 'N' ? 'selected' : '' }}>Não</option>
                                <option value="S" {{ old('consumidor_final') == 'S' ? 'selected' : '' }}>Sim</option>
                            </select>
                            @error('consumidor_final')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">Indica se é consumidor final para emissão de NFe</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                            <textarea name="observations" rows="3" placeholder="Observações gerais sobre o cliente..."
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 @error('observations') border-red-500 @enderror">{{ old('observations') }}</textarea>
                            @error('observations')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="border-t pt-6">
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('clients.index') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400 transition-colors">
                            Cancelar
                        </a>
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Salvar Cliente
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        function onlyDigits(v){ return (v||'').replace(/\D+/g,''); }
        function maskPhone(v){
            const d = onlyDigits(v).slice(0,11);
            if(d.length<=10){
                const p1=d.slice(0,2), p2=d.slice(2,6), p3=d.slice(6,10);
                return (p1?`(${p1}) `:'') + p2 + (p3?`-${p3}`:'');
            }
            const p1=d.slice(0,2), p2=d.slice(2,7), p3=d.slice(7,11);
            return (p1?`(${p1}) `:'') + p2 + (p3?`-${p3}`:'');
        }
        function maskCpfCnpj(v){
            const d = onlyDigits(v).slice(0,14);
            if(d.length<=11){
                // CPF
                let out = d;
                if(d.length>3) out = d.slice(0,3)+'.'+d.slice(3);
                if(d.length>6) out = out.slice(0,7)+'.'+out.slice(7);
                if(d.length>9) out = out.slice(0,11)+'-'+out.slice(11);
                return out;
            }
            // CNPJ
            let out = d.slice(0,2);
            if(d.length>=3) out += '.'+d.slice(2,5);
            if(d.length>=6) out += '.'+d.slice(5,8);
            if(d.length>=9) out += '/'+d.slice(8,12);
            if(d.length>=13) out += '-'+d.slice(12,14);
            return out;
        }
        function maskCep(v){ const d=onlyDigits(v).slice(0,8); return d.length>5? d.slice(0,5)+'-'+d.slice(5): d; }

        const tel = document.querySelector('input[name="phone"]');
        const doc = document.querySelector('input[name="cpf_cnpj"]');
        const cep = document.querySelector('#zip_code');
        const address = document.querySelector('#address');
        const neighborhood = document.querySelector('#neighborhood');
        const city = document.querySelector('#city');
        const state = document.querySelector('#state');
        const numberInput = document.querySelector('input[name="number"]');
        if(tel) tel.addEventListener('input', ()=> tel.value = maskPhone(tel.value));
        if(doc) doc.addEventListener('input', ()=> doc.value = maskCpfCnpj(doc.value));
        async function viaCepLookup(digits, shouldFocus=false){
            try{
                const r = await fetch('https://viacep.com.br/ws/'+digits+'/json/');
                if(!r.ok) return;
                const data = await r.json();
                if(data && !data.erro){
                    if(address) address.value = data.logradouro || '';
                    if(neighborhood) neighborhood.value = data.bairro || '';
                    if(city) city.value = data.localidade || '';
                    if(state){
                        const uf = (data.uf || '').toUpperCase();
                        if(state.tagName.toLowerCase()==='select'){
                            state.value = uf;
                        } else {
                            state.value = uf;
                        }
                    }
                    if(shouldFocus && numberInput){ numberInput.focus(); }
                }
            }catch(e){ /* noop */ }
        }
        if(cep){
            let last='';
            cep.addEventListener('input', ()=>{
                cep.value = maskCep(cep.value);
                const d = onlyDigits(cep.value);
                if(d.length===8 && d!==last){ last=d; viaCepLookup(d,true); }
            });
            const d0 = onlyDigits(cep.value);
            if(d0.length===8){ viaCepLookup(d0,false); }
        }
    });
    </script>
</x-app-layout>