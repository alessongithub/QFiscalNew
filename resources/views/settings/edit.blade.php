<x-app-layout>
    @php
        $brandPrimary = isset($partner) && !empty($partner->primary_color) ? $partner->primary_color : '#059669';
        $brandSecondary = isset($partner) && !empty($partner->secondary_color) ? $partner->secondary_color : '#047857';
    @endphp
    
    <!-- Header moderno com gradiente -->
    <div class="text-white rounded-lg shadow-lg p-6 mb-8" style="background: linear-gradient(135deg, {{ $brandPrimary }}, {{ $brandSecondary }});">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">Configurações do Sistema</h1>
                <p class="text-green-100 opacity-90">Personalize e configure seu sistema de acordo com suas necessidades</p>
            </div>
            <div class="hidden md:block">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto">
        @if(session('success'))
            <script>
                (function(){
                    var msg = @json(session('success'));
                    var fire = function(){
                        if (window.showNotification && typeof window.showNotification === 'function') {
                            window.showNotification(msg, 'success');
                        } else {
                            // fallback mínimo
                            var n = document.createElement('div');
                            n.className = 'fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white font-medium bg-green-500';
                            n.textContent = msg;
                            document.body.appendChild(n);
                            setTimeout(function(){ n.remove(); }, 3000);
                        }
                    };
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', fire);
                    } else { fire(); }
                })();
            </script>
        @endif
        @if($errors->any())
            <script>
                (function(){
                    var msg = @json($errors->first());
                    var fire = function(){
                        if (window.showNotification && typeof window.showNotification === 'function') {
                            window.showNotification(msg, 'error');
                        } else {
                            var n = document.createElement('div');
                            n.className = 'fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white font-medium bg-red-500';
                            n.textContent = msg;
                            document.body.appendChild(n);
                            setTimeout(function(){ n.remove(); }, 3000);
                        }
                    };
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', fire);
                    } else { fire(); }
                })();
            </script>
        @endif

        <form method="POST" action="{{ ($showFiscal ?? false) ? route('settings.fiscal.update') : route('settings.update') }}" enctype="multipart/form-data" class="space-y-8">
            @csrf @method('PUT')
            
            @if((($showGeneral ?? true)) && method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('settings.edit'))
            
            <!-- Configurações Gerais -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200" style="background: linear-gradient(90deg, {{ $brandPrimary }}15, {{ $brandSecondary }}15);">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <svg class="w-6 h-6 mr-3" style="color: {{ $brandPrimary }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Configurações Gerais
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Tema do Sistema</label>
                            <select name="ui[theme]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent transition-all" style="focus:ring-color: {{ $brandPrimary }}">
                                <option value="light" @selected(($values['ui.theme']??'light')==='light')>🌞 Claro</option>
                                <option value="dark" @selected(($values['ui.theme']??'light')==='dark')>🌙 Escuro</option>
                            </select>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Layout de Impressão</label>
                            <select name="print[default]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent transition-all" style="focus:ring-color: {{ $brandPrimary }}">
                                <option value="a4" @selected(($values['print.default']??'a4')==='a4')>📄 A4</option>
                                <option value="80mm" @selected(($values['print.default']??'a4')==='80mm')>🧾 80mm</option>
                </select>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Rodapé do Comprovante</label>
                        <input type="text" name="print[footer]" value="{{ $values['print.footer'] ?? '' }}" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent transition-all" 
                               style="focus:ring-color: {{ $brandPrimary }}" maxlength="200" 
                               placeholder="Digite o texto que aparecerá no rodapé dos comprovantes">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Tipo de Impressora Térmica</label>
                            <select name="print[printer_type]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent transition-all" style="focus:ring-color: {{ $brandPrimary }}">
                                <option value="thermal_58" @selected(($values['print.printer_type']??'thermal_80')==='thermal_58')>58 mm</option>
                                <option value="thermal_80" @selected(($values['print.printer_type']??'thermal_80')==='thermal_80')>80 mm</option>
                                <option value="system" @selected(($values['print.printer_type']??'thermal_80')==='system')>Impressora do Sistema</option>
                            </select>
                            <p class="text-xs text-gray-500">Use "Impressora do Sistema" para usar a configuração do navegador.</p>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Colunas do Cupom</label>
                            <input type="number" min="16" max="64" name="print[ticket_columns]" value="{{ $values['print.ticket_columns'] ?? '42' }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent transition-all"
                                   style="focus:ring-color: {{ $brandPrimary }}" placeholder="Ex.: 32, 42, 48">
                            <p class="text-xs text-gray-500">Ajuste conforme a impressora (58mm≈32, 80mm≈42/48).</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configurações NFe -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200" style="background: linear-gradient(90deg, #2563EB15, #10B98115);">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        NF-e (Numeração e Ambiente)
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Série</label>
                            <input type="number" min="1" max="999" name="nfe[series]" value="{{ old('nfe.series', \App\Models\Setting::get('nfe.series','1')) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <p class="text-xs text-gray-500">Série fiscal da NF-e (padrão 1)</p>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Próximo Número</label>
                            @php $serieSet = \App\Models\Setting::get('nfe.series','1'); @endphp
                            <input type="number" min="1" max="999999999" name="nfe[next_number]" value="{{ old('nfe.next_number', \App\Models\Setting::get('nfe.next_number.series.' . $serieSet, '') ) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <p class="text-xs text-gray-500">Define o próximo número a ser emitido para a série atual.</p>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Ambiente</label>
                            @php $env = \App\Models\Setting::get('nfe.environment', \App\Models\Setting::getGlobal('services.delphi.environment', (config('app.env')==='production'?'producao':'homologacao'))); @endphp
                            <select name="nfe[environment]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="homologacao" @selected($env==='homologacao')>Homologação</option>
                                <option value="producao" @selected($env==='producao')>Produção</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configurações PDV -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200" style="background: linear-gradient(90deg, #3B82F615, #1D4ED815);">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        PDV (Ponto de Venda)
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Cliente Obrigatório no PDV</label>
                            <select name="pos[require_client]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="0" @selected(($values['pos.require_client']??'0')==='0')>❌ Não</option>
                                <option value="1" @selected(($values['pos.require_client']??'0')==='1')>✅ Sim</option>
                            </select>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Bloquear Venda sem Estoque</label>
                            <select name="pos[block_without_stock]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="1" @selected(($values['pos.block_without_stock']??'1')==='1')>🔒 Sim</option>
                                <option value="0" @selected(($values['pos.block_without_stock']??'1')==='0')>🔓 Não</option>
                            </select>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Forma de Pagamento À Vista</label>
                            <select name="pos[default_cash_method]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="cash" @selected(($values['pos.default_cash_method']??'cash')==='cash')>💵 Dinheiro</option>
                                <option value="card" @selected(($values['pos.default_cash_method']??'cash')==='card')>💳 Cartão</option>
                                <option value="pix" @selected(($values['pos.default_cash_method']??'cash')==='pix')>📱 PIX</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Imprimir automaticamente após pagamento aprovado</label>
                            <select name="pos[auto_print_on_payment]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="0" @selected(($values['pos.auto_print_on_payment']??'0')==='0')>❌ Não</option>
                                <option value="1" @selected(($values['pos.auto_print_on_payment']??'0')==='1')>✅ Sim</option>
                            </select>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Forma de Pagamento Parcelado</label>
                            <select name="pos[default_installment_method]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="boleto" @selected(($values['pos.default_installment_method']??'boleto')==='boleto')>🧾 Boleto</option>
                                <option value="card" @selected(($values['pos.default_installment_method']??'boleto')==='card')>💳 Cartão</option>
                </select>
            </div>


            <!-- Retenção de Logs -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200" style="background: linear-gradient(90deg, #37415115, #11182715);">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Retenção de Logs de Atividades
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Dias de retenção (aplica para todos os tipos)</label>
                            <input type="number" name="logs_retention_days" min="30" max="1095"
                                   value="{{ $logsRetentionDays ?? 180 }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-transparent transition-all"
                                   placeholder="180">
                            <p class="text-xs text-gray-500">Mínimo 30 dias, máximo 1095 (3 anos). Um job diário faz a limpeza automática.</p>
                        </div>
                    </div>
                </div>
            </div>
                    </div>
                </div>
            </div>

            <!-- Configurações de Estoque -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200" style="background: linear-gradient(90deg, #F59E0B15, #D97B0615);">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        Estoque
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Permitir Estoque Negativo</label>
                        <select name="stock[allow_negative]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all">
                            <option value="0" @selected(($values['stock.allow_negative']??'0')==='0')>❌ Não</option>
                            <option value="1" @selected(($values['stock.allow_negative']??'0')==='1')>✅ Sim</option>
                    </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Baixa de Estoque</label>
                        @php $issueOn = $values['stock.issue_on'] ?? 'order_fulfill'; @endphp
                        <select name="stock[issue_on]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all">
                            <option value="order_create" @selected($issueOn==='order_create')>Ao criar o pedido (saída imediata)</option>
                            <option value="order_fulfill" @selected($issueOn==='order_fulfill')>Ao finalizar o pedido (saída na finalização)</option>
                        </select>
                        <p class="text-xs text-gray-500">Defina quando o sistema dará a saída do estoque dos produtos.</p>
                    </div>
                </div>
            </div>

            <!-- Configurações de OS -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200" style="background: linear-gradient(90deg, #8B5CF615, #7C3AED15);">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Ordens de Serviço
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Garantia Padrão (dias)</label>
                        <div class="relative">
                            <input type="number" name="service_orders[default_warranty_days]" 
                                   value="{{ $values['service_orders.default_warranty_days'] ?? '90' }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" 
                                   min="0" max="3650" placeholder="90">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-400 text-sm">dias</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configurações de Cancelamento de Pedidos -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200" style="background: linear-gradient(90deg, #DC262615, #EF444415);">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancelamento de Pedidos
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Prazo máximo para cancelamento (dias)</label>
                            <div class="relative">
                                <input type="number" name="orders[cancel][max_days]" 
                                       value="{{ $values['orders.cancel.max_days'] ?? '90' }}" 
                                       min="0" max="365"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-400 text-sm">dias</span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">0 = sem limite de prazo</p>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Taxa de antecipação de cartão (%)</label>
                            <div class="relative">
                                <input type="number" step="0.01" min="0" max="100" 
                                       name="orders[cancel][card_anticipation_fee_percent]" 
                                       value="{{ $values['orders.cancel.card_anticipation_fee_percent'] ?? '3.5' }}" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-400 text-sm">%</span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">Taxa cobrada ao estornar pagamentos em cartão antecipado</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configurações de Boleto -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200" style="background: linear-gradient(90deg, #1E40AF15, #1D4ED815);">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Boleto Bancário
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Multa Padrão (%)</label>
                            <div class="relative">
                                <input type="number" step="0.01" min="0" max="2" 
                                       name="boleto[fine_percent]" value="{{ $values['boleto.fine_percent'] ?? '0' }}" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed"
                                       disabled readonly>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-400 text-sm">%</span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">Máximo de 2% por lei. Configurado diretamente no painel do Mercado Pago.</p>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Juros Mensais (%)</label>
                            <div class="relative">
                                <input type="number" step="0.01" min="0" max="1" 
                                       name="boleto[interest_month_percent]" value="{{ $values['boleto.interest_month_percent'] ?? '0' }}" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed"
                                       disabled readonly>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-400 text-sm">%</span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">Máximo 1%/mês (~0,033%/dia). Configurado no Mercado Pago.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configurações WhatsApp -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200" style="background: linear-gradient(90deg, #059E6915, #047E5715);">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-green-600" fill="currentColor" viewBox="0 0 32 32">
                            <path d="M19.11 17.67c-.26-.13-1.53-.75-1.77-.83-.24-.09-.42-.13-.6.13-.18.26-.69.83-.85 1.01-.16.18-.31.2-.57.07-.26-.13-1.09-.4-2.08-1.28-.77-.69-1.29-1.54-1.44-1.8-.15-.26-.02-.4.11-.53.11-.11.26-.29.4-.44.13-.15.18-.26.27-.44.09-.18.04-.33-.02-.46-.07-.13-.6-1.44-.82-1.97-.22-.53-.44-.46-.6-.46-.16 0-.33-.02-.51-.02-.18 0-.46.07-.7.33-.24.26-.92.9-.92 2.19 0 1.29.95 2.54 1.08 2.71.13.18 1.87 3.05 4.53 4.28.63.27 1.12.43 1.5.55.63.2 1.21.17 1.66.1.51-.08 1.53-.62 1.74-1.22.22-.6.22-1.12.15-1.22-.07-.11-.24-.18-.49-.31z"/>
                            <path d="M16 3C8.82 3 3 8.82 3 16c0 2.29.61 4.44 1.67 6.3L3 29l6.86-1.8C11.75 28.4 13.81 29 16 29c7.18 0 13-5.82 13-13S23.18 3 16 3zm0 23.75c-2.17 0-4.18-.66-5.84-1.78l-.42-.27-4.06 1.07 1.09-3.96-.28-.41A10.66 10.66 0 015.25 16c0-5.93 4.82-10.75 10.75-10.75S26.75 10.07 26.75 16 21.93 26.75 16 26.75z"/>
                        </svg>
                        WhatsApp
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="space-y-6">
                        <div class="space-y-3">
                            <label class="block text-sm font-medium text-gray-700">Template de Mensagem - Pedidos</label>
                            <textarea name="whatsapp[order_template]" rows="4" 
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all resize-none"
                                      placeholder="Digite o template da mensagem para pedidos">{{ $values['whatsapp.order_template'] ?? 'Olá {cliente}, seu pedido #{numero} - {titulo} no valor de R$ {total} está {status}. Itens:\n{itens}' }}</textarea>
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full">{cliente}</span>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full">{numero}</span>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full">{titulo}</span>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full">{total}</span>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full">{status}</span>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full">{itens}</span>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <label class="block text-sm font-medium text-gray-700">Template de Mensagem - Orçamentos</label>
                            <textarea name="whatsapp[quote_template]" rows="4" 
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all resize-none"
                                      placeholder="Digite o template da mensagem para orçamentos">{{ $values['whatsapp.quote_template'] ?? 'Olá {cliente}, seu orçamento #{numero} - {titulo} no valor de R$ {total} está {status}. Itens:\n{itens}' }}</textarea>
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full">{cliente}</span>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full">{numero}</span>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full">{titulo}</span>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full">{total}</span>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full">{status}</span>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full">{itens}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if((($showFiscal ?? true)) && method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('tax_config.edit'))
            <!-- Configurações Fiscais -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200" style="background: linear-gradient(90deg, #DC262615, #B9173115);">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Configurações Fiscais
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Regime Tributário</label>
                            <select name="tax[regime_tributario]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all">
                            @php $rt = old('tax.regime_tributario', $taxConfig->regime_tributario ?? 'simples_nacional'); @endphp
                                <option value="simples_nacional" @selected($rt==='simples_nacional')>📋 Simples Nacional</option>
                                <option value="lucro_presumido" @selected($rt==='lucro_presumido')>💼 Lucro Presumido</option>
                                <option value="lucro_real" @selected($rt==='lucro_real')>🏢 Lucro Real</option>
                        </select>
                    </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">CNAE Principal</label>
                            <input type="text" name="tax[cnae_principal]" 
                                   value="{{ old('tax.cnae_principal', $taxConfig->cnae_principal) }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" 
                                   maxlength="20" placeholder="Ex: 4751-2/01">
                    </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Anexo (Simples Nacional)</label>
                        @php $anx = old('tax.anexo_simples', $taxConfig->anexo_simples); @endphp
                            <select name="tax[anexo_simples]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all">
                                <option value="">— Selecione —</option>
                            <option value="I" @selected($anx==='I')>Anexo I</option>
                            <option value="II" @selected($anx==='II')>Anexo II</option>
                            <option value="III" @selected($anx==='III')>Anexo III</option>
                            <option value="IV" @selected($anx==='IV')>Anexo IV</option>
                            <option value="V" @selected($anx==='V')>Anexo V</option>
                        </select>
                    </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Alíquota Simples (0–1)</label>
                            <input type="number" step="0.0001" min="0" max="1" 
                                   name="tax[aliquota_simples_nacional]" 
                                   value="{{ old('tax.aliquota_simples_nacional', $taxConfig->aliquota_simples_nacional) }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all"
                                   placeholder="Ex: 0.045">
                        </div>
                    </div>
                    
                    @if($taxConfig->updatedBy)
                    <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center gap-2 text-sm text-blue-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-medium">Última alteração:</span>
                            <span>{{ $taxConfig->updatedBy->name }}</span>
                            @if($taxConfig->updated_at)
                                <span class="text-blue-600">em {{ $taxConfig->updated_at->format('d/m/Y H:i') }}</span>
                            @endif
                        </div>
                    </div>
                    @endif
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Lei da Transparência (IBPT)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-center space-x-3">
                                <input type="hidden" name="tax[habilitar_ibpt]" value="0">
                                <input type="checkbox" name="tax[habilitar_ibpt]" value="1" 
                                       @checked(old('tax.habilitar_ibpt', $taxConfig->habilitar_ibpt))
                                       class="w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                <label class="text-sm font-medium text-gray-700">Habilitar IBPT</label>
                            </div>
                            <div class="space-y-2">
                                <input type="text" name="tax[codigo_ibpt_padrao]" 
                                       value="{{ old('tax.codigo_ibpt_padrao', $taxConfig->codigo_ibpt_padrao) }}" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" 
                                       placeholder="Código IBPT padrão (opcional)" maxlength="50">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if((($showFiscal ?? true)) && method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('tax_config.edit'))
            <!-- Emissor de NF-e -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200" style="background: linear-gradient(90deg, #10B98115, #05966915);">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Emissor de NF-e
                    </h2>
                </div>
                <div class="p-6 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2 md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700">Ambiente</label>
                            @php $tenantEnv = \App\Models\Setting::get('nfe.environment', 'homologacao'); @endphp
                            <select name="nfe[environment]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="homologacao" @selected($tenantEnv==='homologacao')>Homologação</option>
                                <option value="producao" @selected($tenantEnv==='producao')>Produção</option>
                            </select>
                            <p class="text-xs text-gray-500">Escolha onde suas notas serão emitidas.</p>
                        </div>
           
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                CNPJ <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="emitter-cnpj" name="emitter[cnpj]" 
                                   value="{{ old('emitter.cnpj', $emitter->cnpj ?: ($user->tenant->cnpj ?? '')) }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                                   placeholder="00.000.000/0000-00" maxlength="18" required>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Inscrição Estadual</label>
                            <input type="text" name="emitter[ie]" 
                                   value="{{ old('emitter.ie', $emitter->ie) }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                                   placeholder="000.000.000.000">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Razão Social <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="emitter[razao_social]" 
                                   value="{{ old('emitter.razao_social', $emitter->razao_social ?: ($user->tenant->name ?? '')) }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                                   placeholder="Razão Social da Empresa" required>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Nome Fantasia</label>
                            <input type="text" name="emitter[nome_fantasia]" 
                                   value="{{ old('emitter.nome_fantasia', $emitter->nome_fantasia ?: ($user->tenant->fantasy_name ?? '')) }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                                   placeholder="Nome Fantasia">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Telefone</label>
                            <input type="text" id="emitter-phone" name="emitter[phone]" 
                                   value="{{ old('emitter.phone', $emitter->phone ?: ($user->tenant->phone ?? '')) }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                                   placeholder="(00) 00000-0000" maxlength="15">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="emitter[email]" 
                                   value="{{ old('emitter.email', $emitter->email ?: ($user->tenant->email ?? '')) }}" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                                   placeholder="email@empresa.com.br">
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Endereço
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    CEP <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="text" name="emitter[zip_code]" id="emitter-zip" 
                                           value="{{ old('emitter.zip_code', $emitter->zip_code ?: ($user->tenant->zip_code ?? '')) }}" 
                                           class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                                           placeholder="00000-000" maxlength="9" required>
                                    <div id="cep-loading" class="absolute inset-y-0 right-0 pr-3 flex items-center hidden">
                                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-emerald-500"></div>
                                    </div>
                                </div>
                        </div>
                        <div class="space-y-2 md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Logradouro <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="emitter[address]" id="emitter-address" 
                                       value="{{ old('emitter.address', $emitter->address ?: ($user->tenant->address ?? '')) }}" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                                       placeholder="Rua, Avenida..." required>
                        </div>
                        <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Número <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="emitter[number]" 
                                       value="{{ old('emitter.number', $emitter->number ?: ($user->tenant->number ?? '')) }}" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                                       placeholder="123" required>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Complemento</label>
                                <input type="text" name="emitter[complement]" 
                                       value="{{ old('emitter.complement', $emitter->complement ?: ($user->tenant->complement ?? '')) }}" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                                       placeholder="Sala, Andar...">
                        </div>
                        <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Bairro <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="emitter[neighborhood]" id="emitter-neighborhood" 
                                       value="{{ old('emitter.neighborhood', $emitter->neighborhood ?: ($user->tenant->neighborhood ?? '')) }}" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                                       placeholder="Centro" required>
                        </div>
                        <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Cidade <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="emitter[city]" id="emitter-city" 
                                       value="{{ old('emitter.city', $emitter->city ?: ($user->tenant->city ?? '')) }}" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                                       placeholder="São Paulo" required>
                        </div>
                        <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    UF <span class="text-red-500">*</span>
                                </label>
                                <select name="emitter[state]" id="emitter-state" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" required>
                                    <option value="">Selecione</option>
                                    @php
                                        $currentState = old('emitter.state', $emitter->state ?: ($user->tenant->state ?? ''));
                                        $states = [
                                            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
                                            'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
                                            'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
                                            'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
                                            'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
                                            'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
                                            'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'
                                        ];
                                    @endphp
                                    @foreach($states as $uf => $nome)
                                        <option value="{{ $uf }}" @selected($currentState === $uf)>{{ $uf }} - {{ $nome }}</option>
                                    @endforeach
                                </select>
                        </div>
                        <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Código IBGE <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="emitter[codigo_ibge]" id="emitter-ibge" 
                                       value="{{ old('emitter.codigo_ibge', $emitter->codigo_ibge) }}" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" 
                                       maxlength="7" placeholder="1234567" required>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Modelo</label>
                            <select name="emitter[nfe_model]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="55" @selected(old('emitter.nfe_model', $emitter->nfe_model)==='55')>55 - NFe</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Série</label>
                            <input type="text" name="emitter[nfe_serie]" value="{{ old('emitter.nfe_serie', $emitter->nfe_serie) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent" maxlength="3">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Número Atual</label>
                            <input type="number" name="emitter[nfe_number_current]" value="{{ old('emitter.nfe_number_current', $emitter->nfe_number_current) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent" min="0">
                        </div>
                        <div class="space-y-2 md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Crédito ICMS (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="emitter[icms_credit_percent]" value="{{ old('emitter.icms_credit_percent', $emitter->icms_credit_percent) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="Ex: 3,00">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Certificado A1 (.pfx/.p12)</label>
                            <input type="file" name="emitter[certificate_file]" id="emitter-cert-file" accept=".pfx,.p12,application/x-pkcs12,application/octet-stream" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <p class="text-xs text-gray-600 mt-1">
                                Selecionado: <span id="cert-selected-label" class="font-medium">
                                    @php
                                        $currentCert = $emitter->certificate_path ? basename($emitter->certificate_path) : null;
                                    @endphp
                                    {{ $currentCert ?? 'Nenhum' }}
                                </span>
                            </p>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Senha do Certificado</label>
                            <div class="relative">
                                <input type="password" id="emitter-cert-pass" name="emitter[certificate_password]" class="w-full pr-12 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="Digite para atualizar">
                                <button type="button" id="toggle-cert-pass" class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700" tabindex="-1" aria-label="Mostrar senha">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">A senha do A1 é necessária para assinar NF-e.</p>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Validade do Certificado</label>
                            <input type="date" name="emitter[certificate_valid_until]" value="{{ old('emitter.certificate_valid_until', optional($emitter->certificate_valid_until)->format('Y-m-d')) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                    </div>

                    <!-- Certificados Instalados no Windows -->
                    <div class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-blue-800 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                Certificados Instalados no Windows
                            </h3>
                            <div class="flex gap-2">
                                <button type="button" id="test-powershell-btn" class="px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center text-sm">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Testar
                                </button>
                                <button type="button" id="load-certificates-btn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Carregar Certificados
                                </button>
                            </div>
                        </div>
                        
                        <div id="certificates-loading" class="hidden text-center py-4">
                            <div class="inline-flex items-center">
                                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mr-3"></div>
                                <span class="text-blue-600">Carregando certificados...</span>
                            </div>
                        </div>
                        
                        <div id="certificates-list" class="hidden">
                            <div class="space-y-3">
                                <div class="text-sm text-blue-700 mb-3">
                                    <strong>Selecione um certificado instalado:</strong>
                                </div>
                                <div id="certificates-container" class="space-y-2">
                                    <!-- Certificados serão carregados aqui -->
                                </div>
                            </div>
                        </div>
                        
                        <div id="certificates-error" class="hidden text-red-600 text-sm mt-2">
                            <!-- Erro será exibido aqui -->
                        </div>
                        
                        <div id="certificates-empty" class="hidden text-gray-600 text-sm mt-2">
                            Nenhum certificado digital encontrado no Windows.
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Disco de Armazenamento</label>
                            <select name="emitter[base_storage_disk]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                @php $diskSel = old('emitter.base_storage_disk', $emitter->base_storage_disk ?? config('filesystems.default')); @endphp
                                <option value="local" @selected($diskSel==='local')>Local (privado)</option>
                                <option value="s3" @selected($diskSel==='s3')>S3/Compatível</option>
                            </select>
                            <p class="text-xs text-gray-500">Arquivos ficam em uma pasta por empresa (tenant).</p>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Pasta Base</label>
                            <input type="text" name="emitter[base_storage_path]" value="{{ old('emitter.base_storage_path', $emitter->base_storage_path) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="tenants/{id}/nfe">
                            <p class="text-xs text-gray-500">Se vazio, usaremos tenants/{{ auth()->user()->tenant_id }}/nfe.</p>
                        </div>
                    </div>

                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-sm text-emerald-800">
                        Os diretórios de XML, DANFE e eventos serão organizados automaticamente por tenant:
                        <code>xml/</code>, <code>danfe/</code>, <code>eventos/</code>, <code>eventos/cancelamento</code>, <code>eventos/carta-correcao</code>, <code>eventos/inutilizacao</code>.
                    </div>
                </div>
            </div>
            @endif


            

            <!-- Botão de Salvar -->
            <div class="flex justify-end pt-6">
                <button type="submit" class="px-8 py-4 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                        style="background: linear-gradient(135deg, {{ $brandPrimary }}, {{ $brandSecondary }});">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Salvar Configurações
                    </button>
                </div>
            </div>
        </form>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elementos
        const zip = document.getElementById('emitter-zip');
        const address = document.getElementById('emitter-address');
        const neighborhood = document.getElementById('emitter-neighborhood');
        const city = document.getElementById('emitter-city');
        const state = document.getElementById('emitter-state');
        const ibge = document.getElementById('emitter-ibge');
        const toggle = document.getElementById('toggle-cert-pass');
        const pass = document.getElementById('emitter-cert-pass');
        const cnpjField = document.getElementById('emitter-cnpj');
        const phoneField = document.getElementById('emitter-phone');
        const cepLoading = document.getElementById('cep-loading');

        // Toggle senha certificado
        if (toggle && pass) {
            toggle.addEventListener('click', function() {
                const type = pass.getAttribute('type') === 'password' ? 'text' : 'password';
                pass.setAttribute('type', type);
            });
        }

        // Máscaras de formatação
        function applyCnpjMask(value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{2})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1/$2')
                .replace(/(\d{4})(\d)/, '$1-$2')
                .replace(/(-\d{2})\d+?$/, '$1');
        }

        function applyPhoneMask(value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{4})(\d)/, '$1-$2')
                .replace(/(\d{4})-(\d)(\d{4})/, '$1$2-$3')
                .replace(/(-\d{4})\d+?$/, '$1');
        }

        function applyCepMask(value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{5})(\d)/, '$1-$2')
                .replace(/(-\d{3})\d+?$/, '$1');
        }

        // Aplicar máscaras
        if (cnpjField) {
            // Formatar CNPJ já existente (vindo do profile)
            if (cnpjField.value) {
                cnpjField.value = applyCnpjMask(cnpjField.value);
            }
            
            cnpjField.addEventListener('input', function(e) {
                e.target.value = applyCnpjMask(e.target.value);
            });
        }

        if (phoneField) {
            // Formatar telefone já existente (vindo do profile)
            if (phoneField.value) {
                phoneField.value = applyPhoneMask(phoneField.value);
            }
            
            phoneField.addEventListener('input', function(e) {
                e.target.value = applyPhoneMask(e.target.value);
            });
        }

        if (zip) {
            // Formatar CEP já existente (vindo do profile)
            if (zip.value) {
                zip.value = applyCepMask(zip.value);
            }
            
            zip.addEventListener('input', function(e) {
                e.target.value = applyCepMask(e.target.value);
            });
        }

        // Funções auxiliares
        function cleanCep(value) { 
            return (value || '').replace(/\D/g, ''); 
        }

        function showLoading(show) {
            if (cepLoading) {
                cepLoading.classList.toggle('hidden', !show);
            }
        }

        // Busca CEP via ViaCEP
        async function fetchViaCep(cep) {
            if (!cep || cep.length !== 8) return;
            
            showLoading(true);
            
            try {
                const resp = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                if (!resp.ok) throw new Error('Erro na requisição');
                
                const data = await resp.json();
                if (data && !data.erro) {
                    let hasChanges = false;
                    
                    // Sempre preencher/atualizar campos com dados do CEP
                    if (address && data.logradouro) {
                        const wasEmpty = !address.value.trim();
                        address.value = data.logradouro;
                        address.classList.add(wasEmpty ? 'bg-green-50' : 'bg-blue-50');
                        setTimeout(() => address.classList.remove('bg-green-50', 'bg-blue-50'), 2000);
                        hasChanges = true;
                    }
                    
                    if (neighborhood && data.bairro) {
                        const wasEmpty = !neighborhood.value.trim();
                        neighborhood.value = data.bairro;
                        neighborhood.classList.add(wasEmpty ? 'bg-green-50' : 'bg-blue-50');
                        setTimeout(() => neighborhood.classList.remove('bg-green-50', 'bg-blue-50'), 2000);
                        hasChanges = true;
                    }
                    
                    if (city && data.localidade) {
                        const wasEmpty = !city.value.trim();
                        city.value = data.localidade;
                        city.classList.add(wasEmpty ? 'bg-green-50' : 'bg-blue-50');
                        setTimeout(() => city.classList.remove('bg-green-50', 'bg-blue-50'), 2000);
                        hasChanges = true;
                    }
                    
                    if (state && data.uf) {
                        const wasEmpty = !state.value;
                        state.value = data.uf;
                        state.classList.add(wasEmpty ? 'bg-green-50' : 'bg-blue-50');
                        setTimeout(() => state.classList.remove('bg-green-50', 'bg-blue-50'), 2000);
                        hasChanges = true;
                    }
                    
                    if (ibge && data.ibge) {
                        const wasEmpty = !ibge.value.trim();
                        ibge.value = data.ibge;
                        ibge.classList.add(wasEmpty ? 'bg-green-50' : 'bg-blue-50');
                        setTimeout(() => ibge.classList.remove('bg-green-50', 'bg-blue-50'), 2000);
                        hasChanges = true;
                    }
                    
                    // Mostrar notificação de sucesso
                    if (hasChanges) {
                        showNotification('Endereço atualizado com dados do CEP!', 'success');
                    }
                } else {
                    // CEP não encontrado
                    if (zip) {
                        zip.classList.add('border-red-300', 'bg-red-50');
                        setTimeout(() => {
                            zip.classList.remove('border-red-300', 'bg-red-50');
                        }, 3000);
                    }
                    showNotification('CEP não encontrado!', 'error');
                }
            } catch (error) {
                console.error('Erro ao buscar CEP:', error);
                if (zip) {
                    zip.classList.add('border-orange-300', 'bg-orange-50');
                    setTimeout(() => {
                        zip.classList.remove('border-orange-300', 'bg-orange-50');
                    }, 3000);
                }
                showNotification('Erro ao buscar CEP. Tente novamente.', 'warning');
            } finally {
                showLoading(false);
            }
        }
        
        // Função para mostrar notificações temporárias
        function showNotification(message, type = 'info') {
            // Remove notificação anterior se existir
            const existing = document.getElementById('cep-notification');
            if (existing) existing.remove();
            
            const notification = document.createElement('div');
            notification.id = 'cep-notification';
            notification.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white font-medium transition-all transform translate-x-0 ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                type === 'warning' ? 'bg-orange-500' : 'bg-blue-500'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Remover após 3 segundos
            setTimeout(() => {
                notification.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        // Tornar disponível globalmente para ser usado por scripts inline de flash
        window.showNotification = showNotification;

        // Event listeners para CEP
        if (zip) {
            // Buscar ao sair do campo
            zip.addEventListener('blur', function() {
                const cleanedCep = cleanCep(zip.value);
                if (cleanedCep && cleanedCep.length === 8) {
                    fetchViaCep(cleanedCep);
                }
            });

            // Buscar automaticamente quando digitar 8 dígitos
            zip.addEventListener('input', function() {
                const cleanedCep = cleanCep(zip.value);
                if (cleanedCep.length === 8) {
                    setTimeout(() => fetchViaCep(cleanedCep), 500);
                }
            });
        }

        // Validação em tempo real
        function addRealTimeValidation() {
            // CNPJ validation
            if (cnpjField) {
                cnpjField.addEventListener('blur', function() {
                    const cnpj = this.value.replace(/\D/g, '');
                    if (cnpj.length !== 14 && cnpj.length > 0) {
                        this.classList.add('border-red-300');
                        this.title = 'CNPJ deve ter 14 dígitos';
                    } else {
                        this.classList.remove('border-red-300');
                        this.title = '';
                    }
                });
            }

            // Phone validation
            if (phoneField) {
                phoneField.addEventListener('blur', function() {
                    const phone = this.value.replace(/\D/g, '');
                    if (phone.length < 10 && phone.length > 0) {
                        this.classList.add('border-red-300');
                        this.title = 'Telefone deve ter pelo menos 10 dígitos';
                    } else {
                        this.classList.remove('border-red-300');
                        this.title = '';
                    }
                });
            }
        }

        addRealTimeValidation();

        // Atualizar label do certificado selecionado
        (function(){
            var input = document.getElementById('emitter-cert-file');
            var label = document.getElementById('cert-selected-label');
            if (input && label) {
                input.addEventListener('change', function(){
                    if (this.files && this.files.length > 0) {
                        label.textContent = this.files[0].name || 'Selecionado';
                    } else {
                        label.textContent = 'Nenhum';
                    }
                });
            }
        })();

        // === CERTIFICADOS INSTALADOS ===
        const loadCertificatesBtn = document.getElementById('load-certificates-btn');
        const testPowerShellBtn = document.getElementById('test-powershell-btn');
        const certificatesLoading = document.getElementById('certificates-loading');
        const certificatesList = document.getElementById('certificates-list');
        const certificatesContainer = document.getElementById('certificates-container');
        const certificatesError = document.getElementById('certificates-error');
        const certificatesEmpty = document.getElementById('certificates-empty');

        // Teste do PowerShell
        if (testPowerShellBtn) {
            testPowerShellBtn.addEventListener('click', async function() {
                try {
                    const response = await fetch('/api/certificates/test-powershell', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        showNotification('PowerShell está funcionando! ✅', 'success');
                        console.log('PowerShell test output:', data.output);
                    } else {
                        showNotification('Erro no PowerShell: ' + data.message, 'error');
                    }
                } catch (error) {
                    showNotification('Erro ao testar PowerShell: ' + error.message, 'error');
                }
            });
        }

        if (loadCertificatesBtn) {
            loadCertificatesBtn.addEventListener('click', async function() {
                // Mostrar loading
                certificatesLoading.classList.remove('hidden');
                certificatesList.classList.add('hidden');
                certificatesError.classList.add('hidden');
                certificatesEmpty.classList.add('hidden');
                certificatesContainer.innerHTML = '';

                try {
                    const response = await fetch('/api/certificates/installed', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();
                    certificatesLoading.classList.add('hidden');

                    if (data.success && data.certificates && data.certificates.length > 0) {
                        // Mostrar lista de certificados
                        certificatesList.classList.remove('hidden');
                        
                        data.certificates.forEach((cert, index) => {
                            const certElement = createCertificateElement(cert, index);
                            certificatesContainer.appendChild(certElement);
                        });
                    } else {
                        // Mostrar mensagem de vazio
                        certificatesEmpty.classList.remove('hidden');
                    }
                } catch (error) {
                    certificatesLoading.classList.add('hidden');
                    certificatesError.classList.remove('hidden');
                    certificatesError.innerHTML = `
                        <div class="text-red-600 font-medium">Erro ao carregar certificados:</div>
                        <div class="text-sm mt-1">${error.message}</div>
                        <div class="text-xs text-gray-500 mt-2">
                            Verifique se o PowerShell está habilitado e se há certificados instalados no Windows.
                        </div>
                    `;
                    console.error('Erro ao carregar certificados:', error);
                }
            });
        }

        function createCertificateElement(cert, index) {
            const div = document.createElement('div');
            div.className = 'border border-blue-200 rounded-lg p-3 hover:bg-blue-100 transition-colors cursor-pointer';
            div.innerHTML = `
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <input type="radio" name="selected_certificate" value="${cert.serial_number}" id="cert-${index}" class="mr-3">
                            <label for="cert-${index}" class="font-medium text-blue-800 cursor-pointer">
                                ${cert.subject_name || 'Certificado Digital'}
                            </label>
                        </div>
                        <div class="text-sm text-gray-600 space-y-1">
                            <div><strong>Número de Série:</strong> ${cert.serial_number}</div>
                            <div><strong>Emissor:</strong> ${cert.issuer_name || 'N/A'}</div>
                            <div><strong>Válido até:</strong> ${cert.valid_until ? new Date(cert.valid_until).toLocaleDateString('pt-BR') : 'N/A'}</div>
                            <div><strong>Tipo:</strong> ${cert.certificate_type || 'A1/A3'}</div>
                        </div>
                    </div>
                    <div class="ml-4">
                        <button type="button" onclick="selectCertificate('${cert.serial_number}')" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition-colors">
                            Selecionar
                        </button>
                    </div>
                </div>
            `;
            return div;
        }

        // Função global para selecionar certificado
        window.selectCertificate = function(serialNumber) {
            // Marcar radio button
            const radio = document.querySelector(`input[value="${serialNumber}"]`);
            if (radio) {
                radio.checked = true;
            }

            // Salvar seleção nas configurações do tenant
            fetch('/settings', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    nfe: {
                        certificate_serial: serialNumber
                    }
                })
            }).then(response => {
                if (response.ok) {
                    showNotification('Certificado selecionado com sucesso!', 'success');
                } else {
                    showNotification('Erro ao salvar seleção do certificado', 'error');
                }
            }).catch(error => {
                showNotification('Erro ao salvar seleção do certificado', 'error');
            });
        };
    });
    </script>
    </div>
</x-app-layout>