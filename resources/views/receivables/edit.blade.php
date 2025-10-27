<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Conta a Receber</h2>
                    <p class="text-sm text-gray-500">Modifique os dados da receita</p>
                </div>
            </div>
            <a href="{{ route('receivables.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                <!-- Header do Card -->
                <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                </div>
                <div>
                            <h3 class="text-lg font-semibold text-white">Editar Informações da Conta a Receber</h3>
                            <p class="text-green-100 text-sm">Modifique os dados conforme necessário</p>
                        </div>
                    </div>
                </div>

                <!-- Formulário -->
                <form action="{{ route('receivables.update', $receivable) }}" method="POST" class="p-6 space-y-6" x-data='receivableForm()'>
                    @csrf @method('PUT')
                    
                    <!-- Seção Cliente -->
                    <div class="border-b border-gray-200 pb-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Cliente</h3>
            </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        Cliente Cadastrado
                                    </span>
                                </label>
                                <select name="client_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                                    <option value="">— Selecione um cliente —</option>
                                    @foreach($clients as $c)
                                        <option value="{{ $c->id }}" @selected(old('client_id', $receivable->client_id)==$c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Número do Documento
                                    </span>
                                </label>
                                <input type="text" name="document_number" value="{{ old('document_number', $receivable->document_number) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors" placeholder="Ex.: 12345">
                            </div>
                        </div>
                    </div>

                    <!-- Seção Informações Gerais -->
                    <div class="border-b border-gray-200 pb-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Informações Gerais</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Descrição
                                        <span class="text-red-500 ml-1">*</span>
                                    </span>
                                </label>
                                <input type="text" name="description" value="{{ old('description', $receivable->description) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors" placeholder="Ex.: Venda de produtos" required>
                            </div>
                            
                <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                        Forma de Pagamento
                                    </span>
                                </label>
                                <select name="payment_method" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                                    <option value="">— Selecione —</option>
                                    <option value="cash" @selected(old('payment_method', $receivable->payment_method)==='cash')>Dinheiro</option>
                                    <option value="pix" @selected(old('payment_method', $receivable->payment_method)==='pix')>PIX</option>
                                    <option value="card" @selected(old('payment_method', $receivable->payment_method)==='card')>Cartão</option>
                                    <option value="boleto" @selected(old('payment_method', $receivable->payment_method)==='boleto')>Boleto</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Seção Valores e Datas -->
                    <div class="border-b border-gray-200 pb-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Valores e Datas</h3>
                </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/>
                                        </svg>
                                        Valor
                                        <span class="text-red-500 ml-1">*</span>
                                    </span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">R$</span>
                                    </div>
                                    <input type="number" step="0.01" name="amount" value="{{ old('amount', $receivable->amount) }}" class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors" placeholder="0,00" required>
                </div>
            </div>
                            
                <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Data de Vencimento
                                        <span class="text-red-500 ml-1">*</span>
                                    </span>
                                </label>
                                <input type="date" name="due_date" x-model="dueDate" @change="checkDueDate()" value="{{ old('due_date', $receivable->due_date->format('Y-m-d')) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors" required>
                                
                                <!-- Aviso para datas passadas -->
                                <div x-show="isOverdue" x-transition class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                        <div class="text-sm">
                                            <p class="text-yellow-800 font-medium">Conta vencida</p>
                                            <p class="text-yellow-700">Esta conta já venceu. Será marcada como em atraso.</p>
                                        </div>
                                    </div>
                                </div>
                </div>
                            
                <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Status
                                    </span>
                                </label>
                                <select name="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                                    @foreach(['open' => 'Em aberto','partial' => 'Parcial','paid' => 'Pago','canceled' => 'Cancelado','reversed' => 'Estornado'] as $k => $v)
                                        <option value="{{ $k }}" @selected(old('status', $receivable->status) === $k)>{{ $v }}</option>
                                    @endforeach
                                </select>
                </div>
            </div>
                        
                        @if($receivable->status === 'paid' || old('status') === 'paid')
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Data do Recebimento
                                </span>
                            </label>
                            <input type="datetime-local" name="received_at" value="{{ old('received_at', $receivable->received_at ? $receivable->received_at->format('Y-m-d\TH:i') : '') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                        </div>
                        @endif
                    </div>

                    <!-- Botões de Ação -->
                    <div class="flex items-center justify-end space-x-4 pt-6">
                        <a href="{{ route('receivables.index') }}" class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancelar
                        </a>
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function receivableForm() {
            return {
                dueDate: '{{ old("due_date", $receivable->due_date->format("Y-m-d")) }}',
                isOverdue: false,
                
                init() {
                    // Verifica se há data preenchida no carregamento
                    if (this.dueDate) {
                        this.checkDueDate();
                    }
                },
                
                checkDueDate() {
                    if (this.dueDate) {
                        const today = new Date().toISOString().split('T')[0];
                        this.isOverdue = this.dueDate < today;
                    } else {
                        this.isOverdue = false;
                    }
                }
            }
        }
    </script>
</x-app-layout>