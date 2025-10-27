<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Configurações SMTP') }}
        </h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            <form method="POST" action="{{ route('admin.smtp-settings.update') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Host -->
                    <div>
                        <x-input-label for="host" :value="__('Host SMTP')" />
                        <x-text-input id="host" name="host" type="text" class="mt-1 block w-full" :value="old('host', $smtpConfig->host)" required />
                        <x-input-error :messages="$errors->get('host')" class="mt-2" />
                    </div>

                    <!-- Porta -->
                    <div>
                        <x-input-label for="port" :value="__('Porta')" />
                        <x-text-input id="port" name="port" type="number" class="mt-1 block w-full" :value="old('port', $smtpConfig->port)" required />
                        <x-input-error :messages="$errors->get('port')" class="mt-2" />
                    </div>

                    <!-- Username -->
                    <div>
                        <x-input-label for="username" :value="__('Usuário')" />
                        <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $smtpConfig->username)" required />
                        <x-input-error :messages="$errors->get('username')" class="mt-2" />
                    </div>

                    <!-- Password -->
                    <div>
                        <x-input-label for="password" :value="__('Senha')" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :value="old('password', $smtpConfig->password)" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <!-- Encryption -->
                    <div>
                        <x-input-label for="encryption" :value="__('Criptografia')" />
                        <select id="encryption" name="encryption" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="tls" {{ old('encryption', $smtpConfig->encryption) === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ old('encryption', $smtpConfig->encryption) === 'ssl' ? 'selected' : '' }}>SSL</option>
                        </select>
                        <x-input-error :messages="$errors->get('encryption')" class="mt-2" />
                    </div>

                    <!-- From Address -->
                    <div>
                        <x-input-label for="from_address" :value="__('E-mail de Envio')" />
                        <x-text-input id="from_address" name="from_address" type="email" class="mt-1 block w-full" :value="old('from_address', $smtpConfig->from_address)" required />
                        <x-input-error :messages="$errors->get('from_address')" class="mt-2" />
                    </div>

                    <!-- From Name -->
                    <div class="col-span-2">
                        <x-input-label for="from_name" :value="__('Nome de Exibição')" />
                        <x-text-input id="from_name" name="from_name" type="text" class="mt-1 block w-full" :value="old('from_name', $smtpConfig->from_name)" required />
                        <x-input-error :messages="$errors->get('from_name')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6">
                    <x-primary-button>
                        {{ __('Salvar Configurações') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
