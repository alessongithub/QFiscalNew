<x-app-layout>
    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-600 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Gestão de Clientes</h2>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">Gerencie seus clientes de forma completa</p>
                    @isset($plan)
                            <div class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                            Plano: <span class="font-semibold">{{ $plan->name }}</span>
                            <span class="mx-2">•</span>
                            Clientes: <span class="font-semibold">{{ $totalClients }}</span>
                            /
                            <span class="font-semibold">{{ $maxClients === -1 ? 'Ilimitado' : $maxClients }}</span>
                        </div>
                    @endisset
                </div>
                @php $limiteAtingido = ($maxClients !== -1 && isset($totalClients) && isset($maxClients) && $totalClients >= $maxClients); @endphp
                @if($limiteAtingido)
                    <a href="{{ route('plans.upgrade') }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors shadow-md">
                        Fazer Upgrade
                    </a>
                @else
                    <a href="{{ route('clients.create') }}" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors shadow-md">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Novo Cliente
                    </a>
                @endif
            </div>
        </div>

        <!-- Filtros -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <!-- Linha 1: Buscar -->
                <div class="md:col-span-12">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Nome, email ou CPF/CNPJ..." 
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>
                <!-- Linha 2: Demais filtros -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
                    <select name="type" class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="">Todos</option>
                        <option value="pf" {{ request('type') == 'pf' ? 'selected' : '' }}>Pessoa Física</option>
                        <option value="pj" {{ request('type') == 'pj' ? 'selected' : '' }}>Pessoa Jurídica</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select name="status" class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="">Todos</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Ativo</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inativo</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ordenar por</label>
                    <select name="sort" class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 p-2 min-w-[140px]">
                        <option value="name" {{ request('sort','name')=='name' ? 'selected' : '' }}>Nome</option>
                        <option value="created_at" {{ request('sort')=='created_at' ? 'selected' : '' }}>Data de cadastro</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Direção</label>
                    <select name="direction" class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 p-2 min-w-[120px]">
                        <option value="asc" {{ request('direction','asc')=='asc' ? 'selected' : '' }}>Crescente</option>
                        <option value="desc" {{ request('direction')=='desc' ? 'selected' : '' }}>Decrescente</option>
                    </select>
                </div>
                <div class="md:col-span-2 md:col-start-11">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mostrar</label>
                    <select name="per_page" class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 p-2 min-w-[160px]">
                        @foreach([10,12,25,50,100,200] as $opt)
                            <option value="{{ $opt }}" {{ (int)request('per_page', 10) === $opt ? 'selected' : '' }}>{{ $opt }} por página</option>
                        @endforeach
                    </select>
                </div>
                <!-- Linha 3: Botões -->
                <div class="md:col-span-12 flex items-end justify-end gap-2 mt-1">
                    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Filtrar
                    </button>
                    <a href="{{ route('clients.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">Limpar</a>
                </div>
            </form>
        </div>

        <!-- Mensagens -->
        @if(session('success'))
        <div class="mx-6 mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
        @endif

        <!-- Tabela -->
        <div class="p-4">
            @if(isset($clients) && $clients->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nome</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Telefone</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">CPF/CNPJ</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                        @foreach($clients as $client)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $client->name }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $client->email ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $client->formatted_phone ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $client->formatted_cpf_cnpj }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $client->type == 'pf' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                    {{ $client->type_name }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $client->status == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $client->status_name }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex justify-center space-x-2">
                                    <a href="{{ route('clients.show', $client) }}" class="text-gray-700 hover:text-gray-900" title="Ver">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="{{ route('clients.edit', $client) }}" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('clients.destroy', $client) }}" class="inline" 
                                          onsubmit="return confirm('Tem certeza que deseja excluir este cliente?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Excluir">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            @if(isset($clients))
            <div class="mt-6">
                {{ $clients->links() }}
            </div>
            @endif
            @else
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400 text-lg mb-2">Nenhum cliente encontrado</p>
                <p class="text-gray-400 dark:text-gray-500 text-sm mb-4">Clique em "Novo Cliente" para começar</p>
                <a href="{{ route('clients.create') }}" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    Criar Primeiro Cliente
                </a>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>