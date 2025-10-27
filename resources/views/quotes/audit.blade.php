<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Auditoria do Orçamento #{{ $quote->number }}</h2>
            <a href="{{ $quote->status === 'canceled' ? route('quotes.show', $quote) : route('quotes.edit', $quote) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar ao Orçamento
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Informações do Orçamento -->
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informações do Orçamento</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Número</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $quote->number }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Título</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $quote->title }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cliente</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $quote->client->name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <p class="mt-1 text-sm text-gray-900">
                            @php
                                $statusLabels = [
                                    'awaiting' => 'Aguardando',
                                    'approved' => 'Aprovado',
                                    'not_approved' => 'Reprovado',
                                    'canceled' => 'Cancelado',
                                    'expirado' => 'Expirado'
                                ];
                            @endphp
                            {{ $statusLabels[$quote->status] ?? $quote->status }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Valor Total</label>
                        <p class="mt-1 text-sm text-gray-900">R$ {{ number_format($quote->total_amount, 2, ',', '.') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data de Validade</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $quote->validity_date ? $quote->validity_date->format('d/m/Y') : 'Não definida' }}</p>
                    </div>
                </div>
            </div>

            <!-- Histórico de Auditoria -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Histórico de Auditoria</h3>
                
                @if($audits->count() > 0)
                    <div class="flow-root">
                        <ul class="-mb-8">
                            @foreach($audits as $index => $audit)
                                <li>
                                    <div class="relative pb-8">
                                        @if(!$loop->last)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                @php
                                                    $actionColors = [
                                                        'created' => 'bg-green-500',
                                                        'updated' => 'bg-blue-500',
                                                        'approved' => 'bg-green-600',
                                                        'rejected' => 'bg-red-500',
                                                        'canceled' => 'bg-red-600',
                                                        'converted' => 'bg-purple-500',
                                                        'notified' => 'bg-yellow-500'
                                                    ];
                                                    $actionIcons = [
                                                        'created' => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
                                                        'updated' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
                                                        'approved' => 'M5 13l4 4L19 7',
                                                        'rejected' => 'M6 18L18 6M6 6l12 12',
                                                        'canceled' => 'M6 18L18 6M6 6l12 12',
                                                        'converted' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                                                        'notified' => 'M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12.828 7H4.828z'
                                                    ];
                                                @endphp
                                                <div class="h-8 w-8 rounded-full {{ $actionColors[$audit->action] ?? 'bg-gray-500' }} flex items-center justify-center ring-8 ring-white">
                                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $actionIcons[$audit->action] ?? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' }}"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm text-gray-500">
                                                        <span class="font-medium text-gray-900">{{ $audit->user->name }}</span>
                                                        @php
                                                            $actionLabels = [
                                                                'created' => 'criou o orçamento',
                                                                'updated' => 'atualizou o orçamento',
                                                                'approved' => 'aprovou o orçamento',
                                                                'rejected' => 'reprovou o orçamento',
                                                                'canceled' => 'cancelou o orçamento',
                                                                'converted' => 'converteu o orçamento em pedido',
                                                                'notified' => 'notificou o cliente'
                                                            ];
                                                        @endphp
                                                        {{ $actionLabels[$audit->action] ?? $audit->action }}
                                                    </p>
                                                    @if($audit->notes)
                                                        <p class="text-sm text-gray-600 mt-1">{{ $audit->notes }}</p>
                                                    @endif
                                                </div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                    <time datetime="{{ $audit->created_at->toISOString() }}">
                                                        {{ $audit->created_at->format('d/m/Y H:i') }}
                                                    </time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma auditoria encontrada</h3>
                        <p class="mt-1 text-sm text-gray-500">Este orçamento ainda não possui histórico de auditoria.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
