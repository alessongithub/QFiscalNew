<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Parceiro - QFiscal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="bg-white shadow rounded p-6 w-full max-w-md">
            <div class="mb-4 text-center">
                <img src="{{ asset('logo/logo_transp.png') }}" class="h-10 w-auto mx-auto" alt="QFiscal">
                <h1 class="text-xl font-semibold text-gray-800 mt-2">Login Parceiro</h1>
            </div>
            @if(session('success'))
                <div class="mb-4 p-3 bg-green-50 text-green-800 rounded">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 text-red-800 rounded">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('partner.login.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs text-gray-600">E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full border rounded p-2" required>
                </div>
                <div>
                    <label class="block text-xs text-gray-600">Senha</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" class="w-full border rounded p-2 pr-10" required>
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                            <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg id="eye-off-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <script>
                function togglePassword() {
                    const passwordInput = document.getElementById('password');
                    const eyeIcon = document.getElementById('eye-icon');
                    const eyeOffIcon = document.getElementById('eye-off-icon');
                    
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        eyeIcon.classList.add('hidden');
                        eyeOffIcon.classList.remove('hidden');
                    } else {
                        passwordInput.type = 'password';
                        eyeIcon.classList.remove('hidden');
                        eyeOffIcon.classList.add('hidden');
                    }
                }
                </script>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember" class="text-sm text-gray-600">Lembrar-me</label>
                </div>
                <div class="pt-2">
                    <button class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded">Entrar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>


