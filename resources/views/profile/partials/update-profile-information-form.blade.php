<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Informações do Perfil</h2>
        <p class="mt-1 text-sm text-gray-600">Atualize os dados da sua conta. Para alterar Nome ou CNPJ/CPF, entre em contato com o suporte.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nome" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full bg-gray-100" :value="old('name', $user->name)" disabled autocomplete="name" />
        </div>

        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        Seu e-mail não foi verificado.

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Clique aqui para reenviar o e-mail de verificação.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            Um novo link de verificação foi enviado para o seu e-mail.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        @php $tenant = auth()->user()->tenant; @endphp
        @if($tenant)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="tenant_name" value="Razão Social" />
                <x-text-input id="tenant_name" type="text" class="mt-1 block w-full bg-gray-100" value="{{ $tenant->name }}" disabled />
            </div>
            <div>
                <x-input-label for="tenant_cnpj" value="CNPJ/CPF" />
                <x-text-input id="tenant_cnpj" type="text" class="mt-1 block w-full bg-gray-100" value="{{ $tenant->cnpj }}" disabled />
            </div>
            <div>
                <x-input-label for="phone" value="Telefone" />
                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $tenant->phone)" />
            </div>
            <div>
                <x-input-label for="zip_code" value="CEP" />
                <x-text-input id="zip_code" name="zip_code" type="text" class="mt-1 block w-full" :value="old('zip_code', $tenant->zip_code)" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="address" value="Endereço" />
                <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $tenant->address)" />
            </div>
            <div>
                <x-input-label for="number" value="Número" />
                <x-text-input id="number" name="number" type="text" class="mt-1 block w-full" :value="old('number', $tenant->number)" />
            </div>
            <div>
                <x-input-label for="complement" value="Complemento" />
                <x-text-input id="complement" name="complement" type="text" class="mt-1 block w-full" :value="old('complement', $tenant->complement)" />
            </div>
            <div>
                <x-input-label for="neighborhood" value="Bairro" />
                <x-text-input id="neighborhood" name="neighborhood" type="text" class="mt-1 block w-full" :value="old('neighborhood', $tenant->neighborhood)" />
            </div>
            <div>
                <x-input-label for="city" value="Cidade" />
                <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $tenant->city)" />
            </div>
            <div>
                <x-input-label for="state" value="UF" />
                <x-text-input id="state" name="state" type="text" class="mt-1 block w-full" :value="old('state', $tenant->state)" />
            </div>
        </div>
        <div class="mt-6">
            <div class="mb-2 text-sm text-gray-700 font-medium">Logo da empresa</div>
            <div class="flex items-center gap-4">
                @if($tenant->logo_path)
                    <img src="{{ $tenant->logo_url }}" alt="Logo" class="h-16 w-auto rounded border" />
                @else
                    <div class="text-sm text-gray-500">Nenhuma logo enviada</div>
                @endif
            </div>
            <div class="mt-3 text-sm text-gray-600">Envie um arquivo JPG ou PNG, até 1 MB, tamanho recomendado 400x200 px (proporção 2:1).</div>
            <div class="mt-2">
                <input type="file" name="tenant_logo" accept="image/png,image/jpeg" class="border rounded p-2">
                <x-input-error class="mt-2" :messages="$errors->get('tenant_logo')" />
            </div>
        </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>Salvar</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >Salvo.</p>
            @endif
        </div>
    </form>
</section>
