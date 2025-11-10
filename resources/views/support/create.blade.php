<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Contato com Suporte') }}
        </h2>
    </x-slot>

    <div class="py-8 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <h1 class="text-3xl font-bold text-white mb-2">Entre em Contato</h1>
                    <p class="text-blue-100">Preencha o formulário abaixo e nossa equipe responderá o mais breve possível</p>
                </div>

                <div class="p-8">
                    @if(session('error'))
                        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                {{ session('error') }}
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('support.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Categoria <span class="text-red-500">*</span>
                                </label>
                                <select name="category" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Selecione uma categoria</option>
                                    <option value="technical" {{ old('category') === 'technical' ? 'selected' : '' }}>Suporte Técnico</option>
                                    <option value="billing" {{ old('category') === 'billing' ? 'selected' : '' }}>Faturamento/Assinatura</option>
                                    <option value="feature" {{ old('category') === 'feature' ? 'selected' : '' }}>Sugestão de Funcionalidade</option>
                                    <option value="account" {{ old('category') === 'account' ? 'selected' : '' }}>Conta/Perfil</option>
                                    <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Outro</option>
                                </select>
                                @error('category')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Prioridade <span class="text-red-500">*</span>
                                </label>
                                <select name="priority" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Selecione a prioridade</option>
                                    <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Baixa</option>
                                    <option value="medium" {{ old('priority') === 'medium' ? 'selected' : '' }}>Média</option>
                                    <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>Alta</option>
                                    <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgente</option>
                                </select>
                                @error('priority')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Assunto <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="subject" value="{{ old('subject') }}" required 
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Ex: Problema ao emitir nota fiscal">
                            @error('subject')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Mensagem <span class="text-red-500">*</span>
                            </label>
                            <textarea name="message" rows="6" required 
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Descreva detalhadamente sua dúvida, problema ou sugestão...">{{ old('message') }}</textarea>
                            <p class="mt-1 text-sm text-gray-500">Mínimo de 10 caracteres</p>
                            @error('message')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Telefone (Opcional)
                            </label>
                            <input type="text" name="phone" value="{{ old('phone') }}" 
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="(00) 00000-0000">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <strong>Informações automáticas:</strong> Seu Tenant ID e dados da empresa serão enviados automaticamente junto com sua mensagem.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-4">
                            <a href="{{ route('plans.upgrade') }}" class="text-gray-600 hover:text-gray-900 font-medium">
                                ← Voltar para planos
                            </a>
                            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                    Enviar Mensagem
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

