<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Redefinir Senha do Administrador</h2>
        <p>Digite o e-mail do administrador e a nova senha para redefinir o acesso.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.reset-password.post') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('E-mail do Administrador')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- New Password -->
        <div class="mt-4">
            <x-input-label for="new_password" :value="__('Nova Senha')" />
            <div class="relative">
                <x-text-input id="new_password" class="block mt-1 w-full pr-10" type="password" name="new_password" required />
                <button type="button" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                        onclick="togglePassword('new_password')">
                    <i id="new_password-icon" class="fas fa-eye"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('new_password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="new_password_confirmation" :value="__('Confirmar Nova Senha')" />
            <div class="relative">
                <x-text-input id="new_password_confirmation" class="block mt-1 w-full pr-10" type="password" name="new_password_confirmation" required />
                <button type="button" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                        onclick="togglePassword('new_password_confirmation')">
                    <i id="new_password_confirmation-icon" class="fas fa-eye"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('new_password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900 mr-4">
                Voltar para Login
            </a>
            <x-primary-button>
                {{ __('Redefinir Senha') }}
            </x-primary-button>
        </div>
    </form>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const passwordIcon = document.getElementById(fieldId + '-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }
    </script>
</x-guest-layout>
