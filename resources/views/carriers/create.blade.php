<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nova Transportadora</h2>
            <a href="{{ route('carriers.index') }}" class="text-gray-700">Voltar</a>
        </div>
    </x-slot>

    <div class="bg-white p-6 rounded shadow max-w-5xl">
        <form action="{{ route('carriers.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Dados da Transportadora -->
            <div class="border rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-800">Dados da Transportadora</h3>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="active" value="1" class="h-4 w-4" {{ old('active', true) ? 'checked' : '' }}>
                        <span>Ativo</span>
                    </label>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Nome<span class="text-red-600">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded px-3 py-2 {{ $errors->has('name') ? 'border-red-500' : '' }}" placeholder="Transportadora XPTO" required>
                        @error('name')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Nome Fantasia</label>
                        <input type="text" name="trade_name" value="{{ old('trade_name') }}" class="w-full border rounded px-3 py-2" placeholder="XPTO Logística">
                    </div>
                </div>
            </div>

            <!-- Documentos -->
            <div class="border rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Documentos</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">CNPJ</label>
                        <input type="text" name="cnpj" value="{{ old('cnpj') }}" class="w-full border rounded px-3 py-2" placeholder="00.000.000/0000-00" inputmode="numeric" pattern="^\d{2}\.\d{3}\.\d{3}/\d{4}-\d{2}$" title="Formato: 00.000.000/0000-00">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Inscrição Estadual</label>
                        <input type="text" name="ie" value="{{ old('ie') }}" class="w-full border rounded px-3 py-2" placeholder="ISENTO ou número">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">RNTRC</label>
                        <input type="text" name="rntc" value="{{ old('rntc') }}" class="w-full border rounded px-3 py-2" placeholder="Registro RNTRC">
                    </div>
                </div>
            </div>

            <!-- Endereço -->
            <div class="border rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Endereço</h3>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-600 mb-1">CEP</label>
                        <input id="zip_code" type="text" name="zip_code" value="{{ old('zip_code') }}" class="w-full border rounded px-3 py-2" placeholder="00000-000" inputmode="numeric" pattern="^\d{5}-\d{3}$" title="Formato: 00000-000">
                        <div id="cep_help" class="text-[11px] text-gray-500 mt-1"></div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">UF</label>
                        <input id="state" type="text" name="state" value="{{ old('state') }}" maxlength="2" class="w-full border rounded px-3 py-2" placeholder="UF" pattern="^[A-Z]{2}$" title="Use UF com 2 letras, ex.: SP">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Número</label>
                        <input type="text" name="number" value="{{ old('number') }}" class="w-full border rounded px-3 py-2" placeholder="Nº">
                    </div>
                    <div class="md:col-span-5">
                        <label class="block text-xs text-gray-600 mb-1">Complemento</label>
                        <input type="text" name="complement" value="{{ old('complement') }}" class="w-full border rounded px-3 py-2" placeholder="Sala, Bloco, ...">
                    </div>
                    <div class="md:col-span-6">
                        <label class="block text-xs text-gray-600 mb-1">Logradouro</label>
                        <input id="street" type="text" name="street" value="{{ old('street') }}" class="w-full border rounded px-3 py-2" placeholder="Rua, Avenida...">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-600 mb-1">Bairro</label>
                        <input id="district" type="text" name="district" value="{{ old('district') }}" class="w-full border rounded px-3 py-2">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-600 mb-1">Cidade</label>
                        <input id="city" type="text" name="city" value="{{ old('city') }}" class="w-full border rounded px-3 py-2">
                    </div>
                </div>
            </div>

            <!-- Contato e Veículo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="border rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">Contato</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Telefone</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="w-full border rounded px-3 py-2" placeholder="(00) 00000-0000" inputmode="numeric" pattern="^\(\d{2}\)\s?\d{4,5}-\d{4}$" title="Formato: (00) 00000-0000 ou (00) 0000-0000">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="w-full border rounded px-3 py-2" placeholder="contato@empresa.com">
                        </div>
                    </div>
                </div>
                <div class="border rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">Veículo</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Placa</label>
                            <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate') }}" class="w-full border rounded px-3 py-2" placeholder="AAA0A00" pattern="^([A-Z]{3}-?\d{4}|[A-Z]{3}\d[A-Z]\d{2})$" title="Placa antiga (AAA-0000) ou Mercosul (AAA0A00)">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">UF Placa</label>
                            <input type="text" name="vehicle_state" value="{{ old('vehicle_state') }}" maxlength="2" class="w-full border rounded px-3 py-2" placeholder="UF" pattern="^[A-Z]{2}$" title="Use UF com 2 letras, ex.: SP">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('carriers.index') }}" class="px-4 py-2 bg-gray-100 text-gray-800 rounded hover:bg-gray-200">Cancelar</a>
                <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Salvar</button>
            </div>
        </form>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        function onlyDigits(value) { return (value || '').replace(/\D+/g, ''); }
        function maskCNPJ(value) {
            const v = onlyDigits(value).slice(0,14);
            let out = '';
            if (v.length > 0) out = v.slice(0,2);
            if (v.length >= 3) out += '.' + v.slice(2,5);
            if (v.length >= 6) out += '.' + v.slice(5,8);
            if (v.length >= 9) out += '/' + v.slice(8,12);
            if (v.length >= 13) out += '-' + v.slice(12,14);
            return out;
        }
        function maskCEP(value) {
            const v = onlyDigits(value).slice(0,8);
            return v.length > 5 ? v.slice(0,5) + '-' + v.slice(5) : v;
        }
        function maskPhone(value) {
            const v = onlyDigits(value).slice(0,11);
            if (v.length <= 10) {
                const p1 = v.slice(0,2);
                const p2 = v.slice(2,6);
                const p3 = v.slice(6,10);
                return (p1 ? '('+p1+') ' : '') + p2 + (p3 ? '-' + p3 : '');
            } else {
                const p1 = v.slice(0,2);
                const p2 = v.slice(2,7);
                const p3 = v.slice(7,11);
                return (p1 ? '('+p1+') ' : '') + p2 + (p3 ? '-' + p3 : '');
            }
        }
        function upper(value) { return (value || '').toUpperCase(); }

        const cnpj = document.querySelector('input[name="cnpj"]');
        const cep = document.querySelector('#zip_code');
        const phone = document.querySelector('input[name="phone"]');
        const ufInputs = [document.querySelector('#state'), document.querySelector('input[name="vehicle_state"]')].filter(Boolean);
        const plate = document.querySelector('input[name="vehicle_plate"]');
        const street = document.querySelector('#street');
        const district = document.querySelector('#district');
        const city = document.querySelector('#city');
        const cepHelp = document.querySelector('#cep_help');
        const numberInput = document.querySelector('input[name="number"]');

        if (cnpj) cnpj.addEventListener('input', () => { cnpj.value = maskCNPJ(cnpj.value); });
        async function viaCepLookup(cepDigits, shouldFocus = false) {
            try {
                const r = await fetch('https://viacep.com.br/ws/' + cepDigits + '/json/');
                if (!r.ok) return;
                const data = await r.json();
                if (data && !data.erro) {
                    if (street) street.value = data.logradouro || '';
                    if (district) district.value = data.bairro || '';
                    if (city) city.value = data.localidade || '';
                    if (ufInputs[0]) ufInputs[0].value = (data.uf || '').toUpperCase();
                    if (cepHelp) cepHelp.textContent = (data.logradouro || '') + (data.bairro ? ' - ' + data.bairro : '') + (data.localidade ? ' - ' + data.localidade : '') + (data.uf ? '/' + data.uf : '');
                    if (shouldFocus && numberInput) {
                        numberInput.focus();
                    }
                } else if (cepHelp) {
                    cepHelp.textContent = 'CEP não encontrado.';
                }
            } catch (e) {
                if (cepHelp) cepHelp.textContent = 'Falha ao consultar CEP.';
            }
        }

        if (cep) {
            let lastQuery = '';
            cep.addEventListener('input', () => {
                const before = cep.value;
                cep.value = maskCEP(cep.value);
                const digits = onlyDigits(cep.value);
                if (digits.length === 8 && digits !== lastQuery) {
                    lastQuery = digits;
                    viaCepLookup(digits, true);
                } else if (digits.length < 8 && cepHelp) {
                    cepHelp.textContent = '';
                }
            });
            // Auto-trigger if already filled (edit flows)
            const d = onlyDigits(cep.value);
            if (d.length === 8) viaCepLookup(d, false);
        }
        if (phone) phone.addEventListener('input', () => { phone.value = maskPhone(phone.value); });
        ufInputs.forEach(i => i && i.addEventListener('input', () => { i.value = upper(i.value).slice(0,2); }));
        if (plate) plate.addEventListener('input', () => { plate.value = upper(plate.value).slice(0,7); });
    });
    </script>
</x-app-layout>
