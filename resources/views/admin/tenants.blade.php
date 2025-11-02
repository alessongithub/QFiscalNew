<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gerenciamento de Tenants') }}
        </h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            <!-- Filtros -->
            <form method="GET" action="{{ route('admin.tenants') }}" class="mb-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Busca por tenant -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Buscar Tenant</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome, fantasia, email, CNPJ..." class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    
                    <!-- Filtro por parceiro -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Parceiro</label>
                        <select name="partner_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Todos os parceiros</option>
                            <option value="none" {{ request('partner_id') === 'none' ? 'selected' : '' }}>Sem parceiro</option>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" {{ request('partner_id') == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Filtro por plano -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Plano</label>
                        <select name="plan_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Todos os planos</option>
                            <option value="none" {{ request('plan_id') === 'none' ? 'selected' : '' }}>Sem plano</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Filtro assinaturas vencidas -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Status Assinatura</label>
                        <label class="flex items-center mt-2">
                            <input type="checkbox" name="expired" value="1" {{ request('expired') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Apenas vencidas</span>
                        </label>
                    </div>
                    
                    <!-- Registros por página -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Por página</label>
                        <select name="per_page" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" onchange="this.form.submit()">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-2 mt-4">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium">
                        Filtrar
                    </button>
                    <a href="{{ route('admin.tenants') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium">
                        Limpar
                    </a>
                </div>
            </form>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nome
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                CNPJ
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Usuários
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Plano
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Parceiro
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Criado em
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($tenants as $tenant)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $tenant->name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $tenant->email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $tenant->cnpj }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $tenant->users->count() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="font-medium text-gray-900">{{ optional($tenant->plan)->name ?? '—' }}</div>
                                    @if(optional($tenant->plan)->price)
                                        <div class="text-xs text-gray-500">R$ {{ number_format($tenant->plan->price, 2, ',', '.') }}/mês</div>
                                    @endif
                                    @if($tenant->plan_expires_at)
                                        <div class="text-xs text-gray-400 mt-1">Vence: {{ $tenant->plan_expires_at->format('d/m/Y') }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $tenant->partner ? $tenant->partner->name : '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $tenant->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $tenant->active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $tenant->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('admin.tenants.edit', $tenant) }}" class="text-indigo-600 hover:text-indigo-900 mr-2">Editar</a>
                                    <form method="POST" action="{{ route('admin.tenants.toggle', $tenant) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $tenant->active ? 'Desativar' : 'Ativar' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $tenants->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
