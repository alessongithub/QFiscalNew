<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Atividades do Sistema
            </h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6">
        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow mb-6 p-4">
            <form method="GET" action="{{ route('activity.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>Todos</option>
                        <option value="orders" {{ $type === 'orders' ? 'selected' : '' }}>Pedidos</option>
                        <option value="quotes" {{ $type === 'quotes' ? 'selected' : '' }}>Orçamentos</option>
                        <option value="tax_rates" {{ $type === 'tax_rates' ? 'selected' : '' }}>Tributações</option>
                        <option value="settings" {{ $type === 'settings' ? 'selected' : '' }}>Configurações</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Usuário</label>
                    <select name="user_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">Todos</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $userId == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Registros por página</label>
                    <select name="per_page" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        @foreach([10,25,50,100] as $pp)
                            <option value="{{ $pp }}" {{ ($perPage ?? 25) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-5 flex justify-end gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Filtrar</button>
                    <a href="{{ route('activity.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Limpar</a>
                </div>
            </form>
        </div>

        <!-- Timeline -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <h3 class="text-white text-lg font-semibold">Histórico de Atividades</h3>
                <p class="text-blue-100 text-sm">{{ $activities->count() }} atividade(s) encontrada(s)</p>
            </div>

            <div class="p-6">
                @if(($paginator->total() ?? 0) > 0)
                    <div class="relative">
                        <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                        
                        <ul class="space-y-6">
                            @foreach(($paginator->items() ?? []) as $activity)
                                @php
                                    $typeColors = [
                                        'order' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'border' => 'border-blue-300', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                                        'quote' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'border' => 'border-green-300', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                                        'tax_rate' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'border' => 'border-purple-300', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                                        'settings' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'border' => 'border-orange-300', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                                    ];
                                    $colors = $typeColors[$activity['type']] ?? $typeColors['order'];
                                @endphp
                                <li class="relative pl-16">
                                    <div class="absolute left-0 w-16 flex justify-center">
                                        <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $colors['bg'] }} border-2 {{ $colors['border'] }}">
                                            <svg class="w-4 h-4 {{ $colors['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $colors['icon'] }}"/>
                                            </svg>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-white rounded-lg border {{ $colors['border'] }} shadow-sm p-4 hover:shadow-md transition-shadow">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center flex-wrap gap-x-2 mb-2">
                                                    @php
                                                        $byLabels = [
                                                            'created' => 'Criado por:',
                                                            'updated' => 'Editado por:',
                                                            'deleted' => 'Excluído por:',
                                                            'approved' => 'Aprovado por:',
                                                            'rejected' => 'Reprovado por:',
                                                            'canceled' => 'Cancelado por:',
                                                            'converted' => 'Convertido por:',
                                                        ];
                                                        $who = $activity['user']->name ?? 'Sistema';
                                                        $prefix = $byLabels[$activity['action']] ?? ($activity['action_label'] ?? 'Ação:');
                                                    @endphp
                                                    <span class="text-gray-600">{{ $prefix }}</span>
                                                    <span class="font-semibold text-gray-900">{{ $who }}</span>
                                                    <span class="text-sm {{ $colors['text'] }} font-medium">— {{ $activity['type_label'] }}</span>
                                                    @if($activity['entity_label'])
                                                        <span class="text-gray-600">:</span>
                                                        @if($activity['url'])
                                                            <a href="{{ $activity['url'] }}" class="text-blue-600 hover:text-blue-800 font-medium">{{ $activity['entity_label'] }}</a>
                                                        @else
                                                            <span class="font-medium">{{ $activity['entity_label'] }}</span>
                                                        @endif
                                                    @endif
                                                </div>
                                                
                                                @if($activity['notes'])
                                                    <p class="text-sm text-gray-700 mb-2">{{ $activity['notes'] }}</p>
                                                @endif
                                                
                                                @if($activity['changes'] && is_array($activity['changes']))
                                                    <div class="mt-2 bg-gray-50 rounded-lg p-3 border border-gray-200">
                                                        <div class="text-xs font-semibold text-gray-600 uppercase mb-2">Mudanças:</div>
                                                        <div class="space-y-1">
                                                            @foreach($activity['changes'] as $field => $change)
                                                                @php
                                                                    $fieldLabels = [
                                                                        'status' => 'Status',
                                                                        'Itens devolvidos' => 'Itens devolvidos',
                                                                        'justification' => 'Justificativa',
                                                                        'financial_reversal' => 'Estorno financeiro',
                                                                        'stock_reversal' => 'Reversão de estoque',
                                                                        'discount_total' => 'Desconto total',
                                                                        'total_amount' => 'Valor total',
                                                                        'updated_fields' => 'Campos alterados',
                                                                        'action_type' => 'Tipo de ação',
                                                                        'product_name' => 'Produto',
                                                                        'quantity' => 'Quantidade',
                                                                        'unit_price' => 'Preço unitário',
                                                                        'stock_returned' => 'Estoque devolvido',
                                                                        'stock_reduced' => 'Estoque baixado',
                                                                        'payments_registered' => 'Pagamentos registrados',
                                                                        'new_total_amount' => 'Novo valor total',
                                                                        'was_existing' => 'Produto existente',
                                                                        'timestamp' => 'Data/Hora',
                                                                        'source' => 'Origem',
                                                                        'quote_number' => 'Número do orçamento',
                                                                        'quote_conversion' => 'Conversão de orçamento',
                                                                        'total_amount' => 'Valor total',
                                                                        'items_count' => 'Quantidade de itens',
                                                                        'freight_mode' => 'Modalidade de frete',
                                                                        'freight_value' => 'Valor do frete',
                                                                        'auto_adjustments_applied' => 'Ajustes automáticos aplicados',
                                                                        'adjustments' => 'Ajustes',
                                                                        'total_adjustments' => 'Total de ajustes',
                                                                        'to' => 'Destinatário',
                                                                        'subject' => 'Assunto',
                                                                        'template' => 'Modelo',
                                                                        'has_pdf' => 'PDF anexado',
                                                                        'cancel_reason' => 'Motivo do cancelamento',
                                                                        'canceled_by' => 'Cancelado por',
                                                                        'total_estornado' => 'Total estornado',
                                                                        'total_cancelado' => 'Total cancelado',
                                                                        'taxa_antecipacao' => 'Taxa de antecipação',
                                                                        'receivables_count' => 'Quantidade de recebíveis',
                                                                        'payment_type' => 'Tipo de pagamento',
                                                                        'payment_method' => 'Forma de pagamento',
                                                                        'qty' => 'Qtd',
                                                                        'prev' => 'Anterior',
                                                                        'new' => 'Novo',
                                                                    ];
                                                                    $fieldLabel = $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field));
                                                                @endphp
                                                                @if(is_array($change) && isset($change['old']) && isset($change['new']))
                                                                    <div class="text-sm">
                                                                        <span class="font-medium text-gray-700">{{ $fieldLabel }}:</span>
                                                                        <span class="text-red-600 line-through mr-2">{{ $change['old'] ?? '—' }}</span>
                                                                        <span class="text-green-600 font-semibold">→ {{ $change['new'] ?? '—' }}</span>
                                                                    </div>
                                                                @elseif($field === 'old' && isset($activity['changes']['new']))
                                                                    <div class="text-sm">
                                                                        <span class="text-red-600 line-through mr-2">{{ $change ?? '—' }}</span>
                                                                        <span class="text-green-600 font-semibold">→ {{ $activity['changes']['new'] ?? '—' }}</span>
                                                                    </div>
                                                                @elseif($field === 'timestamp' && is_string($change))
                                                                    @php
                                                                        try {
                                                                            $timestamp = \Carbon\Carbon::parse($change);
                                                                            $formattedTimestamp = $timestamp->format('d/m/Y H:i:s');
                                                                        } catch (\Exception $e) {
                                                                            $formattedTimestamp = $change;
                                                                        }
                                                                    @endphp
                                                                    <div class="text-sm">
                                                                        <span class="font-medium text-gray-700">{{ $fieldLabel }}:</span>
                                                                        <span class="text-gray-800"> {{ $formattedTimestamp }}</span>
                                                                    </div>
                                                                @elseif($field === 'source' && $change === 'quote_conversion')
                                                                    <div class="text-sm">
                                                                        <span class="font-medium text-gray-700">{{ $fieldLabel }}:</span>
                                                                        <span class="text-gray-800"> Conversão de orçamento</span>
                                                                    </div>
                                                                @elseif($field === 'freight_mode' && is_string($change))
                                                                    @php
                                                                        $freightLabels = [
                                                                            '0' => 'Sem frete',
                                                                            '1' => 'Por conta do destinatário',
                                                                            '2' => 'Por conta do remetente',
                                                                            '9' => 'Sem especificação',
                                                                        ];
                                                                        $freightLabel = $freightLabels[$change] ?? $change;
                                                                    @endphp
                                                                    <div class="text-sm">
                                                                        <span class="font-medium text-gray-700">{{ $fieldLabel }}:</span>
                                                                        <span class="text-gray-800"> {{ $freightLabel }}</span>
                                                                    </div>
                                                                @elseif($field === 'freight_value' && is_numeric($change))
                                                                    <div class="text-sm">
                                                                        <span class="font-medium text-gray-700">{{ $fieldLabel }}:</span>
                                                                        <span class="text-gray-800"> R$ {{ number_format((float)$change, 2, ',', '.') }}</span>
                                                                    </div>
                                                                @elseif($field === 'has_pdf')
                                                                    @php
                                                                        $pdfValue = is_bool($change) ? $change : (strtolower((string)$change) === 'true' || (string)$change === '1');
                                                                    @endphp
                                                                    <div class="text-sm">
                                                                        <span class="font-medium text-gray-700">{{ $fieldLabel }}:</span>
                                                                        <span class="text-gray-800"> {{ $pdfValue ? 'Sim' : 'Não' }}</span>
                                                                    </div>
                                                                @elseif($field === 'template' && is_string($change))
                                                                    @php
                                                                    $templateLabels = [
                                                                        'order_confirmation' => 'Confirmação do Pedido',
                                                                        'order_fulfilled' => 'Pedido Finalizado',
                                                                        'order_shipped' => 'Pedido Enviado',
                                                                        'quote_default' => 'Modelo Padrão',
                                                                        'approval_request' => 'Solicitação de Aprovação',
                                                                        'ready_for_pickup' => 'Pronto para Retirada',
                                                                        'cancellation' => 'Cancelamento de OS',
                                                                        'custom' => 'Mensagem Personalizada',
                                                                    ];
                                                                        $templateLabel = $templateLabels[$change] ?? $change;
                                                                    @endphp
                                                                    <div class="text-sm">
                                                                        <span class="font-medium text-gray-700">{{ $fieldLabel }}:</span>
                                                                        <span class="text-gray-800"> {{ $templateLabel }}</span>
                                                                    </div>
                                                                @elseif($field === 'payment_type' && is_string($change))
                                                                    @php
                                                                        $paymentTypeLabels = [
                                                                            'immediate' => 'À vista',
                                                                            'invoice' => 'Faturado',
                                                                            'mixed' => 'Misto',
                                                                        ];
                                                                        $paymentTypeLabel = $paymentTypeLabels[$change] ?? $change;
                                                                    @endphp
                                                                    <div class="text-sm">
                                                                        <span class="font-medium text-gray-700">{{ $fieldLabel }}:</span>
                                                                        <span class="text-gray-800"> {{ $paymentTypeLabel }}</span>
                                                                    </div>
                                                                @elseif($field === 'payment_method' && is_string($change))
                                                                    @php
                                                                        $paymentMethodLabels = [
                                                                            'cash' => 'Dinheiro',
                                                                            'card' => 'Cartão',
                                                                            'pix' => 'PIX',
                                                                            'boleto' => 'Boleto',
                                                                            'transfer' => 'Transferência',
                                                                            'mixed' => 'Misto',
                                                                        ];
                                                                        $paymentMethodLabel = $paymentMethodLabels[$change] ?? $change;
                                                                    @endphp
                                                                    <div class="text-sm">
                                                                        <span class="font-medium text-gray-700">{{ $fieldLabel }}:</span>
                                                                        <span class="text-gray-800"> {{ $paymentMethodLabel }}</span>
                                                                    </div>
                                                                @elseif(is_string($change))
                                                                    <div class="text-sm">
                                                                        <span class="font-medium text-gray-700">{{ $fieldLabel }}:</span>
                                                                        <span class="text-gray-800"> {{ $change }}</span>
                                                                    </div>
                                                                @elseif(is_bool($change))
                                                                    <div class="text-sm">
                                                                        <span class="font-medium text-gray-700">{{ $fieldLabel }}:</span>
                                                                        <span class="text-gray-800"> {{ $change ? 'Sim' : 'Não' }}</span>
                                                                    </div>
                                                                @elseif(is_numeric($change))
                                                                    <div class="text-sm">
                                                                        <span class="font-medium text-gray-700">{{ $fieldLabel }}:</span>
                                                                        <span class="text-gray-800"> 
                                                                            @if(in_array($field, ['qty', 'prev', 'new']))
                                                                                {{ number_format((float)$change, 3, ',', '.') }}
                                                                            @else
                                                                                {{ number_format((float)$change, 2, ',', '.') }}
                                                                            @endif
                                                                        </span>
                                                                    </div>
                                                                @elseif(is_array($change))
                                                                    @if($field === 'adjustments' && is_array($change))
                                                                        <div class="text-sm">
                                                                            <span class="font-medium text-gray-700">{{ $fieldLabel }}:</span>
                                                                            <ul class="list-disc ml-5 mt-1 space-y-1">
                                                                                @foreach($change as $adj)
                                                                                    @if(isset($adj['action']) && $adj['action'] === 'removed')
                                                                                        <li>
                                                                                            <span class="font-medium">{{ $adj['name'] ?? 'Item' }}</span> — 
                                                                                            Removido (quantidade original: {{ number_format((float)($adj['original_qty'] ?? 0), 3, ',', '.') }})
                                                                                        </li>
                                                                                    @elseif(isset($adj['action']) && $adj['action'] === 'adjusted')
                                                                                        <li>
                                                                                            <span class="font-medium">{{ $adj['name'] ?? 'Item' }}</span> — 
                                                                                            Quantidade: {{ number_format((float)($adj['original_qty'] ?? 0), 3, ',', '.') }} → {{ number_format((float)($adj['new_qty'] ?? 0), 3, ',', '.') }}
                                                                                        </li>
                                                                                    @endif
                                                                                @endforeach
                                                                            </ul>
                                                                        </div>
                                                                    @else
                                                                        {{-- Array genérico --}}
                                                                        <div class="text-sm">
                                                                            <span class="font-medium text-gray-700">{{ $fieldLabel }}:</span>
                                                                            <pre class="text-xs mt-1 bg-gray-100 p-2 rounded">{{ json_encode($change, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <div class="ml-4 text-right">
                                                <time class="text-xs text-gray-500 whitespace-nowrap" datetime="{{ $activity['created_at']->toISOString() }}">
                                                    {{ $activity['created_at']->format('d/m/Y') }}
                                                </time>
                                                <time class="text-xs text-gray-400 block" datetime="{{ $activity['created_at']->toISOString() }}">
                                                    {{ $activity['created_at']->format('H:i') }}
                                                </time>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="mt-6">
                        {{ $paginator->withQueryString()->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma atividade encontrada</h3>
                        <p class="text-sm text-gray-500">Não há atividades registradas para o período selecionado.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

