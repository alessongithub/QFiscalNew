@extends('layouts.app')

@section('title', 'Finalizar OS #' . $serviceOrder->number)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Finalizar OS</h1>
                <p class="text-gray-600 mt-1">OS #{{ $serviceOrder->number }} - {{ $serviceOrder->title }}</p>
            </div>
            <a href="{{ route('service_orders.show', $serviceOrder) }}" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar
            </a>
        </div>
    </div>

    <!-- Informações da OS -->
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Informações da OS</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-600">Cliente:</span>
                <span class="text-gray-800">{{ $serviceOrder->client->name }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">Equipamento:</span>
                <span class="text-gray-800">{{ $serviceOrder->equipment_brand }} {{ $serviceOrder->equipment_model }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">Técnico:</span>
                <span class="text-gray-800">{{ $serviceOrder->technician->name ?? 'Não definido' }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">Valor Orçado:</span>
                <span class="text-gray-800 font-semibold">R$ {{ number_format($serviceOrder->budget_amount ?? 0, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Formulário de Finalização -->
    <form action="{{ route('service_orders.finalize', $serviceOrder) }}" method="POST" class="space-y-6">
        @csrf
        
        <!-- Data e Observações -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Dados de Finalização</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="finalization_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Data de Finalização <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           id="finalization_date" 
                           name="finalization_date" 
                           value="{{ old('finalization_date', date('Y-m-d')) }}"
                           min="{{ $serviceOrder->created_at->format('Y-m-d') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('finalization_date') border-red-500 @enderror"
                           required>
                    @error('finalization_date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="delivery_method" class="block text-sm font-medium text-gray-700 mb-2">
                        Método de Entrega <span class="text-red-500">*</span>
                    </label>
                    <select id="delivery_method" 
                            name="delivery_method" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('delivery_method') border-red-500 @enderror"
                            required>
                        <option value="">Selecione...</option>
                        <option value="pickup" {{ old('delivery_method') === 'pickup' ? 'selected' : '' }}>Retirada pelo Cliente</option>
                        <option value="delivery" {{ old('delivery_method') === 'delivery' ? 'selected' : '' }}>Entrega</option>
                        <option value="shipping" {{ old('delivery_method') === 'shipping' ? 'selected' : '' }}>Envio por Transportadora</option>
                    </select>
                    @error('delivery_method')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            
            <div class="mt-4">
                <label for="finalization_notes" class="block text-sm font-medium text-gray-700 mb-2">
                    Observações da Finalização
                </label>
                <textarea id="finalization_notes" 
                          name="finalization_notes" 
                          rows="3" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('finalization_notes') border-red-500 @enderror"
                          placeholder="Observações sobre a finalização do serviço...">{{ old('finalization_notes') }}</textarea>
                @error('finalization_notes')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Entrega -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Dados da Entrega</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="delivered_by" class="block text-sm font-medium text-gray-700 mb-2">
                        Entregado por
                    </label>
                    <select id="delivered_by" 
                            name="delivered_by" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('delivered_by') border-red-500 @enderror">
                        <option value="">Selecione...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('delivered_by') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('delivered_by')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="equipment_condition" class="block text-sm font-medium text-gray-700 mb-2">
                        Condição do Equipamento <span class="text-red-500">*</span>
                    </label>
                    <select id="equipment_condition" 
                            name="equipment_condition" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('equipment_condition') border-red-500 @enderror"
                            required>
                        <option value="">Selecione...</option>
                        <option value="perfect" {{ old('equipment_condition') === 'perfect' ? 'selected' : '' }}>Perfeito</option>
                        <option value="good" {{ old('equipment_condition') === 'good' ? 'selected' : '' }}>Bom</option>
                        <option value="damaged" {{ old('equipment_condition') === 'damaged' ? 'selected' : '' }}>Danificado</option>
                    </select>
                    @error('equipment_condition')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            
            <div class="mt-4">
                <label for="accessories_included" class="block text-sm font-medium text-gray-700 mb-2">
                    Acessórios Inclusos
                </label>
                <textarea id="accessories_included" 
                          name="accessories_included" 
                          rows="2" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('accessories_included') border-red-500 @enderror"
                          placeholder="Liste os acessórios que foram entregues junto com o equipamento...">{{ old('accessories_included') }}</textarea>
                @error('accessories_included')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mt-4">
                <label for="client_signature" class="block text-sm font-medium text-gray-700 mb-2">
                    Assinatura do Cliente
                </label>
                <textarea id="client_signature" 
                          name="client_signature" 
                          rows="2" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('client_signature') border-red-500 @enderror"
                          placeholder="Nome do cliente que recebeu o equipamento...">{{ old('client_signature') }}</textarea>
                @error('client_signature')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Pagamento -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Dados do Pagamento</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="final_amount" class="block text-sm font-medium text-gray-700 mb-2">
                        Valor Final <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="final_amount" 
                           name="final_amount" 
                           step="0.01" 
                           min="0"
                           value="{{ old('final_amount', $serviceOrder->budget_amount ?? 0) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('final_amount') border-red-500 @enderror"
                           required>
                    @error('final_amount')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">
                        Método de Pagamento <span class="text-red-500">*</span>
                    </label>
                    <select id="payment_method" 
                            name="payment_method" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('payment_method') border-red-500 @enderror"
                            required>
                        <option value="">Selecione...</option>
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Dinheiro</option>
                        <option value="card" {{ old('payment_method') === 'card' ? 'selected' : '' }}>Cartão</option>
                        <option value="pix" {{ old('payment_method') === 'pix' ? 'selected' : '' }}>PIX</option>
                        <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>Transferência</option>
                        <option value="boleto" {{ old('payment_method') === 'boleto' ? 'selected' : '' }}>Boleto Bancário</option>
                        <option value="mixed" {{ old('payment_method') === 'mixed' ? 'selected' : '' }}>Pagamento Misto</option>
                    </select>
                    @error('payment_method')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Campos condicionais baseados no método de pagamento -->
                <div id="card-fields" class="hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="installments" class="block text-sm font-medium text-gray-700 mb-2">
                                Número de Parcelas
                            </label>
                            <select id="installments" 
                                    name="installments" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('installments') border-red-500 @enderror">
                                <option value="1" {{ old('installments') == '1' ? 'selected' : '' }}>À vista</option>
                                <option value="2" {{ old('installments') == '2' ? 'selected' : '' }}>2x</option>
                                <option value="3" {{ old('installments') == '3' ? 'selected' : '' }}>3x</option>
                                <option value="4" {{ old('installments') == '4' ? 'selected' : '' }}>4x</option>
                                <option value="5" {{ old('installments') == '5' ? 'selected' : '' }}>5x</option>
                                <option value="6" {{ old('installments') == '6' ? 'selected' : '' }}>6x</option>
                                <option value="12" {{ old('installments') == '12' ? 'selected' : '' }}>12x</option>
                            </select>
                            @error('installments')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div id="mixed-fields" class="hidden">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="entry_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Valor da Entrada
                            </label>
                            <input type="number" 
                                   id="entry_amount" 
                                   name="entry_amount" 
                                   step="0.01" 
                                   min="0"
                                   value="{{ old('entry_amount') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('entry_amount') border-red-500 @enderror">
                            @error('entry_amount')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="entry_method" class="block text-sm font-medium text-gray-700 mb-2">
                                Método da Entrada
                            </label>
                            <select id="entry_method" 
                                    name="entry_method" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('entry_method') border-red-500 @enderror">
                                <option value="cash" {{ old('entry_method') === 'cash' ? 'selected' : '' }}>Dinheiro</option>
                                <option value="pix" {{ old('entry_method') === 'pix' ? 'selected' : '' }}>PIX</option>
                            </select>
                            @error('entry_method')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="mixed_installments" class="block text-sm font-medium text-gray-700 mb-2">
                                Parcelas do Restante
                            </label>
                            <select id="mixed_installments" 
                                    name="installments" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('installments') border-red-500 @enderror">
                                <option value="1" {{ old('installments') == '1' ? 'selected' : '' }}>À vista</option>
                                <option value="2" {{ old('installments') == '2' ? 'selected' : '' }}>2x</option>
                                <option value="3" {{ old('installments') == '3' ? 'selected' : '' }}>3x</option>
                                <option value="4" {{ old('installments') == '4' ? 'selected' : '' }}>4x</option>
                                <option value="5" {{ old('installments') == '5' ? 'selected' : '' }}>5x</option>
                                <option value="6" {{ old('installments') == '6' ? 'selected' : '' }}>6x</option>
                                <option value="12" {{ old('installments') == '12' ? 'selected' : '' }}>12x</option>
                            </select>
                            @error('installments')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <div class="mt-6">
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="payment_received" 
                                   value="on"
                                   {{ old('payment_received') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm font-medium text-gray-700">Pagamento Recebido</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('service_orders.show', $serviceOrder) }}" 
               class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Finalizar OS
            </button>
        </div>
    </form>
</div>

<!-- Toast Container -->
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

<script>
function showToast(message, type = 'success') {
    const toastId = 'toast-' + Date.now();
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    const icon = type === 'success' ? 'M5 13l4 4L19 7' : type === 'error' ? 'M6 18L18 6M6 6l12 12' : 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
    
    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3 min-w-80 max-w-md`;
    toast.innerHTML = `
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${icon}"/>
        </svg>
        <span class="flex-1">${message}</span>
        <button onclick="hideToast('${toastId}')" class="flex-shrink-0 ml-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    `;
    
    document.getElementById('toast-container').appendChild(toast);
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        hideToast(toastId);
    }, 5000);
}

function hideToast(toastId) {
    const toast = document.getElementById(toastId);
    if (toast) {
        toast.remove();
    }
}

// Show session messages
document.addEventListener('DOMContentLoaded', function() {
    @if(session('success'))
        showToast('{{ session('success') }}', 'success');
    @endif
    
    @if(session('error'))
        showToast('{{ session('error') }}', 'error');
    @endif
    
    // Controle dos campos condicionais de pagamento
    function togglePaymentFields() {
        const paymentMethod = document.getElementById('payment_method').value;
        const cardFields = document.getElementById('card-fields');
        const mixedFields = document.getElementById('mixed-fields');
        
        // Esconder todos os campos condicionais
        cardFields.classList.add('hidden');
        mixedFields.classList.add('hidden');
        
        // Mostrar campos baseados no método selecionado
        if (paymentMethod === 'card') {
            cardFields.classList.remove('hidden');
        } else if (paymentMethod === 'mixed') {
            mixedFields.classList.remove('hidden');
        }
    }
    
    // Adicionar listener ao select de método de pagamento
    document.getElementById('payment_method').addEventListener('change', togglePaymentFields);
    
    // Executar na carga da página para manter estado
    togglePaymentFields();
});
</script>
@endsection