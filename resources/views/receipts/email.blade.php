<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Enviar Recibo por E-mail</h2>
            <div class="flex space-x-2">
                <a href="{{ route('receipts.show', $receipt) }}" class="px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Ver Recibo
                </a>
                <a href="{{ route('receipts.index') }}" class="px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto">
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

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <!-- Informações do Recibo -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Informações do Recibo</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="font-medium text-gray-600">Número:</span>
                    <span class="text-gray-800 ml-2">{{ $receipt->number }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-600">Cliente:</span>
                    <span class="text-gray-800 ml-2">{{ optional($receipt->client)->name ?? '—' }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-600">Email do Cliente:</span>
                    <span class="text-gray-800 ml-2">
                        @if(optional($receipt->client)->email)
                            {{ $receipt->client->email }}
                        @else
                            <span class="text-red-500">Não cadastrado</span>
                        @endif
                    </span>
                </div>
                <div>
                    <span class="font-medium text-gray-600">Data de Emissão:</span>
                    <span class="text-gray-800 ml-2">{{ optional($receipt->issue_date)->format('d/m/Y') ?? '—' }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-600">Valor:</span>
                    <span class="text-gray-800 ml-2 font-semibold">R$ {{ number_format($receipt->amount, 2, ',', '.') }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-600">Status:</span>
                    <span class="ml-2 px-2 py-1 rounded text-white text-xs {{ $receipt->status==='issued' ? 'bg-green-600' : 'bg-gray-600' }}">
                        {{ $receipt->status==='issued'?'Emitido':'Cancelado' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Formulário de Email -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuração do E-mail</h3>
            
            <form action="{{ route('receipts.email_send', $receipt) }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Email de Destino -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email de Destino <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="email" 
                        name="to" 
                        value="{{ old('to', $to) }}" 
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('to') border-red-500 @enderror" 
                        placeholder="exemplo@email.com"
                        required
                    >
                    @error('to')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Assunto -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Assunto <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="subject" 
                        value="{{ old('subject', $subject) }}" 
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
                        name="message" 
                        rows="10" 
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                        placeholder="Deixe em branco para usar o template padrão ou escreva sua mensagem personalizada..."
                    >{{ old('message') }}</textarea>
                    <p class="mt-1 text-sm text-gray-500">Se deixar em branco, será usado o template padrão do recibo.</p>
                </div>

                <!-- Aviso sobre PDF -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-blue-900">
                            <div class="font-semibold">PDF Anexado</div>
                            <div class="text-sm mt-1">O PDF do recibo será anexado automaticamente ao email.</div>
                        </div>
                    </div>
                </div>

                <!-- Botões -->
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('receipts.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
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






