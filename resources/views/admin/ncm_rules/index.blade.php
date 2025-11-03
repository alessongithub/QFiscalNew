<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <i class="fas fa-barcode mr-2"></i>
                Regras NCM → GTIN (Global)
            </h2>
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.ncm_rules.export') }}" 
                   class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition duration-150 ease-in-out shadow-sm">
                    <i class="fas fa-file-export mr-2"></i>Exportar CSV
                </a>
                <a href="{{ route('admin.ncm_rules.import') }}" 
                   class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition duration-150 ease-in-out shadow-sm">
                    <i class="fas fa-upload mr-2"></i>Importar CSV
                </a>
            </div>
        </div>
        <p class="mt-2 text-sm text-gray-600">
            <i class="fas fa-info-circle mr-1"></i>
            Estas regras são <strong>globais</strong> e aplicadas a <strong>todos os tenants</strong>. Atualizações aqui serão visíveis para todos.
        </p>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 text-green-700 border border-green-200 rounded-lg">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-check-circle mr-3"></i>
                        <strong>{{ session('success') }}</strong>
                    </div>
                    @if(session('import_stats'))
                        @php
                            $stats = session('import_stats');
                        @endphp
                        <div class="mt-3 pl-7 space-y-1 text-sm">
                            <div>✓ Total processado: <strong>{{ $stats['processed'] }}</strong></div>
                            <div>✓ Novas regras criadas: <strong class="text-blue-600">{{ $stats['created'] }}</strong></div>
                            <div>✓ Regras atualizadas: <strong class="text-purple-600">{{ $stats['updated'] }}</strong></div>
                            @if($stats['skipped'] > 0)
                                <div>⊘ Ignoradas (sem alterações): <strong>{{ $stats['skipped'] }}</strong></div>
                            @endif
                            @if(!empty($stats['errors']))
                                <div class="mt-2 p-2 bg-red-100 border border-red-300 rounded text-xs">
                                    <strong class="text-red-800">Erros encontrados:</strong>
                                    <ul class="list-disc list-inside mt-1 space-y-1">
                                        @foreach(array_slice($stats['errors'], 0, 10) as $error)
                                            <li class="text-red-700">{{ $error }}</li>
                                        @endforeach
                                        @if(count($stats['errors']) > 10)
                                            <li class="text-red-600 italic">... e mais {{ count($stats['errors']) - 10 }} erro(s)</li>
                                        @endif
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            <!-- Filtros -->
            <form method="GET" action="{{ route('admin.ncm_rules.index') }}" class="mb-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Filtro por código NCM -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            <i class="fas fa-hashtag mr-1"></i>Código NCM
                        </label>
                        <input type="text" 
                               name="ncm" 
                               value="{{ request('ncm') }}"
                               placeholder="Ex: 84713000"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>

                    <!-- Filtro por observação -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            <i class="fas fa-sticky-note mr-1"></i>Observação
                        </label>
                        <input type="text" 
                               name="note" 
                               value="{{ request('note') }}"
                               placeholder="Ex: Computadores"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>

                    <!-- Filtro por GTIN obrigatório -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            <i class="fas fa-barcode mr-1"></i>Requer GTIN
                        </label>
                        <select name="requires_gtin" 
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Todos</option>
                            <option value="1" {{ request('requires_gtin') === '1' ? 'selected' : '' }}>Obrigatório</option>
                            <option value="0" {{ request('requires_gtin') === '0' ? 'selected' : '' }}>Opcional</option>
                        </select>
                    </div>

                    <!-- Registros por página -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            <i class="fas fa-list mr-1"></i>Por página
                        </label>
                        <select name="per_page" 
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                onchange="this.form.submit()">
                            <option value="25" {{ request('per_page', 50) == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                            <option value="200" {{ request('per_page') == 200 ? 'selected' : '' }}>200</option>
                            <option value="500" {{ request('per_page') == 500 ? 'selected' : '' }}>500</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium">
                        <i class="fas fa-search mr-1"></i>Filtrar
                    </button>
                    <a href="{{ route('admin.ncm_rules.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium">
                        <i class="fas fa-times mr-1"></i>Limpar
                    </a>
                </div>
            </form>

            <!-- Tabela -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-hashtag mr-1"></i>NCM
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-barcode mr-1"></i>Requer GTIN
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-sticky-note mr-1"></i>Observação
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-cogs mr-1"></i>Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($rules as $r)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-mono text-sm font-medium text-gray-900 bg-gray-100 px-2 py-1 rounded">
                                    {{ $r->ncm }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($r->requires_gtin)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>Obrigatório
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check mr-1"></i>Opcional
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $r->note }}">
                                    {{ $r->note ?: '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <form action="{{ route('admin.ncm_rules.destroy', $r) }}" method="POST" class="inline" 
                                      onsubmit="return confirm('Tem certeza que deseja excluir esta regra NCM {{ $r->ncm }}?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" 
                                            class="inline-flex items-center px-3 py-1 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 transition duration-150 ease-in-out">
                                        <i class="fas fa-trash mr-1"></i>Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-inbox text-4xl mb-4"></i>
                                    <p class="text-lg font-medium text-gray-900 mb-2">Nenhuma regra cadastrada</p>
                                    <p class="text-sm text-gray-500">Comece importando regras via CSV</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            @if($rules->hasPages())
            <div class="mt-4">
                {{ $rules->links() }}
            </div>
            @endif

            <!-- Informações -->
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium mb-1">Sobre as Regras NCM:</p>
                        <ul class="list-disc list-inside space-y-1 text-blue-700">
                            <li>As regras são <strong>globais</strong> e aplicadas a todos os tenants</li>
                            <li>NCMs são armazenados <strong>sem pontos</strong> (apenas dígitos)</li>
                            <li>Use <strong>Exportar CSV</strong> para baixar todas as regras atuais</li>
                            <li>Use <strong>Importar CSV</strong> para atualizar em massa via arquivo</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

