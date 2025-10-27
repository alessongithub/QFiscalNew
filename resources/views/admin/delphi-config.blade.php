<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Configuração do Emissor Delphi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Header -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Configuração do Emissor Delphi</h1>
                                <p class="text-gray-600 mt-2">Configure a conexão com o emissor de notas fiscais</p>
                            </div>
                            <div class="flex items-center space-x-4">
                                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                    </svg>
                                    Voltar
                                </a>
                            </div>
                        </div>
                    </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <!-- Debug: Valores Atuais -->
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-blue-800 mb-2">🔍 Valores Atuais Salvos:</h3>
            <div class="text-xs text-blue-700 space-y-1">
                <div><strong>URL:</strong> {{ \App\Models\Setting::getGlobal('services.delphi.url', 'Não configurado') }}</div>
                <div><strong>Timeout:</strong> {{ \App\Models\Setting::getGlobal('services.delphi.timeout', 'Não configurado') }}</div>
                <div><strong>Token:</strong> {{ \App\Models\Setting::getGlobal('services.delphi.token', 'Não configurado') ? '✅ Configurado (' . Str::limit(\App\Models\Setting::getGlobal('services.delphi.token', ''), 20) . '...)' : '❌ Não configurado' }}</div>
                <div><strong>Ambiente:</strong> {{ \App\Models\Setting::getGlobal('services.delphi.environment', 'Não configurado') }}</div>
            </div>
        </div>

        <!-- Status do Emissor -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Status da Conexão
                </h2>
            </div>
            <div class="p-6">
                <div id="emitter-status" class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600">Verificando conexão...</span>
                </div>
            </div>
        </div>

        <!-- Formulário de Configuração -->
        <form method="POST" action="{{ route('admin.delphi-config.update') }}" class="space-y-8">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200" style="background: linear-gradient(90deg, #2563EB15, #1D4ED815);">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 1.657-1.343 3-3 3S6 12.657 6 11s1.343-3 3-3 3 1.343 3 3z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11V7m0 4l3 3m-3-3l-3 3"/>
                        </svg>
                        Configurações de Conexão
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">URL do Emissor</label>
                            <input type="url" name="url" value="{{ old('url', \App\Models\Setting::getGlobal('services.delphi.url', 'http://127.0.0.1:18080')) }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('url') border-red-500 @enderror"
                                   placeholder="http://127.0.0.1:18080" required>
                            @error('url')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">URL completa do servidor onde está rodando o emissor Delphi</p>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Timeout (segundos)</label>
                            <input type="number" min="5" max="300" name="timeout" value="{{ old('timeout', \App\Models\Setting::getGlobal('services.delphi.timeout', '60')) }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('timeout') border-red-500 @enderror"
                                   required>
                            @error('timeout')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">Tempo limite para requisições (5-300 segundos)</p>
                        </div>
                        
                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Token de Autenticação</label>
                            <div class="relative">
                                @php $currentToken = \App\Models\Setting::getGlobal('services.delphi.token', ''); @endphp
                                <input type="password" name="token" value="{{ old('token', $currentToken) }}" 
                                       class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('token') border-red-500 @enderror"
                                       placeholder="{{ $currentToken ? 'Token configurado (clique no olho para ver)' : 'Token opcional para autenticação' }}">
                                <button type="button" onclick="togglePasswordVisibility('token')" 
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <svg id="token-eye-open" class="w-5 h-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg id="token-eye-closed" class="w-5 h-5 text-gray-400 hover:text-gray-600 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                                    </svg>
                                </button>
                            </div>
                            @error('token')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">
                                @if($currentToken)
                                    ✅ Token configurado: {{ Str::limit($currentToken, 20) }}...
                                @else
                                    Será enviado no header Authorization: Bearer {token} em todas as requisições
                                @endif
                            </p>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Ambiente</label>
                            <div class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-600">
                                Configurado pelo tenant em Configurações › Emissor de NF-e
                            </div>
                            <p class="text-xs text-gray-500 mt-1">O Admin define URL/Token/Timeout. O ambiente (Homologação/Produção) é escolhido pelo próprio tenant.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="flex justify-end space-x-4">
                <a href="{{ route('admin.dashboard') }}" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    Salvar Configurações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePasswordVisibility(fieldName) {
    const field = document.querySelector(`input[name="${fieldName}"]`);
    const eyeOpen = document.getElementById(`${fieldName}-eye-open`);
    const eyeClosed = document.getElementById(`${fieldName}-eye-closed`);
    
    if (field.type === 'password') {
        field.type = 'text';
        eyeOpen.classList.add('hidden');
        eyeClosed.classList.remove('hidden');
    } else {
        field.type = 'password';
        eyeOpen.classList.remove('hidden');
        eyeClosed.classList.add('hidden');
    }
}

// Verificar status do emissor
document.addEventListener('DOMContentLoaded', function() {
    fetch('{{ route("admin.emitter-healthcheck-json") }}')
        .then(response => response.json())
        .then(data => {
            const statusDiv = document.getElementById('emitter-status');
            if (data.success) {
                statusDiv.innerHTML = `
                    <div class="flex items-center text-green-600">
                        <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium">Emissor ONLINE</span>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        Conectado em: ${data.url || 'URL não informada'}
                    </div>
                `;
            } else {
                statusDiv.innerHTML = `
                    <div class="flex items-center text-red-600">
                        <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium">Emissor OFFLINE</span>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        ${data.message || 'Não foi possível conectar ao emissor'}
                    </div>
                `;
            }
        })
        .catch(error => {
            const statusDiv = document.getElementById('emitter-status');
            statusDiv.innerHTML = `
                <div class="flex items-center text-red-600">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-medium">Erro na verificação</span>
                </div>
                <div class="mt-2 text-sm text-gray-600">
                    Erro ao verificar status do emissor
                </div>
            `;
        });
});
</script>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
