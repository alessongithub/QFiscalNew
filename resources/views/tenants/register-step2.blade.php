<x-guest-layout>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gray-100">
        <div class="text-center mb-8">
            <img src="{{ asset('logo/logo.png') }}" alt="QFiscal" class="w-48 h-auto mx-auto mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Dados da Empresa</h1>
        </div>

        <div class="w-full sm:max-w-md px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <!-- Progresso -->
            <div class="mb-6">
                <div class="flex items-center mb-2">
                    <div class="flex items-center text-green-600">
                        <div class="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full text-sm font-medium">✓</div>
                        <span class="ml-2 text-sm font-medium">Dados Básicos</span>
                    </div>
                    <div class="flex-1 h-px bg-green-600 mx-4"></div>
                    <div class="flex items-center text-blue-600">
                        <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-medium">2</div>
                        <span class="ml-2 text-sm font-medium">Dados da Empresa</span>
                    </div>
                    <div class="flex-1 h-px bg-gray-300 mx-4"></div>
                    <div class="flex items-center text-gray-400">
                        <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-600 rounded-full text-sm font-medium">3</div>
                        <span class="ml-2 text-sm font-medium">Pagamento</span>
                    </div>
                </div>
            </div>

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('tenant.register.step2') }}">
                @csrf

                <!-- Dados Básicos da Empresa -->
                <div class="space-y-4">
                    <!-- Nome/Razão Social -->
                    <div>
                        <x-input-label for="name" value="Razão Social *" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <!-- Nome Fantasia -->
                    <div>
                        <x-input-label for="fantasy_name" value="Nome Fantasia" />
                        <x-text-input id="fantasy_name" class="block mt-1 w-full" type="text" name="fantasy_name" :value="old('fantasy_name')" />
                        <x-input-error :messages="$errors->get('fantasy_name')" class="mt-2" />
                    </div>

                    <!-- CNPJ -->
                    <div>
                        <x-input-label for="cnpj" value="CNPJ *" />
                        <x-text-input id="cnpj" class="block mt-1 w-full" type="text" name="cnpj" :value="old('cnpj')" required placeholder="00.000.000/0000-00" />
                        <x-input-error :messages="$errors->get('cnpj')" class="mt-2" />
                    </div>

                    <!-- Telefone -->
                    <div>
                        <x-input-label for="phone" value="Telefone *" />
                        <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" required placeholder="(00) 00000-0000" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>

                    <!-- CEP -->
                    <div>
                        <x-input-label for="zip_code" value="CEP *" />
                        <x-text-input id="zip_code" class="block mt-1 w-full" type="text" name="zip_code" :value="old('zip_code')" required placeholder="00000-000" />
                        <x-input-error :messages="$errors->get('zip_code')" class="mt-2" />
                    </div>

                    <!-- Endereço -->
                    <div>
                        <x-input-label for="address" value="Endereço *" />
                        <x-text-input id="address" class="block mt-1 w-full" type="text" name="address" :value="old('address')" required />
                        <x-input-error :messages="$errors->get('address')" class="mt-2" />
                    </div>

                    <!-- Número -->
                    <div>
                        <x-input-label for="number" value="Número *" />
                        <x-text-input id="number" class="block mt-1 w-full" type="text" name="number" :value="old('number')" required />
                        <x-input-error :messages="$errors->get('number')" class="mt-2" />
                    </div>

                    <!-- Complemento -->
                    <div>
                        <x-input-label for="complement" value="Complemento" />
                        <x-text-input id="complement" class="block mt-1 w-full" type="text" name="complement" :value="old('complement')" />
                        <x-input-error :messages="$errors->get('complement')" class="mt-2" />
                    </div>

                    <!-- Bairro -->
                    <div>
                        <x-input-label for="neighborhood" value="Bairro *" />
                        <x-text-input id="neighborhood" class="block mt-1 w-full" type="text" name="neighborhood" :value="old('neighborhood')" required />
                        <x-input-error :messages="$errors->get('neighborhood')" class="mt-2" />
                    </div>

                    <!-- Cidade -->
                    <div>
                        <x-input-label for="city" value="Cidade *" />
                        <x-text-input id="city" class="block mt-1 w-full" type="text" name="city" :value="old('city')" required />
                        <x-input-error :messages="$errors->get('city')" class="mt-2" />
                    </div>

                    <!-- Estado -->
                    <div>
                        <x-input-label for="state" value="Estado *" />
                        <select id="state" name="state" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">Selecione...</option>
                            @foreach(['AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'] as $uf => $estado)
                                <option value="{{ $uf }}" {{ old('state') == $uf ? 'selected' : '' }}>{{ $estado }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('state')" class="mt-2" />
                    </div>
                </div>

                <!-- Plano -->
                <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <h3 class="text-lg font-semibold mb-2 text-green-800">Plano Selecionado: Gratuito</h3>
                    <div class="text-sm text-green-700">
                        <p><strong>Incluso no plano gratuito:</strong></p>
                        <ul class="list-disc list-inside mt-1">
                            <li>1 usuário administrador</li>
                            <li>Até 50 clientes cadastrados</li>
                            <li>Gestão básica de clientes</li>
                            <li>Suporte por email</li>
                        </ul>
                        <p class="mt-2 text-xs">Você poderá fazer upgrade para planos pagos a qualquer momento.</p>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-6">
                    <a href="{{ route('tenant.register') }}" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        ← Voltar
                    </a>

                    <x-primary-button class="ml-4">
                        Finalizar Cadastro
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#cnpj').mask('00.000.000/0000-00');
            $('#phone').mask('(00) 00000-0000');
            $('#zip_code').mask('00000-000');
            
            // Buscar CEP
            $('#zip_code').blur(function() {
                var cep = $(this).val().replace(/\D/g, '');
                if (cep.length === 8) {
                    $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function(data) {
                        if (!data.erro) {
                            $('#address').val(data.logradouro);
                            $('#neighborhood').val(data.bairro);
                            $('#city').val(data.localidade);
                            $('#state').val(data.uf);
                        }
                    });
                }
            });
        });
    </script>
    @endpush
</x-guest-layout>