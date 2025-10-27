<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/>
                </svg>
                Conta a Pagar #{{ $payable->id }}
            </h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('payables.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Voltar
                </a>
                @if(auth()->user()->hasPermission('payables.edit') && $payable->status !== 'paid')
                    <a href="{{ route('payables.edit', $payable) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Informações Principais -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Dados da Conta a Pagar -->
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-red-50 to-red-100 px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Dados da Conta a Pagar
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ID</label>
                                    <div class="text-lg font-semibold text-gray-900">#{{ $payable->id }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                        @if($payable->status === 'open') bg-yellow-100 text-yellow-800
                                        @elseif($payable->status === 'paid') bg-green-100 text-green-800
                                        @elseif($payable->status === 'canceled') bg-red-100 text-red-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        @switch($payable->status)
                                            @case('open') Em aberto @break
                                            @case('paid') Pago @break
                                @case('canceled') Cancelado @break
                                @case('reversed') Estornado @break
                                @default {{ ucfirst($payable->status) }} @break
                                        @endswitch
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Fornecedor</label>
                                    <div class="text-gray-900">{{ $payable->supplier_name }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
                                    <div class="text-lg font-semibold text-gray-900">R$ {{ number_format($payable->amount, 2, ',', '.') }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                                    <div class="text-gray-900">{{ $payable->description }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Vencimento</label>
                                    <div class="text-gray-900">{{ $payable->due_date->format('d/m/Y') }}</div>
                                </div>
                                @if($payable->document_number)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Número do Documento</label>
                                    <div class="text-gray-900">{{ $payable->document_number }}</div>
                                </div>
                                @endif
                                @if($payable->payment_method)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Forma de Pagamento</label>
                                    <div class="text-gray-900">
                                        @switch($payable->payment_method)
                                            @case('cash') Dinheiro @break
                                            @case('card') Cartão @break
                                            @case('pix') PIX @break
                                            @default {{ ucfirst($payable->payment_method) }} @break
                                        @endswitch
                                    </div>
                                </div>
                                @endif
                                @if($payable->paid_at)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Data do Pagamento</label>
                                    <div class="text-gray-900">{{ $payable->paid_at->format('d/m/Y H:i') }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar com Auditoria -->
                <div class="space-y-6">
                    
                    <!-- Informações de Auditoria -->
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Auditoria
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Criado em</label>
                                <div class="text-gray-900">{{ $payable->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                            @if($payable->createdBy)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Criado por</label>
                                <div class="text-gray-900">{{ $payable->createdBy->name }}</div>
                            </div>
                            @endif
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Última atualização</label>
                                <div class="text-gray-900">{{ $payable->updated_at->format('d/m/Y H:i') }}</div>
                            </div>
                            @if($payable->updatedBy)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Atualizado por</label>
                                <div class="text-gray-900">{{ $payable->updatedBy->name }}</div>
                            </div>
                            @endif
                            
                            @if($payable->paid_at)
                            <div class="border-t pt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pagamento realizado em</label>
                                <div class="text-gray-900">{{ $payable->paid_at->format('d/m/Y H:i') }}</div>
                            </div>
                            @if($payable->paidBy)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pago por</label>
                                <div class="text-gray-900">{{ $payable->paidBy->name }}</div>
                            </div>
                            @endif
                            @endif
                            
                            @if($payable->reversed_at)
                            <div class="border-t pt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estorno realizado em</label>
                                <div class="text-gray-900">{{ $payable->reversed_at->format('d/m/Y H:i') }}</div>
                            </div>
                            @if($payable->reversedBy)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estornado por</label>
                                <div class="text-gray-900">{{ $payable->reversedBy->name }}</div>
                            </div>
                            @endif
                            @if($payable->reverse_reason)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Motivo do estorno</label>
                                <div class="text-gray-900 bg-gray-50 p-2 rounded text-sm">{{ $payable->reverse_reason }}</div>
                            </div>
                            @endif
                            @endif
                            
                            @if($payable->canceled_at)
                            <div class="border-t pt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cancelado em</label>
                                <div class="text-gray-900">{{ $payable->canceled_at->format('d/m/Y H:i') }}</div>
                            </div>
                            @if($payable->canceledBy)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cancelado por</label>
                                <div class="text-gray-900">{{ $payable->canceledBy->name }}</div>
                            </div>
                            @endif
                            @if($payable->cancel_reason)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Motivo do cancelamento</label>
                                <div class="text-gray-900 bg-gray-50 p-2 rounded text-sm">{{ $payable->cancel_reason }}</div>
                            </div>
                            @endif
                            @endif
                        </div>
                    </div>

                    <!-- Ações Rápidas -->
                    @if($payable->status === 'open' && auth()->user()->hasPermission('payables.pay'))
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-green-50 to-green-100 px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/>
                                </svg>
                                Ações
                            </h3>
                        </div>
                        <div class="p-6">
                            <form action="{{ route('payables.pay', $payable) }}" method="POST" class="mb-4" onsubmit="return confirmPaymentShow()">
                                @csrf
                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Marcar como Pago
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmPaymentShow() {
            const supplier = '{{ $payable->supplier_name }}';
            const amount = {{ $payable->amount }};
            const formattedAmount = new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(amount);
            
            return confirm(`Confirma o pagamento desta conta?\n\nFornecedor: ${supplier}\nValor: ${formattedAmount}\n\nEsta ação será registrada na auditoria.`);
        }
    </script>
</x-app-layout>
