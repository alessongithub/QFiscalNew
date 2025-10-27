<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <i class="fas fa-barcode mr-2"></i>
                Regras NCM → GTIN
            </h2>
            <a href="{{ route('ncm_rules.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out">
                <i class="fas fa-plus mr-2"></i>Nova Regra
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 text-green-700 border border-green-200 rounded-lg flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    {{ session('success') }}
                </div>
            @endif

            <!-- Card principal -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <!-- Header da tabela -->
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Lista de Regras</h3>
                            <p class="mt-1 text-sm text-gray-600">Gerencie as regras que determinam quando um NCM exige GTIN</p>
                        </div>
                        <div class="text-sm text-gray-500">
                            Total: {{ $rules->total() }} regras
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <form method="GET" action="{{ route('ncm_rules.index') }}" class="space-y-4" id="filterForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Filtro por código NCM -->
                            <div>
                                <label for="ncm" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-hashtag mr-1"></i>Código NCM
                                </label>
                                <input type="text" 
                                       id="ncm" 
                                       name="ncm" 
                                       value="{{ request('ncm') }}"
                                       placeholder="Ex: 8471.30.00"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>

                            <!-- Filtro por observação -->
                            <div>
                                <label for="note" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-sticky-note mr-1"></i>Observação
                                </label>
                                <input type="text" 
                                       id="note" 
                                       name="note" 
                                       value="{{ request('note') }}"
                                       placeholder="Ex: Computadores pessoais"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>

                            <!-- Filtro por GTIN obrigatório -->
                            <div>
                                <label for="requires_gtin" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-barcode mr-1"></i>Requer GTIN
                                </label>
                                <select id="requires_gtin" 
                                        name="requires_gtin" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="">Todos</option>
                                    <option value="1" {{ request('requires_gtin') === '1' ? 'selected' : '' }}>Obrigatório</option>
                                    <option value="0" {{ request('requires_gtin') === '0' ? 'selected' : '' }}>Opcional</option>
                                </select>
                            </div>

                            <!-- Ordenação -->
                            <div>
                                <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-sort mr-1"></i>Ordenar por
                                </label>
                                <select id="sort_by" 
                                        name="sort_by" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="ncm" {{ request('sort_by', 'ncm') === 'ncm' ? 'selected' : '' }}>Código NCM</option>
                                    <option value="note" {{ request('sort_by') === 'note' ? 'selected' : '' }}>Observação</option>
                                    <option value="requires_gtin" {{ request('sort_by') === 'requires_gtin' ? 'selected' : '' }}>Requer GTIN</option>
                                    <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Data de criação</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Direção da ordenação -->
                            <div>
                                <label for="sort_direction" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-sort-alpha-down mr-1"></i>Direção
                                </label>
                                <select id="sort_direction" 
                                        name="sort_direction" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="asc" {{ request('sort_direction', 'asc') === 'asc' ? 'selected' : '' }}>Crescente</option>
                                    <option value="desc" {{ request('sort_direction') === 'desc' ? 'selected' : '' }}>Decrescente</option>
                                </select>
                            </div>

                            <!-- Registros por página -->
                            <div>
                                <label for="per_page" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-list mr-1"></i>Por página
                                </label>
                                <select id="per_page" 
                                        name="per_page" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="10" {{ request('per_page', '20') === '10' ? 'selected' : '' }}>10 registros</option>
                                    <option value="20" {{ request('per_page', '20') === '20' ? 'selected' : '' }}>20 registros</option>
                                    <option value="25" {{ request('per_page') === '25' ? 'selected' : '' }}>25 registros</option>
                                    <option value="50" {{ request('per_page') === '50' ? 'selected' : '' }}>50 registros</option>
                                    <option value="100" {{ request('per_page') === '100' ? 'selected' : '' }}>100 registros</option>
                                    <option value="200" {{ request('per_page') === '200' ? 'selected' : '' }}>200 registros</option>
                                </select>
                            </div>

                            <!-- Botões de ação -->
                            <div class="flex items-end space-x-2">
                                <button type="submit" 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150 ease-in-out">
                                    <i class="fas fa-search mr-1"></i>Filtrar
                                </button>
                                <a href="{{ route('ncm_rules.index') }}" 
                                   class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition duration-150 ease-in-out">
                                    <i class="fas fa-times mr-1"></i>Limpar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

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
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="{{ route('ncm_rules.edit', $r) }}" 
                                           class="inline-flex items-center px-3 py-1 border border-blue-300 rounded-md text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition duration-150 ease-in-out">
                                            <i class="fas fa-edit mr-1"></i>Editar
                                        </a>
                                        <form action="{{ route('ncm_rules.destroy', $r) }}" method="POST" class="inline" 
                                              onsubmit="return confirm('Tem certeza que deseja excluir esta regra NCM {{ $r->ncm }}?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" 
                                                    class="inline-flex items-center px-3 py-1 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 transition duration-150 ease-in-out">
                                                <i class="fas fa-trash mr-1"></i>Excluir
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="text-gray-400">
                                        <i class="fas fa-inbox text-4xl mb-4"></i>
                                        <p class="text-lg font-medium text-gray-900 mb-2">Nenhuma regra cadastrada</p>
                                        <p class="text-sm text-gray-500 mb-4">Comece criando sua primeira regra NCM → GTIN</p>
                                        <a href="{{ route('ncm_rules.create') }}" 
                                           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150 ease-in-out">
                                            <i class="fas fa-plus mr-2"></i>Criar primeira regra
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                @if($rules->hasPages())
                <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
                    {{ $rules->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- JavaScript para filtros -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-submit quando mudar registros por página
            document.getElementById('per_page').addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });

            // Auto-submit quando mudar ordenação
            document.getElementById('sort_by').addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });

            document.getElementById('sort_direction').addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });

            // Auto-submit quando mudar filtro de GTIN
            document.getElementById('requires_gtin').addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });

            // Debounce para busca em tempo real nos campos de texto
            let searchTimeout;
            
            function debounceSearch() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    document.getElementById('filterForm').submit();
                }, 500); // 500ms de delay
            }

            // Aplicar debounce nos campos de texto
            document.getElementById('ncm').addEventListener('input', debounceSearch);
            document.getElementById('note').addEventListener('input', debounceSearch);

            // Adicionar indicador de carregamento
            const form = document.getElementById('filterForm');
            form.addEventListener('submit', function() {
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Filtrando...';
                submitBtn.disabled = true;
                
                // Reabilitar após um tempo (fallback)
                setTimeout(function() {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            });

            // Melhorar UX com highlights nos resultados
            const searchTerms = {
                ncm: '{{ request("ncm") }}',
                note: '{{ request("note") }}'
            };

            // Destacar termos de busca nos resultados
            if (searchTerms.ncm || searchTerms.note) {
                const rows = document.querySelectorAll('tbody tr');
                rows.forEach(function(row) {
                    const ncmCell = row.querySelector('td:first-child');
                    const noteCell = row.querySelector('td:nth-child(3)');
                    
                    if (searchTerms.ncm && ncmCell) {
                        highlightText(ncmCell, searchTerms.ncm);
                    }
                    
                    if (searchTerms.note && noteCell) {
                        highlightText(noteCell, searchTerms.note);
                    }
                });
            }

            function highlightText(element, searchTerm) {
                if (!searchTerm) return;
                
                const text = element.textContent;
                const regex = new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi');
                const highlightedText = text.replace(regex, '<mark class="bg-yellow-200 px-1 rounded">$1</mark>');
                
                if (highlightedText !== text) {
                    element.innerHTML = highlightedText;
                }
            }

            function escapeRegExp(string) {
                return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }

            // Adicionar atalhos de teclado
            document.addEventListener('keydown', function(e) {
                // Ctrl+F para focar no campo de busca NCM
                if (e.ctrlKey && e.key === 'f') {
                    e.preventDefault();
                    document.getElementById('ncm').focus();
                }
                
                // Ctrl+L para limpar filtros
                if (e.ctrlKey && e.key === 'l') {
                    e.preventDefault();
                    window.location.href = '{{ route("ncm_rules.index") }}';
                }
                
                // Enter para submeter formulário
                if (e.key === 'Enter' && (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT')) {
                    e.preventDefault();
                    document.getElementById('filterForm').submit();
                }
            });

            // Mostrar informações de filtros ativos
            const activeFilters = [];
            if (searchTerms.ncm) activeFilters.push(`NCM: "${searchTerms.ncm}"`);
            if (searchTerms.note) activeFilters.push(`Observação: "${searchTerms.note}"`);
            if (document.getElementById('requires_gtin').value) {
                const gtinText = document.getElementById('requires_gtin').value === '1' ? 'Obrigatório' : 'Opcional';
                activeFilters.push(`GTIN: ${gtinText}`);
            }

            if (activeFilters.length > 0) {
                const filterInfo = document.createElement('div');
                filterInfo.className = 'bg-blue-50 border border-blue-200 rounded-md p-3 mb-4';
                filterInfo.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-filter text-blue-600 mr-2"></i>
                        <span class="text-sm text-blue-800 font-medium">Filtros ativos:</span>
                        <span class="text-sm text-blue-700 ml-2">${activeFilters.join(', ')}</span>
                    </div>
                `;
                
                const card = document.querySelector('.bg-white.shadow-lg.rounded-lg');
                card.insertBefore(filterInfo, card.firstChild);
            }
        });
    </script>
</x-app-layout>


