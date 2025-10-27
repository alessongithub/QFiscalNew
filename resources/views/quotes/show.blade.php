<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Visualizar Orçamento #{{ $quote->number }}
            </h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('quotes.print', $quote) }}" target="_blank" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimir
                </a>
                @if(auth()->user()->is_admin || auth()->user()->hasPermission('quotes.audit'))
                <a href="{{ route('quotes.audit', $quote) }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Auditoria
                </a>
                @endif
                <a href="{{ route('quotes.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if($quote->status === 'canceled')
            <!-- Aviso de Cancelamento -->
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <div>
                        <h3 class="text-lg font-semibold text-red-800">Orçamento Cancelado</h3>
                        <p class="text-red-700">Este orçamento foi cancelado e não possui mais validade legal. As informações são mantidas apenas para fins de auditoria e controle interno.</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Informações do Orçamento
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Número</label>
                            <p class="mt-1 text-sm text-gray-900 font-mono bg-gray-100 px-3 py-2 rounded">#{{ $quote->number }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <p class="mt-1">
                                @switch($quote->status)
                                    @case('awaiting')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Aguardando
                                        </span>
                                        @break
                                    @case('approved')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Aprovado
                                        </span>
                                        @break
                                    @case('canceled')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Cancelado
                                        </span>
                                        @break
                                @endswitch
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cliente</label>
                            <p class="mt-1 text-sm text-gray-900">{{ optional($quote->client)->name ?? 'Cliente não informado' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Título</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $quote->title }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Valor Total</label>
                            <p class="mt-1 text-sm font-medium text-gray-900">R$ {{ number_format($quote->total_amount, 2, ',', '.') }}</p>
                        </div>
                        @if($quote->discount_total > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Desconto Total</label>
                            <p class="mt-1 text-sm font-medium text-red-600">- R$ {{ number_format($quote->discount_total, 2, ',', '.') }}</p>
                        </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Data de Criação</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $quote->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        @if($quote->validity_date)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Validade</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $quote->validity_date->format('d/m/Y') }}</p>
                        </div>
                        @endif
                    </div>

                    @if($quote->status === 'approved')
                    <!-- Log de Aprovação -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-xl mb-6 mt-6">
                        <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
                            <h3 class="text-lg font-semibold text-gray-900">Log de Aprovação</h3>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Aprovado em</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ optional($quote->approved_at)->format('d/m/Y H:i') ?? 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Aprovado por</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $quote->approved_by ?? 'Usuário não informado' }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Motivo da Aprovação</label>
                                <div class="mt-1 p-3 bg-green-50 border border-green-200 rounded-lg">
                                    <p class="text-sm text-green-800">{{ $quote->approval_reason ?? 'Motivo não informado' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($quote->status === 'canceled')
                    <!-- Log de Cancelamento -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-xl mb-6 mt-6">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Log de Cancelamento</h3>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Cancelado em</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ optional($quote->canceled_at)->format('d/m/Y H:i') ?? 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Cancelado por</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $quote->canceled_by ?? 'Usuário não informado' }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Motivo do Cancelamento</label>
                                <div class="mt-1 p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <p class="text-sm text-red-800">{{ $quote->cancel_reason ?? 'Motivo não informado' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($quote->items && $quote->items->count() > 0)
                    <!-- Itens do Orçamento -->
                    <div class="mt-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Itens do Orçamento</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preço Unitário</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Desconto</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($quote->items as $item)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ (float)$item->quantity }} {{ $item->unit }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @if($item->discount_value > 0)
                                                <span class="text-red-600">- R$ {{ number_format($item->discount_value, 2, ',', '.') }}</span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">R$ {{ number_format($item->line_total, 2, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if($quote->notes)
                    <!-- Observações -->
                    <div class="mt-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-2">Observações</h4>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-700">{{ $quote->notes }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
