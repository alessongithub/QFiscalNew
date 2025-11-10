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
                            <label class="block text-xs mb-1 mt-3">PIX Sandbox - E-mail do pagador (user de teste)</label>
                            <input type="email" name="pix_sandbox_email" value="{{ old('pix_sandbox_email', $pixSandboxEmail ?? '') }}" class="w-full border rounded px-3 py-2" placeholder="test_user_xxxxx@testuser.com">
                            <p class="text-[11px] text-gray-500 mt-1">Obrigatório para gerar QR Code PIX no sandbox.</p>
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

                <!-- Padrões Globais de Boleto (informativos para tenants) -->
                <div class="border rounded p-4 space-y-4">
                    <h3 class="text-sm font-medium text-gray-800">Padrões de Boleto (Globais)</h3>
                    <p class="text-xs text-gray-600">Defina aqui os valores padrão de multa e juros para exibição aos tenants. A configuração real de multa/juros deve ser feita no painel do Mercado Pago.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs mb-1">Multa padrão (%)</label>
                            <input type="number" step="0.01" min="0" max="2" name="global_boleto_fine_percent" value="{{ old('global_boleto_fine_percent', $globalFine ?? '0') }}" class="w-full border rounded px-3 py-2">
                            <p class="text-[11px] text-gray-500 mt-1">Máx. 2% (lei). Informativo.</p>
                        </div>
                        <div>
                            <label class="block text-xs mb-1">Juros mensais (%)</label>
                            <input type="number" step="0.01" min="0" max="1" name="global_boleto_interest_month_percent" value="{{ old('global_boleto_interest_month_percent', $globalInterest ?? '0') }}" class="w-full border rounded px-3 py-2">
                            <p class="text-[11px] text-gray-500 mt-1">Máx. 1%/mês. Informativo.</p>
                        </div>
                        <div>
                            <label class="block text-xs mb-1">Taxa fixa do boleto (R$) — Mercado Pago</label>
                            <input type="number" step="0.01" min="0" max="50" name="global_boleto_mp_fee_fixed" value="{{ old('global_boleto_mp_fee_fixed', $globalBoletoMpFeeFixed ?? '1.99') }}" class="w-full border rounded px-3 py-2">
                            <p class="text-[11px] text-gray-500 mt-1">Usada no cálculo do saldo quando o MP não informar as fees.</p>
                        </div>
                        <div>
                            <label class="block text-xs mb-1">Taxa PIX (%) — Mercado Pago</label>
                            <input type="number" step="0.01" min="0" max="10" name="pix_mp_fee_percent" value="{{ old('pix_mp_fee_percent', $pixMpFeePercent ?? '0.99') }}" class="w-full border rounded px-3 py-2">
                            <p class="text-[11px] text-gray-500 mt-1">Taxa percentual que o Mercado Pago cobra sobre pagamentos PIX (padrão: 0,99%).</p>
                        </div>
                    </div>
                </div>

                <!-- Celcoin / Galax Pay -->
                <div class="border rounded p-4 space-y-4">
                    <h3 class="text-sm font-medium text-gray-800">Celcoin / Galax Pay (Assinaturas)</h3>
                    <p class="text-xs text-gray-600">Preencha conforme os campos exibidos no painel da Celcoin.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs mb-1">Galax ID</label>
                            <input type="text" name="celcoin_galax_id" value="{{ old('celcoin_galax_id', $config->celcoin_galax_id ?? '') }}" class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-xs mb-1">Galax HASH</label>
                            <input type="text" name="celcoin_galax_hash" value="{{ old('celcoin_galax_hash', $config->celcoin_galax_hash ?? '') }}" class="w-full border rounded px-3 py-2">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs mb-1">Token de segurança do Webhook</label>
                            <input type="text" name="celcoin_webhook_secret" value="{{ old('celcoin_webhook_secret', $config->celcoin_webhook_secret ?? '') }}" class="w-full border rounded px-3 py-2">
                            <p class="text-[11px] text-gray-500 mt-1">Cole exatamente o token exibido no painel.</p>
                        </div>
                        <div>
                            <label class="block text-xs mb-1">URL do Webhook (Celcoin)</label>
                            <input type="text" readonly value="{{ route('webhooks.celcoin') }}" class="w-full border rounded px-3 py-2 bg-gray-50 text-gray-700">
                            <p class="text-[11px] text-gray-500 mt-1">Configure esta URL no painel da Celcoin.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs mb-1">API Version</label>
                            <select name="celcoin_api_version" class="w-full border rounded px-3 py-2">
                                <option value="" {{ empty($config->celcoin_api_version) ? 'selected' : '' }}>Padrão</option>
                                <option value="v1" {{ ($config->celcoin_api_version ?? '') === 'v1' ? 'selected' : '' }}>API V1</option>
                                <option value="v2" {{ ($config->celcoin_api_version ?? '') === 'v2' ? 'selected' : '' }}>API V2</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs mb-1">Public Token (Tokenização via JS)</label>
                            <input type="text" name="celcoin_public_token" value="{{ old('celcoin_public_token', $config->celcoin_public_token ?? '') }}" class="w-full border rounded px-3 py-2">
                        </div>
                    </div>
                </div>

                <div>
                    <button class="px-4 py-2 bg-green-600 text-white rounded">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>


