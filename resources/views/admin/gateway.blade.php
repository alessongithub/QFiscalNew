<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gateway de Pagamento</h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            @if(session('success'))
                <div class="mb-4 p-3 rounded bg-green-50 text-green-800">{{ session('success') }}</div>
            @endif

            <form action="{{ route('admin.gateway.update') }}" method="POST" class="space-y-6 max-w-3xl">
                @csrf
                @method('PUT')

                <div class="border rounded p-4 space-y-4">
                    <div>
                        <label class="block text-sm mb-1">Modo</label>
                        <select name="mode" class="w-full border rounded px-3 py-2">
                            <option value="sandbox" {{ $config->mode === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                            <option value="production" {{ $config->mode === 'production' ? 'selected' : '' }}>Produção</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h3 class="text-sm font-medium mb-2">Sandbox</h3>
                            <label class="block text-xs mb-1">Public Key</label>
                            <input type="text" name="public_key_sandbox" value="{{ old('public_key_sandbox', $config->public_key_sandbox) }}" class="w-full border rounded px-3 py-2">
                            <label class="block text-xs mb-1 mt-3">Access Token</label>
                            <input type="text" name="access_token_sandbox" value="{{ old('access_token_sandbox', $config->access_token_sandbox) }}" class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <h3 class="text-sm font-medium mb-2">Produção</h3>
                            <label class="block text-xs mb-1">Public Key</label>
                            <input type="text" name="public_key_production" value="{{ old('public_key_production', $config->public_key_production) }}" class="w-full border rounded px-3 py-2">
                            <label class="block text-xs mb-1 mt-3">Access Token</label>
                            <input type="text" name="access_token_production" value="{{ old('access_token_production', $config->access_token_production) }}" class="w-full border rounded px-3 py-2">
                            <label class="block text-xs mb-1 mt-3">Client ID</label>
                            <input type="text" name="client_id_production" value="{{ old('client_id_production', $config->client_id_production) }}" class="w-full border rounded px-3 py-2">
                            <label class="block text-xs mb-1 mt-3">Client Secret</label>
                            <input type="text" name="client_secret_production" value="{{ old('client_secret_production', $config->client_secret_production) }}" class="w-full border rounded px-3 py-2">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs mb-1">Assinatura Secreta (Webhooks)</label>
                        <input type="text" name="webhook_secret" value="{{ old('webhook_secret', $config->webhook_secret) }}" class="w-full border rounded px-3 py-2">
                        <p class="text-xs text-gray-500 mt-1">Configure no painel do Mercado Pago para validar as notificações.</p>
                    </div>

                    <div>
                        <label class="block text-xs mb-1">Dias para bloquear login após vencimento</label>
                        <input type="number" min="0" max="30" name="block_login_after_days" value="{{ old('block_login_after_days', $config->block_login_after_days ?? 3) }}" class="w-full border rounded px-3 py-2">
                        <p class="text-xs text-gray-500 mt-1">Padrão: 3 dias.</p>
                    </div>
                </div>

                <div>
                    <button class="px-4 py-2 bg-green-600 text-white rounded">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>


