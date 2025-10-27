<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Visualizar Pedido #{{ $order->number }}
            </h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('orders.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Voltar
                </a>
                @if(auth()->user()->hasPermission('orders.edit') && $order->status !== 'fulfilled' && $order->status !== 'canceled')
                    <a href="{{ route('orders.edit', $order) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar
                    </a>
                @endif
                @if(auth()->user()->is_admin || auth()->user()->hasPermission('orders.audit'))
                    <a href="{{ route('orders.audit', $order) }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Auditoria
                    </a>
                @endif
                <button onclick="printOrder()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimir
                </button>
                <a href="{{ route('orders.pdf', $order) }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Informações Principais -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Dados do Pedido -->
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Dados do Pedido
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                                    <div class="text-lg font-semibold text-gray-900">#{{ $order->number }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                        @if($order->status === 'draft') bg-gray-100 text-gray-800
                                        @elseif($order->status === 'open') bg-yellow-100 text-yellow-800
                                        @elseif($order->status === 'fulfilled') bg-green-100 text-green-800
                                        @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                        @elseif($order->status === 'canceled') bg-red-100 text-red-800
                                        @else bg-blue-100 text-blue-800 @endif">
                                        @switch($order->status)
                                            @case('draft') Rascunho @break
                                            @case('open') Aberto @break
                                            @case('fulfilled') Finalizado @break
                                            @case('cancelled') Cancelado @break
                                            @case('canceled') Cancelado @break
                                            @default {{ ucfirst($order->status) }} @break
                                        @endswitch
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                                    <div class="text-gray-900">{{ $order->title ?: 'Sem título' }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Criação</label>
                                    <div class="text-gray-900">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dados do Cliente -->
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-green-50 to-green-100 px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Dados do Cliente
                            </h3>
                        </div>
                        <div class="p-6">
                            @if($order->client)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                                        <div class="text-gray-900 font-medium">{{ $order->client->name }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Documento</label>
                                        <div class="text-gray-900">{{ $order->client->document ?: 'Não informado' }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                                        <div class="text-gray-900">{{ $order->client->email ?: 'Não informado' }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                                        <div class="text-gray-900">{{ $order->client->phone ?: 'Não informado' }}</div>
                                    </div>
                                    @if($order->client->address)
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
                                            <div class="text-gray-900">{{ $order->client->address }}</div>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="text-gray-500 text-center py-4">Cliente não informado</div>
                            @endif
                        </div>
                    </div>

                    <!-- Itens do Pedido -->
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Itens do Pedido
                            </h3>
                        </div>
                        <div class="w-full">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UN</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">V.Unit</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Desc. (R$)</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    @forelse($order->items as $item)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $item->name }}</div>
                                                @if($item->description)
                                                    <div class="text-sm text-gray-500">{{ $item->description }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($item->quantity, 3, ',', '.') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->unit }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">R$ {{ number_format((float)($item->discount_value ?? 0), 2, ',', '.') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">R$ {{ number_format((float)$item->line_total, 2, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Nenhum item encontrado</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    
                    <!-- Resumo Financeiro -->
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                                Resumo Financeiro
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Subtotal:</span>
                                <span class="text-sm font-medium text-gray-900">R$ {{ number_format($order->items->sum('line_total'), 2, ',', '.') }}</span>
                            </div>
                            @php
                                $totalDiscountItems = $order->items->sum('discount_value');
                            @endphp
                            @if($totalDiscountItems > 0)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Desconto por Itens:</span>
                                    <span class="text-sm font-medium text-red-600">- R$ {{ number_format($totalDiscountItems, 2, ',', '.') }}</span>
                                </div>
                            @endif
                            @if($order->discount_total > 0)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Desconto Global:</span>
                                    <span class="text-sm font-medium text-red-600">- R$ {{ number_format($order->discount_total, 2, ',', '.') }}</span>
                                </div>
                            @endif
                            @if($order->addition_total > 0)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Acréscimo:</span>
                                    <span class="text-sm font-medium text-green-600">+ R$ {{ number_format($order->addition_total, 2, ',', '.') }}</span>
                                </div>
                            @endif
                            @if($order->freight_cost > 0)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Frete:</span>
                                    <span class="text-sm font-medium text-gray-900">R$ {{ number_format($order->freight_cost, 2, ',', '.') }}</span>
                                </div>
                            @endif
                            <hr class="border-gray-200">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-900">Total:</span>
                                <span class="text-lg font-bold text-gray-900">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Informações de Frete -->
                    @if($order->carrier || $order->freight_cost > 0 || $order->freight_mode)
                        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-indigo-50 to-indigo-100 px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    Informações de Frete
                                </h3>
                            </div>
                            <div class="p-6 space-y-4">
                                @if($order->freight_mode)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Modalidade de Frete</label>
                                        <div class="text-gray-900">
                                            @switch($order->freight_mode)
                                                @case(0) Por conta do destinatário @break
                                                @case(1) Por conta do remetente @break
                                                @case(2) Por conta de terceiros @break
                                                @case(9) Sem frete @break
                                                @default Modalidade {{ $order->freight_mode }} @break
                                            @endswitch
                                        </div>
                                    </div>
                                @endif
                                @if($order->carrier)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Transportadora</label>
                                        <div class="text-gray-900">{{ $order->carrier->name }}</div>
                                    </div>
                                @elseif($order->freight_mode && $order->freight_mode != 9)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Transportadora</label>
                                        <div class="text-gray-500 italic">Não informada</div>
                                    </div>
                                @endif
                                @if($order->freight_payer)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Pagador do Frete</label>
                                        <div class="text-gray-900">
                                            @switch($order->freight_payer)
                                                @case('sender') Remetente @break
                                                @case('receiver') Destinatário @break
                                                @case('third') Terceiros @break
                                                @default {{ ucfirst($order->freight_payer) }} @break
                                            @endswitch
                                        </div>
                                    </div>
                                @endif
                                @if($order->freight_obs)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                                        <div class="text-gray-900">{{ $order->freight_obs }}</div>
                                    </div>
                                @endif
                                @if($order->volume_qtd)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade de Volumes</label>
                                        <div class="text-gray-900">{{ $order->volume_qtd }}</div>
                                    </div>
                                @endif
                                @if($order->volume_especie)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Espécie dos Volumes</label>
                                        <div class="text-gray-900">{{ $order->volume_especie }}</div>
                                    </div>
                                @endif
                                @if($order->peso_bruto)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Peso Bruto (kg)</label>
                                        <div class="text-gray-900">{{ number_format($order->peso_bruto, 3, ',', '.') }}</div>
                                    </div>
                                @endif
                                @if($order->peso_liquido)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Peso Líquido (kg)</label>
                                        <div class="text-gray-900">{{ number_format($order->peso_liquido, 3, ',', '.') }}</div>
                                    </div>
                                @endif
                                @if($order->valor_seguro)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Valor do Seguro</label>
                                        <div class="text-gray-900">R$ {{ number_format($order->valor_seguro, 2, ',', '.') }}</div>
                                    </div>
                                @endif
                                @if($order->outras_despesas)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Outras Despesas</label>
                                        <div class="text-gray-900">R$ {{ number_format($order->outras_despesas, 2, ',', '.') }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Informações Adicionais -->
                    @if($order->additional_info || $order->fiscal_info)
                        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Informações Adicionais
                                </h3>
                            </div>
                            <div class="p-6 space-y-4">
                                @if($order->additional_info)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Informações Gerais</label>
                                        <div class="text-gray-900">{{ $order->additional_info }}</div>
                                    </div>
                                @endif
                                @if($order->fiscal_info)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Informações Fiscais</label>
                                        <div class="text-gray-900">{{ $order->fiscal_info }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Contas a Receber -->
                    @if($order->receivables->count() > 0)
                        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-emerald-50 to-emerald-100 px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                    </svg>
                                    Contas a Receber
                                </h3>
                            </div>
                            <div class="p-6">
                                <div class="space-y-3">
                                    @foreach($order->receivables as $receivable)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div>
                                                <div class="font-medium text-gray-900">{{ $receivable->description }}</div>
                                                <div class="text-sm text-gray-600">
                                                    @switch($receivable->status)
                                                        @case('paid') Pago @break
                                                        @case('open') Em aberto @break
                                                        @case('partial') Parcial @break
                                                        @case('canceled') Cancelado @break
                                                        @default {{ ucfirst($receivable->status) }} @break
                                                    @endswitch
                                                    • R$ {{ number_format($receivable->amount, 2, ',', '.') }}
                                                </div>
                                                @if($receivable->payment_method)
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        @switch($receivable->payment_method)
                                                            @case('cash') Dinheiro @break
                                                            @case('pix') PIX @break
                                                            @case('card') Cartão de Crédito @break
                                                            @case('debit') Cartão de Débito @break
                                                            @case('boleto') Boleto Bancário @break
                                                            @case('transfer') Transferência @break
                                                            @case('check') Cheque @break
                                                            @case('other') Outros @break
                                                            @default {{ ucfirst($receivable->payment_method) }} @break
                                                        @endswitch
                                                    </div>
                                                @endif
                                                @if($receivable->due_date)
                                                    <div class="text-xs text-gray-500">
                                                        Vencimento: {{ $receivable->due_date->format('d/m/Y') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                font-size: 12px;
                line-height: 1.4;
            }
            
            .print-container {
                max-width: 100%;
                margin: 0;
                padding: 0;
            }
            
            .print-header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
            }
            
            .print-section {
                margin-bottom: 15px;
                page-break-inside: avoid;
            }
            
            .print-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 10px;
            }
            
            .print-table th,
            .print-table td {
                border: 1px solid #000;
                padding: 5px;
                text-align: left;
            }
            
            .print-table th {
                background-color: #f0f0f0;
                font-weight: bold;
            }
        }
    </style>

    <script>
        function printOrder() {
            // Criar uma nova janela para impressão
            const printWindow = window.open('', '_blank');
            
            // Obter o conteúdo do pedido
            const orderContent = document.querySelector('.py-6').innerHTML;
            
            // HTML completo para impressão
            const printHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Pedido #{{ $order->number }}</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            font-size: 12px;
                            line-height: 1.4;
                            margin: 20px;
                        }
                        
                        .print-header {
                            text-align: center;
                            margin-bottom: 20px;
                            border-bottom: 2px solid #000;
                            padding-bottom: 10px;
                        }
                        
                        .print-logo {
                            max-height: 60px;
                            max-width: 200px;
                            margin-bottom: 10px;
                        }
                        
                        .print-section {
                            margin-bottom: 15px;
                            page-break-inside: avoid;
                        }
                        
                        .print-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 10px;
                        }
                        
                        .print-table th,
                        .print-table td {
                            border: 1px solid #000;
                            padding: 5px;
                            text-align: left;
                        }
                        
                        .print-table th {
                            background-color: #f0f0f0;
                            font-weight: bold;
                        }
                        
                        .text-right {
                            text-align: right;
                        }
                        
                        .font-bold {
                            font-weight: bold;
                        }
                    </style>
                </head>
                <body>
                    <div class="print-header">
                        @if($order->tenant->logo_path && file_exists(public_path('storage/' . $order->tenant->logo_path)))
                            <img src="{{ $order->tenant->logo_url }}" alt="Logo" class="print-logo">
                        @endif
                        <h1>PEDIDO #{{ $order->number }}</h1>
                        <p>Data: {{ $order->created_at->format('d/m/Y H:i') }}</p>
                        <p>Status: {{ 
                            $order->status === 'open' ? 'Aberto' : 
                            ($order->status === 'fulfilled' ? 'Finalizado' : 
                            ($order->status === 'canceled' ? 'Cancelado' : 
                            ucfirst($order->status))) 
                        }}</p>
                    </div>
                    
                    <div class="print-section">
                        <h3>Informações do Cliente</h3>
                        <p><strong>Nome:</strong> {{ $order->client->name ?? 'N/A' }}</p>
                        <p><strong>Documento:</strong> {{ $order->client->document ?? 'N/A' }}</p>
                        <p><strong>Email:</strong> {{ $order->client->email ?? 'N/A' }}</p>
                        <p><strong>Telefone:</strong> {{ $order->client->phone ?? 'N/A' }}</p>
                    </div>
                    
                    <div class="print-section">
                        <h3>Itens do Pedido</h3>
                        <table class="print-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qtd</th>
                                    <th>UN</th>
                                    <th>V.Unit</th>
                                    <th>Desc.</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ number_format($item->quantity, 3, ',', '.') }}</td>
                                    <td>{{ $item->unit }}</td>
                                    <td class="text-right">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                    <td class="text-right">R$ {{ number_format((float)($item->discount_value ?? 0), 2, ',', '.') }}</td>
                                    <td class="text-right font-bold">R$ {{ number_format((float)$item->line_total, 2, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="print-section">
                        <h3>Resumo Financeiro</h3>
                        <table class="print-table">
                            <tr>
                                <td>Subtotal:</td>
                                <td class="text-right">R$ {{ number_format($order->items->sum('line_total'), 2, ',', '.') }}</td>
                            </tr>
                            @php
                                $totalDiscountItems = $order->items->sum('discount_value');
                            @endphp
                            @if($totalDiscountItems > 0)
                            <tr>
                                <td>Desconto por Itens:</td>
                                <td class="text-right">- R$ {{ number_format($totalDiscountItems, 2, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($order->discount_total > 0)
                            <tr>
                                <td>Desconto Global:</td>
                                <td class="text-right">- R$ {{ number_format($order->discount_total, 2, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($order->addition_total > 0)
                            <tr>
                                <td>Acréscimo:</td>
                                <td class="text-right">+ R$ {{ number_format($order->addition_total, 2, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($order->freight_cost > 0)
                            <tr>
                                <td>Frete:</td>
                                <td class="text-right">R$ {{ number_format($order->freight_cost, 2, ',', '.') }}</td>
                            </tr>
                            @endif
                            <tr style="border-top: 2px solid #000;">
                                <td class="font-bold">TOTAL:</td>
                                <td class="text-right font-bold">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                    
                    @if($order->additional_info)
                    <div class="print-section">
                        <h3>Informações Adicionais</h3>
                        <p>{{ $order->additional_info }}</p>
                    </div>
                    @endif
                    
                    <div class="print-section">
                        <p><strong>Impresso em:</strong> {{ now()->format('d/m/Y H:i') }}</p>
                    </div>
                </body>
                </html>
            `;
            
            // Escrever o HTML na nova janela
            printWindow.document.write(printHTML);
            printWindow.document.close();
            
            // Aguardar o carregamento e imprimir
            printWindow.onload = function() {
                printWindow.print();
                printWindow.close();
            };
        }
    </script>
</x-app-layout>
