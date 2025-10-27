<x-guest-layout>
    <form method="POST" action="{{ route('tenant.register') }}" class="space-y-6">
        @csrf

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Dados da Empresa</h2>
            
            <!-- Nome -->
            <div class="mt-4">
                <x-input-label for="name" value="Razão Social" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Nome Fantasia -->
            <div class="mt-4">
                <x-input-label for="fantasy_name" value="Nome Fantasia" />
                <x-text-input id="fantasy_name" class="block mt-1 w-full" type="text" name="fantasy_name" :value="old('fantasy_name')" autocomplete="fantasy_name" />
                <x-input-error :messages="$errors->get('fantasy_name')" class="mt-2" />
            </div>

            <!-- Email -->
            <div class="mt-4">
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="email" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- CNPJ -->
            <div class="mt-4">
                <x-input-label for="cnpj" value="CNPJ" />
                <x-text-input id="cnpj" class="block mt-1 w-full" type="text" name="cnpj" :value="old('cnpj')" required autocomplete="cnpj" />
                <x-input-error :messages="$errors->get('cnpj')" class="mt-2" />
            </div>

            <!-- Telefone -->
            <div class="mt-4">
                <x-input-label for="phone" value="Telefone" />
                <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" required autocomplete="tel" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            <!-- Endereço -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="zip_code" value="CEP" />
                    <x-text-input id="zip_code" class="block mt-1 w-full" type="text" name="zip_code" :value="old('zip_code')" required />
                    <x-input-error :messages="$errors->get('zip_code')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="address" value="Endereço" />
                    <x-text-input id="address" class="block mt-1 w-full" type="text" name="address" :value="old('address')" required />
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="number" value="Número" />
                    <x-text-input id="number" class="block mt-1 w-full" type="text" name="number" :value="old('number')" required />
                    <x-input-error :messages="$errors->get('number')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="complement" value="Complemento" />
                    <x-text-input id="complement" class="block mt-1 w-full" type="text" name="complement" :value="old('complement')" />
                    <x-input-error :messages="$errors->get('complement')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="neighborhood" value="Bairro" />
                    <x-text-input id="neighborhood" class="block mt-1 w-full" type="text" name="neighborhood" :value="old('neighborhood')" required />
                    <x-input-error :messages="$errors->get('neighborhood')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="city" value="Cidade" />
                    <x-text-input id="city" class="block mt-1 w-full" type="text" name="city" :value="old('city')" required />
                    <x-input-error :messages="$errors->get('city')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="state" value="Estado" />
                    <select id="state" name="state" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                        <option value="">Selecione...</option>
                        @foreach(['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'] as $uf)
                            <option value="{{ $uf }}" {{ old('state') == $uf ? 'selected' : '' }}>{{ $uf }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('state')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
            <h2 class="text-lg font-semibold mb-4">Dados do Administrador</h2>

            <!-- Admin Name -->
            <div>
                <x-input-label for="admin_name" value="Nome" />
                <x-text-input id="admin_name" class="block mt-1 w-full" type="text" name="admin_name" :value="old('admin_name')" required />
                <x-input-error :messages="$errors->get('admin_name')" class="mt-2" />
            </div>

            <!-- Admin Email -->
            <div class="mt-4">
                <x-input-label for="admin_email" value="Email" />
                <x-text-input id="admin_email" class="block mt-1 w-full" type="email" name="admin_email" :value="old('admin_email')" required />
                <x-input-error :messages="$errors->get('admin_email')" class="mt-2" />
            </div>

            <!-- Admin Password -->
            <div class="mt-4">
                <x-input-label for="admin_password" value="Senha" />
                <x-text-input id="admin_password" class="block mt-1 w-full" type="password" name="admin_password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('admin_password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                <x-input-label for="admin_password_confirmation" value="Confirmar Senha" />
                <x-text-input id="admin_password_confirmation" class="block mt-1 w-full" type="password" name="admin_password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('admin_password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                Já tem uma conta?
            </a>

            <x-primary-button class="ml-4">
                Cadastrar Empresa
            </x-primary-button>
        </div>
    </form>

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#cnpj').mask('00.000.000/0000-00');
            $('#phone').mask('(00) 00000-0000');
            $('#zip_code').mask('00000-000');
        });
    </script>
    @endpush
</x-guest-layout>