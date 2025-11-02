<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18M8 8h8M5 13h14M7 18h10" />
                </svg>
                Configurações Tributárias
            </h2>
            @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('tax_rates.create'))
            <a href="{{ route('tax_rates.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nova Configuração
            </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-full mx-auto px-4">
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-4 py-3">
                <h3 class="text-white text-base font-semibold">Alíquotas Configuradas</h3>
                <p class="text-green-100 text-xs">Gerencie as configurações tributárias para produtos e serviços</p>
            </div>

            @if(session('success'))
                <script>
                document.addEventListener('DOMContentLoaded', function(){
                    const n = document.createElement('div');
                    n.className = 'fixed top-4 right-4 px-4 py-2 rounded shadow-lg z-50 bg-green-600 text-white';
                    n.textContent = @json(session('success'));
                    document.body.appendChild(n);
                    setTimeout(()=> n.remove(), 3000);
                });
                </script>
            @endif
            @if($errors->any())
                <script>
                document.addEventListener('DOMContentLoaded', function(){
                    const errs = @json($errors->all());
                    errs.forEach(function(msg){
                        const n = document.createElement('div');
                        n.className = 'fixed top-4 right-4 px-4 py-2 mb-2 rounded shadow-lg z-50 bg-red-600 text-white';
                        n.textContent = msg;
                        document.body.appendChild(n);
                        setTimeout(()=> n.remove(), 4000);
                    });
                });
                </script>
            @endif
            
            <!-- Filtros -->
            <div class="bg-gray-50 px-3 py-3 border-b border-gray-200">
                <form method="GET" action="{{ route('tax_rates.index') }}" class="space-y-3" id="filterForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                        <!-- Filtro por nome -->
                        <div>
                            <label for="name" class="block text-xs font-medium text-gray-700 mb-1">
                                <i class="fas fa-tag mr-1"></i>Nome
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="{{ request('name') }}"
                                   placeholder="Ex: Computadores pessoais"
                                   class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Filtro por tipo -->
                        <div>
                            <label for="tipo_nota" class="block text-xs font-medium text-gray-700 mb-1">
                                <i class="fas fa-cube mr-1"></i>Tipo
                            </label>
                            <select id="tipo_nota" 
                                    name="tipo_nota" 
                                    class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todos</option>
                                <option value="produto" {{ request('tipo_nota') === 'produto' ? 'selected' : '' }}>Produto</option>
                                <option value="servico" {{ request('tipo_nota') === 'servico' ? 'selected' : '' }}>Serviço</option>
                            </select>
                        </div>

                        <!-- Filtro por NCM -->
                        <div>
                            <label for="ncm" class="block text-xs font-medium text-gray-700 mb-1">
                                <i class="fas fa-hashtag mr-1"></i>NCM
                            </label>
                            <input type="text" 
                                   id="ncm" 
                                   name="ncm" 
                                   value="{{ request('ncm') }}"
                                   placeholder="Ex: 8471.30.00"
                                   class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Filtro por CFOP -->
                        <div>
                            <label for="cfop" class="block text-xs font-medium text-gray-700 mb-1">
                                <i class="fas fa-barcode mr-1"></i>CFOP
                            </label>
                            <input type="text" 
                                   id="cfop" 
                                   name="cfop" 
                                   value="{{ request('cfop') }}"
                                   placeholder="Ex: 5102"
                                   class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                        <!-- Filtro por código de serviço -->
                        <div>
                            <label for="codigo_servico" class="block text-xs font-medium text-gray-700 mb-1">
                                <i class="fas fa-cogs mr-1"></i>Código Serviço
                            </label>
                            <input type="text" 
                                   id="codigo_servico" 
                                   name="codigo_servico" 
                                   value="{{ request('codigo_servico') }}"
                                   placeholder="Ex: 1.05.01"
                                   class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Filtro por status -->
                        <div>
                            <label for="ativo" class="block text-xs font-medium text-gray-700 mb-1">
                                <i class="fas fa-toggle-on mr-1"></i>Status
                            </label>
                            <select id="ativo" 
                                    name="ativo" 
                                    class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todos</option>
                                <option value="1" {{ request('ativo') === '1' ? 'selected' : '' }}>Ativo</option>
                                <option value="0" {{ request('ativo') === '0' ? 'selected' : '' }}>Inativo</option>
                            </select>
                        </div>

                        <!-- Ordenação -->
                        <div>
                            <label for="sort_by" class="block text-xs font-medium text-gray-700 mb-1">
                                <i class="fas fa-sort mr-1"></i>Ordenar por
                            </label>
                            <select id="sort_by" 
                                    name="sort_by" 
                                    class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="id" {{ request('sort_by', 'id') === 'id' ? 'selected' : '' }}>ID</option>
                                <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Nome</option>
                                <option value="tipo_nota" {{ request('sort_by') === 'tipo_nota' ? 'selected' : '' }}>Tipo</option>
                                <option value="ncm" {{ request('sort_by') === 'ncm' ? 'selected' : '' }}>NCM</option>
                                <option value="cfop" {{ request('sort_by') === 'cfop' ? 'selected' : '' }}>CFOP</option>
                                <option value="codigo_servico" {{ request('sort_by') === 'codigo_servico' ? 'selected' : '' }}>Código Serviço</option>
                                <option value="ativo" {{ request('sort_by') === 'ativo' ? 'selected' : '' }}>Status</option>
                                <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Data de criação</option>
                            </select>
                        </div>

                        <!-- Direção da ordenação -->
                        <div>
                            <label for="sort_direction" class="block text-xs font-medium text-gray-700 mb-1">
                                <i class="fas fa-sort-alpha-down mr-1"></i>Direção
                            </label>
                            <select id="sort_direction" 
                                    name="sort_direction" 
                                    class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="desc" {{ request('sort_direction', 'desc') === 'desc' ? 'selected' : '' }}>Decrescente</option>
                                <option value="asc" {{ request('sort_direction') === 'asc' ? 'selected' : '' }}>Crescente</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <!-- Registros por página -->
                        <div>
                            <label for="per_page" class="block text-xs font-medium text-gray-700 mb-1">
                                <i class="fas fa-list mr-1"></i>Por página
                            </label>
                            <select id="per_page" 
                                    name="per_page" 
                                    class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
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
                                    class="px-3 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700 focus:outline-none focus:ring-1 focus:ring-green-500 transition duration-150 ease-in-out">
                                <i class="fas fa-search mr-1"></i>Filtrar
                            </button>
                            <a href="{{ route('tax_rates.index') }}" 
                               class="px-3 py-1 bg-gray-600 text-white rounded text-xs hover:bg-gray-700 focus:outline-none focus:ring-1 focus:ring-gray-500 transition duration-150 ease-in-out">
                                <i class="fas fa-times mr-1"></i>Limpar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="p-3">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NCM</th>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CFOP</th>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Auditoria</th>
                                <th scope="col" class="relative px-3 py-2"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($rates as $r)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900 max-w-xs truncate" title="{{ $r->name ?: '—' }}">{{ $r->name ?: '—' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($r->tipo_nota === 'produto')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                🏷️ Produto
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                ⚙️ Serviço
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900 font-mono">{{ $r->ncm ?? '—' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900 font-mono">{{ $r->cfop ?? '—' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    @if($r->ativo)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            ✓ Ativo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            ✗ Inativo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-600">
                                    <div class="space-y-1">
                                        @if($r->createdBy)
                                            <div class="flex items-center gap-1">
                                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                <span class="text-gray-500">Criado por:</span>
                                                <span class="font-medium">{{ $r->createdBy->name }}</span>
                                            </div>
                                        @endif
                                        @if($r->updatedBy && $r->updatedBy->id !== ($r->createdBy->id ?? null))
                                            <div class="flex items-center gap-1">
                                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                <span class="text-gray-500">Editado por:</span>
                                                <span class="font-medium">{{ $r->updatedBy->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-right text-xs font-medium">
                                    <div class="flex items-center justify-end space-x-1">
                                        @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('tax_rates.view'))
                                        <a href="{{ route('tax_rates.show', $r) }}" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors" title="Visualizar">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        @endif
                                        @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('tax_rates.edit'))
                                        <a href="{{ route('tax_rates.edit', $r) }}" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-1 focus:ring-green-500 transition-colors" title="Editar">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        @endif
                                        @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('tax_rates.delete'))
                                        <form action="{{ route('tax_rates.destroy', $r) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir esta configuração tributária?\n\nEsta ação não pode ser desfeita.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-1 focus:ring-red-500 transition-colors" title="Excluir">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-3 py-8 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        <h3 class="text-xs font-medium text-gray-900 mb-1">Nenhuma configuração tributária</h3>
                                        <p class="text-xs text-gray-500 mb-3">Comece criando sua primeira configuração de alíquotas.</p>
                                        @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('tax_rates.create'))
                                        <a href="{{ route('tax_rates.create') }}" class="inline-flex items-center px-3 py-1 border border-transparent shadow-sm text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-1 focus:ring-green-500">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Criar primeira configuração
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($rates->hasPages())
                <div class="mt-4 flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        @if ($rates->onFirstPage())
                            <span class="relative inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded text-gray-500 bg-white cursor-default">
                                Anterior
                            </span>
                        @else
                            <a href="{{ $rates->previousPageUrl() }}" class="relative inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                Anterior
                            </a>
                        @endif

                        @if ($rates->hasMorePages())
                            <a href="{{ $rates->nextPageUrl() }}" class="ml-3 relative inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                Próximo
                            </a>
                        @else
                            <span class="ml-3 relative inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded text-gray-500 bg-white cursor-default">
                                Próximo
                            </span>
                        @endif
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs text-gray-700">
                                Mostrando <span class="font-medium">{{ $rates->firstItem() ?? 0 }}</span> a <span class="font-medium">{{ $rates->lastItem() ?? 0 }}</span> de <span class="font-medium">{{ $rates->total() }}</span> registros
                            </p>
                        </div>
                        <div>
                            {{ $rates->links() }}
                        </div>
                    </div>
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

            // Auto-submit quando mudar filtros de seleção
            document.getElementById('tipo_nota').addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });

            document.getElementById('ativo').addEventListener('change', function() {
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
            document.getElementById('name').addEventListener('input', debounceSearch);
            document.getElementById('ncm').addEventListener('input', debounceSearch);
            document.getElementById('cfop').addEventListener('input', debounceSearch);
            document.getElementById('codigo_servico').addEventListener('input', debounceSearch);

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
                name: '{{ request("name") }}',
                ncm: '{{ request("ncm") }}',
                cfop: '{{ request("cfop") }}',
                codigo_servico: '{{ request("codigo_servico") }}'
            };

            // Destacar termos de busca nos resultados
            if (searchTerms.name || searchTerms.ncm || searchTerms.cfop || searchTerms.codigo_servico) {
                const rows = document.querySelectorAll('tbody tr');
                rows.forEach(function(row) {
                    const nameCell = row.querySelector('td:first-child');
                    const ncmCell = row.querySelector('td:nth-child(3)');
                    const cfopCell = row.querySelector('td:nth-child(4)');
                    
                    if (searchTerms.name && nameCell) {
                        highlightText(nameCell, searchTerms.name);
                    }
                    
                    if (searchTerms.ncm && ncmCell) {
                        highlightText(ncmCell, searchTerms.ncm);
                    }
                    
                    if (searchTerms.cfop && cfopCell) {
                        highlightText(cfopCell, searchTerms.cfop);
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
                // Ctrl+F para focar no campo de busca nome
                if (e.ctrlKey && e.key === 'f') {
                    e.preventDefault();
                    document.getElementById('name').focus();
                }
                
                // Ctrl+L para limpar filtros
                if (e.ctrlKey && e.key === 'l') {
                    e.preventDefault();
                    window.location.href = '{{ route("tax_rates.index") }}';
                }
                
                // Enter para submeter formulário
                if (e.key === 'Enter' && (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT')) {
                    e.preventDefault();
                    document.getElementById('filterForm').submit();
                }
            });

            // Mostrar informações de filtros ativos
            const activeFilters = [];
            if (searchTerms.name) activeFilters.push(`Nome: "${searchTerms.name}"`);
            if (document.getElementById('tipo_nota').value) {
                const tipoText = document.getElementById('tipo_nota').value === 'produto' ? 'Produto' : 'Serviço';
                activeFilters.push(`Tipo: ${tipoText}`);
            }
            if (searchTerms.ncm) activeFilters.push(`NCM: "${searchTerms.ncm}"`);
            if (searchTerms.cfop) activeFilters.push(`CFOP: "${searchTerms.cfop}"`);
            if (searchTerms.codigo_servico) activeFilters.push(`Código: "${searchTerms.codigo_servico}"`);
            if (document.getElementById('ativo').value) {
                const statusText = document.getElementById('ativo').value === '1' ? 'Ativo' : 'Inativo';
                activeFilters.push(`Status: ${statusText}`);
            }

            if (activeFilters.length > 0) {
                const filterInfo = document.createElement('div');
                filterInfo.className = 'bg-green-50 border border-green-200 rounded-md p-2 mb-3';
                filterInfo.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-filter text-green-600 mr-2"></i>
                        <span class="text-xs text-green-800 font-medium">Filtros ativos:</span>
                        <span class="text-xs text-green-700 ml-2">${activeFilters.join(', ')}</span>
                    </div>
                `;
                
                const card = document.querySelector('.bg-white.shadow-xl.rounded-lg');
                card.insertBefore(filterInfo, card.children[1]);
            }
        });
    </script>
</x-app-layout>


