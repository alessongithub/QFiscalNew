<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Visualizar Recibo Cancelado</h2>
                <p class="text-sm text-gray-600 mt-1">Recibo #{{ $receipt->number }}</p>
            </div>
            <div class="flex items-center space-x-2">
                <div class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">
                    Cancelado
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-lg rounded-xl">
                <!-- Header com informações do recibo -->
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Recibo #{{ $receipt->number }}</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ $receipt->description }}</p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900">R$ {{ number_format($receipt->amount, 2, ',', '.') }}</div>
                            <div class="text-sm text-gray-500">Valor do Recibo</div>
                        </div>
                    </div>
                </div>

                <!-- Informações principais -->
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Informações do Cliente -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Informações do Cliente</h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ optional($receipt->client)->name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Data de Emissão</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ optional($receipt->issue_date)->format('d/m/Y') ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Status</label>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Cancelado
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Log de Cancelamento -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Log de Cancelamento</h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Cancelado em</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ optional($receipt->canceled_at)->format('d/m/Y H:i') ?? 'N/A' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Cancelado por</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ $receipt->canceled_by ?? 'Usuário não informado' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Motivo do Cancelamento</label>
                                    <div class="mt-1 p-3 bg-red-50 border border-red-200 rounded-lg">
                                        <p class="text-sm text-red-800">{{ $receipt->cancel_reason ?? 'Motivo não informado' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Observações -->
                    @if($receipt->notes)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-lg font-medium text-gray-900 mb-3">Observações</h4>
                        <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg">
                            <p class="text-sm text-gray-700">{{ $receipt->notes }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Aviso de Cancelamento -->
                    <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Recibo Cancelado</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>Este recibo foi cancelado e não possui mais validade legal. As informações são mantidas apenas para fins de auditoria e controle interno.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rodapé com ações -->
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            <p>Este recibo foi cancelado e não pode ser editado ou impresso.</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('receipts.index') }}" 
                               class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition duration-200 font-medium">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Voltar para Lista
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
