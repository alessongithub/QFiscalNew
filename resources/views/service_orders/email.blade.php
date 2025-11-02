<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Enviar e-mail - OS #{{ $serviceOrder->number }}</h2>
            <div class="flex space-x-2">
                <a href="{{ route('service_orders.show', $serviceOrder) }}" class="px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Ver OS
                </a>
                <a href="{{ route('service_orders.index') }}" class="px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <!-- Informações da OS -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Informações da Ordem de Serviço</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="font-medium text-gray-600">Cliente:</span>
                    <span class="text-gray-800">{{ $client->name }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-600">Email do Cliente:</span>
                    <span class="text-gray-800">
                        @if($client->email)
                            {{ $client->email }}
                            @if(!filter_var($client->email, FILTER_VALIDATE_EMAIL))
                                <span class="text-red-500 text-xs ml-1">(Email inválido)</span>
                            @endif
                        @else
                            <span class="text-red-500">Não cadastrado</span>
                        @endif
                    </span>
                </div>
                <div>
                    <span class="font-medium text-gray-600">Título:</span>
                    <span class="text-gray-800">{{ $serviceOrder->title }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-600">Status:</span>
                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                        {{ ['open'=>'Em análise','in_progress'=>'Orçada','in_service'=>'Em andamento','service_finished'=>'Serviço Finalizado','warranty'=>'Garantia','no_repair'=>'Sem reparo','finished'=>'Finalizada','canceled'=>'Cancelada'][$serviceOrder->status] ?? $serviceOrder->status }}
                    </span>
                </div>
            </div>
        </div>

        @if($serviceOrder->status === 'canceled')
        <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-yellow-900">
                <div class="font-semibold">OS Cancelada</div>
                <div class="text-sm mt-1">Esta ordem de serviço foi cancelada. Considere explicar o motivo do cancelamento na mensagem para o cliente.</div>
            </div>
        </div>
        @endif

        <!-- Formulário de Email -->
        <div class="bg-white p-6 rounded-lg shadow">
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h4 class="text-red-800 font-medium">Erro ao enviar email</h4>
                    </div>
                    <ul class="list-disc pl-5 text-red-700">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('service_orders.email_send', $serviceOrder) }}" class="space-y-6">
                @csrf
                
                <!-- Email de Destino -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email de Destino <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="email" 
                        name="to" 
                        value="{{ old('to', $client->email) }}" 
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('to') border-red-500 @enderror" 
                        placeholder="exemplo@email.com"
                        required
                    >
                    @error('to')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Template -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Template</label>
                    <select name="template" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Personalizado</option>
                        <option value="approval_request" @selected(old('template')==='approval_request')>Solicitar Aprovação (Orçamento)</option>
                        <option value="ready_for_pickup" @selected(old('template')==='ready_for_pickup')>Pronto para retirada</option>
                        @if($serviceOrder->status === 'canceled')
                        <option value="cancellation" @selected(old('template')==='cancellation')>Cancelamento de OS</option>
                        @endif
                    </select>
                    <p class="mt-1 text-sm text-gray-500">Escolha um template ou deixe em branco para mensagem personalizada</p>
                </div>

                <!-- Assunto -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Assunto <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="subject" 
                        value="{{ old('subject') }}" 
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('subject') border-red-500 @enderror" 
                        placeholder="Assunto do email"
                        required
                    >
                    @error('subject')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Mensagem -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mensagem (HTML)</label>
                    <textarea 
                        name="body" 
                        rows="10" 
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                        placeholder="Escreva sua mensagem ou escolha um template acima..."
                    >{{ old('body') }}</textarea>
                    <p class="mt-1 text-sm text-gray-500">Você pode usar HTML para formatação. Se escolher um template, esta mensagem será substituída.</p>
                </div>

                <!-- Botões -->
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('service_orders.show', $serviceOrder) }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Enviar Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>


