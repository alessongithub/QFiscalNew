<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Auditoria do Pedido #{{ $order->number }}
            </h2>
            <a href="{{ route('orders.show', $order) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar ao Pedido
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Informações do Pedido -->
                    <div class="mb-8 bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold mb-4">Informações do Pedido</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <span class="font-medium">Número:</span> {{ $order->number }}
                            </div>
                            <div>
                                <span class="font-medium">Cliente:</span> {{ $order->client->name }}
                            </div>
                            <div>
                                <span class="font-medium">Status:</span> 
                                <span class="px-2 py-1 rounded text-xs font-medium
                                    @if($order->status === 'open') bg-blue-100 text-blue-800
                                    @elseif($order->status === 'fulfilled') bg-green-100 text-green-800
                                    @elseif($order->status === 'canceled') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    @php
                                        $statusLabels = [
                                            'open' => 'Aberto',
                                            'fulfilled' => 'Finalizado',
                                            'canceled' => 'Cancelado'
                                        ];
                                    @endphp
                                    {{ $statusLabels[$order->status] ?? ucfirst($order->status) }}
                                </span>
                            </div>
                            <div>
                                <span class="font-medium">Criado em:</span> {{ $order->created_at->format('d/m/Y H:i') }}
                            </div>
                            <div>
                                <span class="font-medium">Valor Total:</span> R$ {{ number_format($order->total, 2, ',', '.') }}
                            </div>
                            <div>
                                <span class="font-medium">Última Edição:</span> 
                                @php
                                    $lastUpdate = $audits->where('action', 'updated')->first();
                                @endphp
                                {{ $lastUpdate ? $lastUpdate->created_at->format('d/m/Y H:i') : ($order->last_edited_at ? $order->last_edited_at->format('d/m/Y H:i') : 'Nunca') }}
                            </div>
                        </div>
                    </div>

                    <!-- Timeline de Auditoria -->
                    <div class="relative">
                        <h3 class="text-lg font-semibold mb-6">Histórico de Ações</h3>
                        
                        @if($audits->count() > 0)
                            <div class="space-y-6">
                                @foreach($audits as $audit)
                                    @php
                                        $actionColors = [
                                            'created' => 'bg-green-500',
                                            'updated' => 'bg-blue-500',
                                            'canceled' => 'bg-red-600',
                                            'fulfilled' => 'bg-green-600',
                                            'finalized' => 'bg-green-600',
                                            'reopened' => 'bg-yellow-500',
                                            'returned' => 'bg-orange-500',
                                            'auto_adjusted_with_returns' => 'bg-purple-500'
                                        ];
                                        $actionIcons = [
                                            'created' => 'M12 6v6m0 0v6m0-6h6m-6 0H6', // Plus icon
                                            'updated' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', // Pencil icon
                                            'canceled' => 'M6 18L18 6M6 6l12 12', // X icon
                                            'fulfilled' => 'M5 13l4 4L19 7', // Checkmark icon
                                            'finalized' => 'M5 13l4 4L19 7', // Checkmark icon
                                            'reopened' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', // Refresh icon
                                            'returned' => 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6', // Arrow left icon
                                            'auto_adjusted_with_returns' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' // Refresh/Adjust icon
                                        ];
                                        $actionLabels = [
                                            'created' => 'criou o pedido',
                                            'updated' => 'atualizou o pedido',
                                            'canceled' => 'cancelou o pedido',
                                            'fulfilled' => 'finalizou o pedido',
                                            'finalized' => 'finalizou o pedido',
                                            'reopened' => 'reabriu o pedido',
                                            'returned' => 'registrou devolução',
                                            'auto_adjusted_with_returns' => 'ajustou automaticamente'
                                        ];
                                        $iconPath = $actionIcons[$audit->action] ?? 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'; // Default info icon
                                        $bgColor = $actionColors[$audit->action] ?? 'bg-gray-500';
                                        
                                        // Determinar se foi criado a partir de orçamento
                                        $isFromQuote = $audit->action === 'created' && 
                                                     isset($audit->changes['source']) && 
                                                     $audit->changes['source'] === 'quote_conversion';
                                    @endphp
                                    <div class="mb-6 flex items-start">
                                        <div class="absolute -left-2.5 w-5 h-5 rounded-full {{ $bgColor }} flex items-center justify-center text-white">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/></svg>
                                        </div>
                                        <div class="ml-6">
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $audit->user->name ?? 'Sistema' }} {{ $actionLabels[$audit->action] ?? $audit->action }}
                                                @if($isFromQuote)
                                                    <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">a partir de orçamento</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-600">Em {{ $audit->created_at->format('d/m/Y H:i') }}</p>
                                            @if($audit->notes)
                                                <p class="mt-1 text-xs text-gray-500">{{ $audit->notes }}</p>
                                            @endif
                                            @if($audit->changes)
                                                <div class="mt-2 text-xs text-gray-500 bg-gray-50 p-3 rounded">
                                                    <p class="font-semibold mb-2">Detalhes:</p>
                                                    @if($audit->action === 'created')
                                                        @if(isset($audit->changes['source']) && $audit->changes['source'] === 'quote_conversion')
                                                            <div class="space-y-1">
                                                                <p><span class="font-medium">Origem:</span> Conversão de orçamento</p>
                                                                @if(isset($audit->changes['quote_number']))
                                                                    <p><span class="font-medium">Orçamento:</span> #{{ $audit->changes['quote_number'] }}</p>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <div class="space-y-1">
                                                                <p><span class="font-medium">Origem:</span> Criação manual</p>
                                                                @if(isset($audit->changes['total_amount']))
                                                                    <p><span class="font-medium">Valor Total:</span> R$ {{ number_format($audit->changes['total_amount'], 2, ',', '.') }}</p>
                                                                @endif
                                                                @if(isset($audit->changes['items_count']))
                                                                    <p><span class="font-medium">Itens:</span> {{ $audit->changes['items_count'] }} produto(s)</p>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @elseif($audit->action === 'updated')
                                                        <div class="space-y-1">
                                                            @if(isset($audit->changes['updated_fields']))
                                                                <p><span class="font-medium">Campos alterados:</span> {{ implode(', ', $audit->changes['updated_fields']) }}</p>
                                                            @endif
                                                            @if(isset($audit->changes['field_changes']))
                                                                @foreach($audit->changes['field_changes'] as $field => $change)
                                                                    @if($field === 'title')
                                                                        <p><span class="font-medium">Título:</span> "{{ $change['old'] }}" → "{{ $change['new'] }}"</p>
                                                                    @elseif($field === 'client_id')
                                                                        <p><span class="font-medium">Cliente:</span> ID {{ $change['old'] }} → ID {{ $change['new'] }}</p>
                                                                    @elseif($field === 'status')
                                                                        <p><span class="font-medium">Status:</span> {{ ucfirst($change['old']) }} → {{ ucfirst($change['new']) }}</p>
                                                                    @elseif($field === 'discount_total')
                                                                        <p><span class="font-medium">Desconto total:</span> R$ {{ number_format($change['old'], 2, ',', '.') }} → R$ {{ number_format($change['new'], 2, ',', '.') }}</p>
                                                                    @elseif($field === 'total_amount')
                                                                        <p><span class="font-medium">Valor total:</span> R$ {{ number_format($change['old'], 2, ',', '.') }} → R$ {{ number_format($change['new'], 2, ',', '.') }}</p>
                                                                    @elseif($field === 'item_discounts')
                                                                        <p><span class="font-medium">Descontos por item:</span> {{ count($change) }} item(s) alterado(s)</p>
                                                                        @foreach($change as $itemId => $itemChange)
                                                                            <p class="ml-4 text-xs">Item #{{ $itemId }}: R$ {{ number_format($itemChange['old'], 2, ',', '.') }} → R$ {{ number_format($itemChange['new'], 2, ',', '.') }}</p>
                                                                        @endforeach
                                                                    @endif
                                                                @endforeach
                                                            @endif
                                                            @if(isset($audit->changes['discount_total']) && !isset($audit->changes['field_changes']))
                                                                @if(is_array($audit->changes['discount_total']))
                                                                    <p><span class="font-medium">Desconto total:</span> R$ {{ number_format($audit->changes['discount_total']['old'], 2, ',', '.') }} → R$ {{ number_format($audit->changes['discount_total']['new'], 2, ',', '.') }}</p>
                                                                @else
                                                                    <p><span class="font-medium">Desconto total:</span> R$ {{ number_format($audit->changes['discount_total'], 2, ',', '.') }}</p>
                                                                @endif
                                                            @endif
                                                            @if(isset($audit->changes['total_amount']) && !isset($audit->changes['field_changes']))
                                                                @if(is_array($audit->changes['total_amount']))
                                                                    <p><span class="font-medium">Valor total:</span> R$ {{ number_format($audit->changes['total_amount']['old'], 2, ',', '.') }} → R$ {{ number_format($audit->changes['total_amount']['new'], 2, ',', '.') }}</p>
                                                                @else
                                                                    <p><span class="font-medium">Valor total:</span> R$ {{ number_format($audit->changes['total_amount'], 2, ',', '.') }}</p>
                                                                @endif
                                                            @endif
                                                            @if(isset($audit->changes['items_updated']))
                                                                <p><span class="font-medium">Itens atualizados:</span> {{ $audit->changes['items_updated'] }}</p>
                                                            @endif
                                                            @if(isset($audit->changes['action_type']))
                                                                @if($audit->changes['action_type'] === 'add_item')
                                                                    <div class="space-y-1">
                                                                        <p><span class="font-medium">Ação:</span> Item adicionado</p>
                                                                        @if(isset($audit->changes['product_name']))
                                                                            <p><span class="font-medium">Produto:</span> {{ $audit->changes['product_name'] }}</p>
                                                                        @endif
                                                                        @if(isset($audit->changes['quantity']))
                                                                            <p><span class="font-medium">Quantidade:</span> {{ $audit->changes['quantity'] }}</p>
                                                                        @endif
                                                                        @if(isset($audit->changes['unit_price']))
                                                                            <p><span class="font-medium">Preço unitário:</span> R$ {{ number_format($audit->changes['unit_price'], 2, ',', '.') }}</p>
                                                                        @endif
                                                                        @if(isset($audit->changes['was_existing']))
                                                                            <p><span class="font-medium">Tipo:</span> {{ $audit->changes['was_existing'] ? 'Produto existente atualizado' : 'Novo produto adicionado' }}</p>
                                                                        @endif
                                                                        @if(isset($audit->changes['new_total_amount']))
                                                                            <p><span class="font-medium">Novo valor total:</span> R$ {{ number_format($audit->changes['new_total_amount'], 2, ',', '.') }}</p>
                                                                        @endif
                                                                    </div>
                                                                @elseif($audit->changes['action_type'] === 'remove_item')
                                                                    <div class="space-y-1">
                                                                        <p><span class="font-medium">Ação:</span> Item removido</p>
                                                                        @if(isset($audit->changes['product_name']))
                                                                            <p><span class="font-medium">Produto:</span> {{ $audit->changes['product_name'] }}</p>
                                                                        @endif
                                                                        @if(isset($audit->changes['quantity']))
                                                                            <p><span class="font-medium">Quantidade:</span> {{ $audit->changes['quantity'] }}</p>
                                                                        @endif
                                                                        @if(isset($audit->changes['unit_price']))
                                                                            <p><span class="font-medium">Preço unitário:</span> R$ {{ number_format($audit->changes['unit_price'], 2, ',', '.') }}</p>
                                                                        @endif
                                                                        @if(isset($audit->changes['stock_returned']) && $audit->changes['stock_returned'])
                                                                            <p><span class="font-medium">Estoque:</span> Devolvido</p>
                                                                        @endif
                                                                        @if(isset($audit->changes['new_total_amount']))
                                                                            <p><span class="font-medium">Novo valor total:</span> R$ {{ number_format($audit->changes['new_total_amount'], 2, ',', '.') }}</p>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    @elseif($audit->action === 'canceled')
                                                        <div class="space-y-1">
                                                            @if(isset($audit->changes['cancel_reason']))
                                                                <p><span class="font-medium">Justificativa:</span> {{ $audit->changes['cancel_reason'] }}</p>
                                                            @endif
                                                            @if(isset($audit->changes['canceled_by']))
                                                                <p><span class="font-medium">Cancelado por:</span> {{ $audit->changes['canceled_by'] }}</p>
                                                            @endif
                                                            @if(isset($audit->changes['total_estornado']) && $audit->changes['total_estornado'] > 0)
                                                                <p><span class="font-medium">Valor estornado:</span> R$ {{ number_format($audit->changes['total_estornado'], 2, ',', '.') }}</p>
                                                            @endif
                                                            @if(isset($audit->changes['total_cancelado']) && $audit->changes['total_cancelado'] > 0)
                                                                <p><span class="font-medium">Parcelas canceladas:</span> R$ {{ number_format($audit->changes['total_cancelado'], 2, ',', '.') }}</p>
                                                                    @endif
                                                            @if(isset($audit->changes['taxa_antecipacao']) && $audit->changes['taxa_antecipacao'] > 0)
                                                                <p><span class="font-medium">Taxa de antecipação:</span> R$ {{ number_format($audit->changes['taxa_antecipacao'], 2, ',', '.') }}</p>
                                                            @endif
                                                            @if(isset($audit->changes['stock_returned']) && $audit->changes['stock_returned'])
                                                                <p><span class="font-medium">Estoque:</span> Devolvido</p>
                                                            @endif
                                                            @if(isset($audit->changes['receivables_count']))
                                                                <p><span class="font-medium">Títulos processados:</span> {{ $audit->changes['receivables_count'] }}</p>
                                                            @endif
                                                        </div>
                                                    @elseif($audit->action === 'finalized')
                                                        <div class="space-y-1">
                                                            @if(isset($audit->changes['freight_mode']))
                                                                <p><span class="font-medium">Modalidade de frete:</span> 
                                                                    @if($audit->changes['freight_mode'] === '0') Sem frete
                                                                    @elseif($audit->changes['freight_mode'] === '1') Por conta do destinatário
                                                                    @elseif($audit->changes['freight_mode'] === '2') Por conta do remetente
                                                                    @elseif($audit->changes['freight_mode'] === '9') Sem especificação
                                                                    @else {{ $audit->changes['freight_mode'] }}
                                                                    @endif
                                                                </p>
                                                            @endif
                                                            @if(isset($audit->changes['freight_value']))
                                                                <p><span class="font-medium">Valor do frete:</span> R$ {{ number_format($audit->changes['freight_value'], 2, ',', '.') }}</p>
                                                            @endif
                                                            @if(isset($audit->changes['stock_reduced']) && $audit->changes['stock_reduced'])
                                                                <p><span class="font-medium">Estoque:</span> Baixado</p>
                                                            @endif
                                                            @if(isset($audit->changes['payments_registered']) && $audit->changes['payments_registered'])
                                                                <p><span class="font-medium">Pagamentos:</span> Registrados</p>
                                                            @endif
                                                        </div>
                                                    @elseif($audit->action === 'reopened')
                                                        <div class="space-y-1">
                                                            @if(isset($audit->changes['justification']))
                                                                <p><span class="font-medium">Justificativa:</span> {{ $audit->changes['justification'] }}</p>
                                                            @endif
                                                            @if(isset($audit->changes['financial_reversal']) && $audit->changes['financial_reversal'])
                                                                <p><span class="font-medium">Financeiro:</span> Estornado</p>
                                                            @endif
                                                            @if(isset($audit->changes['stock_reversal']) && $audit->changes['stock_reversal'])
                                                                <p><span class="font-medium">Estoque:</span> Revertido</p>
                                                            @endif
                                                        </div>
                                                    @elseif($audit->action === 'returned')
                                                        <div class="space-y-1">
                                                            @if(isset($audit->changes['refund_type']))
                                                                <p><span class="font-medium">Tipo de devolução:</span> 
                                                                    @if($audit->changes['refund_type'] === 'credit') Crédito
                                                                    @elseif($audit->changes['refund_type'] === 'abatement') Abatimento
                                                                    @elseif($audit->changes['refund_type'] === 'refund') Estorno
                                                                    @else {{ $audit->changes['refund_type'] }}
                                                                    @endif
                                                                </p>
                                                            @endif
                                                            @if(isset($audit->changes['total_refund']))
                                                                <p><span class="font-medium">Valor devolvido:</span> R$ {{ number_format($audit->changes['total_refund'], 2, ',', '.') }}</p>
                                                            @endif
                                                            @php
                                                                $det = $audit->changes['detalhes'] ?? ($audit->changes['itens_text'] ?? null);
                                                                $itens = $audit->changes['itens'] ?? null;
                                                            @endphp
                                                            @if($det)
                                                                <p><span class="font-medium">Itens devolvidos:</span> {{ $det }}</p>
                                                            @elseif(is_array($itens) && count($itens))
                                                                <div class="text-xs">
                                                                    <span class="font-medium">Itens devolvidos:</span>
                                                                    <ul class="list-disc ml-5">
                                                                        @foreach($itens as $i)
                                                                            <li>{{ $i['produto'] ?? 'Item' }} — {{ number_format((float)($i['quantidade'] ?? 0), 3, ',', '.') }} (R$ {{ number_format((float)($i['valor'] ?? 0), 2, ',', '.') }})</li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @elseif($audit->action === 'status_changed')
                                                        @php
                                                            $map = [
                                                                'open' => 'Aberto',
                                                                'in_progress' => 'Orçada',
                                                                'in_service' => 'Em andamento',
                                                                'service_finished' => 'Serviço finalizado',
                                                                'warranty' => 'Garantia',
                                                                'no_repair' => 'Sem reparo',
                                                                'finished' => 'Finalizado',
                                                                'fulfilled' => 'Finalizado',
                                                                'canceled' => 'Cancelado',
                                                                'partial_returned' => 'Devolução parcial',
                                                            ];
                                                            $old = $audit->changes['status']['old'] ?? null;
                                                            $new = $audit->changes['status']['new'] ?? null;
                                                        @endphp
                                                        <div class="space-y-1">
                                                            <p><span class="font-medium">Status:</span> {{ $map[$old] ?? $old }} → {{ $map[$new] ?? $new }}</p>
                                                        </div>
                                                    @elseif($audit->action === 'auto_adjusted_with_returns')
                                                        <div class="space-y-2">
                                                            @if(isset($audit->changes['adjustments']) && is_array($audit->changes['adjustments']))
                                                                <p class="font-medium">Ajustes realizados:</p>
                                                                <ul class="list-disc ml-5 space-y-1">
                                                                    @foreach($audit->changes['adjustments'] as $adjustment)
                                                                        @if(isset($adjustment['action']) && $adjustment['action'] === 'removed')
                                                                            <li>
                                                                                <span class="font-medium">{{ $adjustment['name'] ?? 'Item' }}</span> — 
                                                                                Removido (quantidade original: {{ number_format((float)($adjustment['original_qty'] ?? 0), 3, ',', '.') }})
                                                                            </li>
                                                                        @elseif(isset($adjustment['action']) && $adjustment['action'] === 'adjusted')
                                                                            <li>
                                                                                <span class="font-medium">{{ $adjustment['name'] ?? 'Item' }}</span> — 
                                                                                Quantidade ajustada: 
                                                                                {{ number_format((float)($adjustment['original_qty'] ?? 0), 3, ',', '.') }} → 
                                                                                {{ number_format((float)($adjustment['new_qty'] ?? 0), 3, ',', '.') }}
                                                                                @if(isset($adjustment['original_discount']) && (float)$adjustment['original_discount'] > 0)
                                                                                    | Desconto original: R$ {{ number_format((float)$adjustment['original_discount'], 2, ',', '.') }}
                                                                                @endif
                                                                                @if(isset($adjustment['new_discount']) && (float)$adjustment['new_discount'] > 0)
                                                                                    | Novo desconto: R$ {{ number_format((float)$adjustment['new_discount'], 2, ',', '.') }}
                                                                                @endif
                                                                            </li>
                                                                        @endif
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                            @if(isset($audit->changes['total_adjustments']))
                                                                <p><span class="font-medium">Total de ajustes:</span> {{ $audit->changes['total_adjustments'] }}</p>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <pre class="whitespace-pre-wrap">{{ json_encode($audit->changes, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p>Nenhuma ação registrada ainda.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
