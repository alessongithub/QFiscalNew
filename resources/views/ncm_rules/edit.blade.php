<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <i class="fas fa-edit mr-2"></i>
                Editar Regra NCM → GTIN
            </h2>
            <a href="{{ route('ncm_rules.index') }}" class="text-gray-600 hover:text-gray-800 transition duration-150 ease-in-out">
                <i class="fas fa-arrow-left mr-1"></i>Voltar à lista
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Card principal -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <!-- Header do formulário -->
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Editar Regra</h3>
                    <p class="mt-1 text-sm text-gray-600">Modifique as configurações da regra NCM {{ $rule->ncm }}</p>
                </div>

                <!-- Formulário -->
                <div class="px-6 py-6">
                    <form method="POST" action="{{ route('ncm_rules.update', $rule) }}" class="space-y-6">
                        @csrf @method('PUT')
                        
                        <!-- Campo NCM -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-hashtag mr-1"></i>
                                NCM <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="text" 
                                       name="ncm" 
                                       value="{{ old('ncm', $rule->ncm) }}" 
                                       class="w-full border border-gray-300 rounded-lg px-4 py-3 font-mono text-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('ncm') border-red-500 @enderror" 
                                       placeholder="Ex: 24.01.10.00 ou 24011000" 
                                       maxlength="20"
                                       pattern="[0-9\.]{4,20}"
                                       required>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <i class="fas fa-hashtag text-gray-400"></i>
                                </div>
                            </div>
                            @error('ncm')
                                <div class="mt-2 flex items-center text-red-600 text-sm">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                            <p class="mt-2 text-sm text-gray-500">Aceita com ou sem pontos; será salvo apenas com dígitos</p>
                        </div>

                        <!-- Campo Requer GTIN -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-start space-x-3">
                                <div class="flex items-center h-5">
                                    <input type="hidden" name="requires_gtin" value="0">
                                    <input type="checkbox" 
                                           name="requires_gtin" 
                                           value="1" 
                                           id="requires_gtin"
                                           class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" 
                                           @checked(old('requires_gtin', $rule->requires_gtin))>
                                </div>
                                <div class="text-sm">
                                    <label for="requires_gtin" class="font-medium text-gray-700 cursor-pointer">
                                        <i class="fas fa-barcode mr-1"></i>
                                        Este NCM exige código GTIN
                                    </label>
                                    <p class="text-gray-500 mt-1">
                                        Quando marcado, produtos com este NCM serão obrigados a ter um código GTIN válido
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Campo Observação -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-sticky-note mr-1"></i>
                                Observação
                            </label>
                            <textarea name="note" 
                                      rows="3"
                                      class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('note') border-red-500 @enderror" 
                                      placeholder="Ex: Grupo I — Tabaco, Produtos farmacêuticos, etc.">{{ old('note', $rule->note) }}</textarea>
                            @error('note')
                                <div class="mt-2 flex items-center text-red-600 text-sm">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                            <p class="mt-2 text-sm text-gray-500">Adicione uma descrição ou categoria para facilitar a identificação</p>
                        </div>

                        <!-- Botões de ação -->
                        <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                            <a href="{{ route('ncm_rules.index') }}" 
                               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition duration-150 ease-in-out">
                                <i class="fas fa-times mr-2"></i>Cancelar
                            </a>
                            <button type="submit" 
                                    class="inline-flex items-center px-6 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                                <i class="fas fa-save mr-2"></i>Atualizar Regra
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


