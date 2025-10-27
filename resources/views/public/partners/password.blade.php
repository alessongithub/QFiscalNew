<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar Senha - Parceiro QFiscal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="bg-white shadow rounded p-6 w-full max-w-md">
            <div class="mb-4 text-center">
                <img src="{{ asset('logo/logo_transp.png') }}" class="h-10 w-auto mx-auto" alt="QFiscal">
                <h1 class="text-xl font-semibold text-gray-800 mt-2">Alterar Senha</h1>
            </div>
            @if(session('success'))
                <div class="mb-4 p-3 bg-green-50 text-green-800 rounded">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 text-red-800 rounded">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('partner.password.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs text-gray-600">Senha atual</label>
                    <input type="password" name="current_password" class="w-full border rounded p-2" required>
                </div>
                <div>
                    <label class="block text-xs text-gray-600">Nova senha</label>
                    <input type="password" name="password" class="w-full border rounded p-2" required>
                </div>
                <div>
                    <label class="block text-xs text-gray-600">Confirmar nova senha</label>
                    <input type="password" name="password_confirmation" class="w-full border rounded p-2" required>
                </div>
                <div class="pt-2">
                    <button class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>


