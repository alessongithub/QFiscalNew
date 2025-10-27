<x-guest-layout>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gray-100">
        <div class="text-center mb-8">
            <img src="{{ asset('logo/logo.png') }}" alt="QFiscal" class="w-48 h-auto mx-auto mb-6">
            <div class="bg-white p-8 rounded-lg shadow-md max-w-md">
                <div class="text-green-600 mb-4">
                    <svg class="h-16 w-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-4">Cadastro Concluído!</h1>
                <p class="text-gray-600 mb-6">Sua empresa foi cadastrada com sucesso no QFiscal.</p>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h3 class="text-lg font-semibold text-blue-800 mb-2">📧 Email de Ativação</h3>
                    <p class="text-blue-700 text-sm">
                        Enviamos um email com o link de ativação para sua conta. 
                        Verifique sua caixa de entrada e spam.
                    </p>
                </div>
                
                <div class="space-y-4">
                    <a href="{{ route('login') }}" class="inline-block w-full bg-blue-600 text-white font-semibold px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        Fazer Login →
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>