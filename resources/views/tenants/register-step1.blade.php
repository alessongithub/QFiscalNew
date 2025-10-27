<x-guest-layout>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gray-100">
        <div class="text-center mb-8">
            <img src="{{ asset('logo/logo.png') }}" alt="QFiscal" class="w-48 h-auto mx-auto mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Cadastre sua Empresa</h1>
        </div>

        <div class="w-full sm:max-w-md px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <!-- Progresso -->
            <div class="mb-6">
                <div class="flex items-center mb-2">
                    <div class="flex items-center text-blue-600">
                        <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-medium">1</div>
                        <span class="ml-2 text-sm font-medium">Dados Básicos</span>
                    </div>
                    <div class="flex-1 h-px bg-gray-300 mx-4"></div>
                    <div class="flex items-center text-gray-400">
                        <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-600 rounded-full text-sm font-medium">2</div>
                        <span class="ml-2 text-sm font-medium">Dados da Empresa</span>
                    </div>
                    <div class="flex-1 h-px bg-gray-300 mx-4"></div>
                    <div class="flex items-center text-gray-400">
                        <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-600 rounded-full text-sm font-medium">3</div>
                        <span class="ml-2 text-sm font-medium">Pagamento</span>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('tenant.register.step1') }}">
                @csrf
                
                <!-- Campo hidden para o plano selecionado -->
                <input type="hidden" name="plano_selecionado" value="{{ $planoSelecionado ?? 'gratuito' }}">

                <!-- Nome -->
                <div>
                    <x-input-label for="admin_name" value="Nome Completo *" />
                    <x-text-input id="admin_name" class="block mt-1 w-full" type="text" name="admin_name" :value="old('admin_name')" required autofocus />
                    <x-input-error :messages="$errors->get('admin_name')" class="mt-2" />
                </div>

                <!-- Email -->
                <div class="mt-4">
                    <x-input-label for="admin_email" value="Email *" />
                    <x-text-input id="admin_email" class="block mt-1 w-full" type="email" name="admin_email" :value="old('admin_email')" required />
                    <x-input-error :messages="$errors->get('admin_email')" class="mt-2" />
                </div>

                <!-- Senha -->
                <div class="mt-4">
                    <x-input-label for="admin_password" value="Senha *" />
                    <div class="relative">
                        <x-text-input id="admin_password" class="block mt-1 w-full pr-10" type="password" name="admin_password" required />
                        <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 mt-1" onclick="togglePasswordVisibility('admin_password')">
                            <svg id="admin_password_icon_show" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg id="admin_password_icon_hide" class="h-5 w-5 text-gray-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('admin_password')" class="mt-2" />
                    <p class="text-xs text-gray-500 mt-1">Mínimo 8 caracteres</p>
                </div>

                <!-- Confirmar Senha -->
                <div class="mt-4">
                    <x-input-label for="admin_password_confirmation" value="Confirmar Senha *" />
                    <div class="relative">
                        <x-text-input id="admin_password_confirmation" class="block mt-1 w-full pr-10" type="password" name="admin_password_confirmation" required />
                        <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 mt-1" onclick="togglePasswordVisibility('admin_password_confirmation')">
                            <svg id="admin_password_confirmation_icon_show" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg id="admin_password_confirmation_icon_hide" class="h-5 w-5 text-gray-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('admin_password_confirmation')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between mt-6">
                    <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                        Já tem uma conta?
                    </a>

                    <x-primary-button class="ml-4">
                        Próximo Passo →
                    </x-primary-button>
                </div>

                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500">
                        <strong>Plano Gratuito Inclui:</strong><br>
                        • 1 usuário<br>
                        • Até 50 clientes<br>
                        • Gestão básica de clientes<br>
                        • Suporte por email
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Recuperar plano do localStorage
            const planoSelecionado = localStorage.getItem('planoSelecionado');
            if (planoSelecionado) {
                document.querySelector('input[name="plano_selecionado"]').value = planoSelecionado;
                console.log('Plano recuperado do localStorage:', planoSelecionado);
            }
            
            window.togglePasswordVisibility = function(fieldId) {
                const field = document.getElementById(fieldId);
                const showIcon = document.getElementById(fieldId + '_icon_show');
                const hideIcon = document.getElementById(fieldId + '_icon_hide');

                if (field.type === 'password') {
                    field.type = 'text';
                    showIcon.classList.add('hidden');
                    hideIcon.classList.remove('hidden');
                } else {
                    field.type = 'password';
                    showIcon.classList.remove('hidden');
                    hideIcon.classList.add('hidden');
                }
            };
        });
    </script>
</x-guest-layout>