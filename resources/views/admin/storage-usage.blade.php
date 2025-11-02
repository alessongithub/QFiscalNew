<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Consumo de Armazenamento por Tenant
        </h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Estatísticas Gerais -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <h3 class="text-sm font-medium text-blue-800 mb-1">Total de Dados</h3>
                    <p class="text-2xl font-bold text-blue-900">{{ number_format($stats['total_data_gb'], 2, ',', '.') }} GB</p>
                    <p class="text-xs text-blue-600 mt-1">{{ $stats['tenants_with_storage'] }} tenants monitorados</p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                    <h3 class="text-sm font-medium text-green-800 mb-1">Total de Arquivos</h3>
                    <p class="text-2xl font-bold text-green-900">{{ number_format($stats['total_files_gb'], 2, ',', '.') }} GB</p>
                    <p class="text-xs text-green-600 mt-1">Espaço usado em arquivos</p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                    <h3 class="text-sm font-medium text-purple-800 mb-1">Espaço Adicional (Dados)</h3>
                    <p class="text-2xl font-bold text-purple-900">{{ number_format($stats['total_additional_data_mb'], 0, ',', '.') }} MB</p>
                    <p class="text-xs text-purple-600 mt-1">Total comprado</p>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                    <h3 class="text-sm font-medium text-yellow-800 mb-1">Espaço Adicional (Arquivos)</h3>
                    <p class="text-2xl font-bold text-yellow-900">{{ number_format($stats['total_additional_files_mb'], 0, ',', '.') }} MB</p>
                    <p class="text-xs text-yellow-600 mt-1">Total comprado</p>
                </div>
            </div>

            <!-- Botão de Atualização -->
            <div class="mb-4 flex items-center justify-between bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div>
                    <p class="text-sm font-medium text-gray-700">Atualização Automática</p>
                    <p class="text-xs text-gray-500">Executada diariamente às 2h da manhã via cron</p>
                </div>
                <form method="GET" action="{{ route('admin.storage-usage') }}" onsubmit="return confirm('Atualizar uso de armazenamento de todos os tenants agora? Isso pode levar alguns segundos.')">
                    <input type="hidden" name="update" value="1">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium">
                        🔄 Atualizar Agora
                    </button>
                </form>
            </div>

            <!-- Filtros -->
            <form method="GET" action="{{ route('admin.storage-usage') }}" class="mb-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Buscar Tenant</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome, fantasia, email, CNPJ..." class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Parceiro</label>
                        <select name="partner_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Todos</option>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" {{ request('partner_id') == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">Filtrar</button>
                    <a href="{{ route('admin.storage-usage') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 text-sm">Limpar</a>
                </div>
            </form>

            <!-- Tabela -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tenant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parceiro</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plano</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dados</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Arquivos</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Última Atualização</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($tenants as $tenant)
                            @php
                                $usage = $tenant->storageUsage;
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900">{{ $tenant->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $tenant->fantasy_name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ optional($tenant->partner)->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="font-medium text-gray-900">{{ optional($tenant->plan)->name ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($usage && $usage->total_data_limit_mb !== -1)
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                                <div class="h-2 rounded-full {{ $usage->data_usage_percent >= 90 ? 'bg-red-500' : ($usage->data_usage_percent >= 75 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                                     style="width: {{ min(100, $usage->data_usage_percent) }}%"></div>
                                            </div>
                                            <span class="text-xs text-gray-600 whitespace-nowrap">
                                                {{ number_format($usage->data_usage_mb, 1) }} / {{ $usage->total_data_limit_mb }} MB
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-500">Ilimitado</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($usage && $usage->total_files_limit_mb !== -1)
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                                <div class="h-2 rounded-full {{ $usage->files_usage_percent >= 90 ? 'bg-red-500' : ($usage->files_usage_percent >= 75 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                                     style="width: {{ min(100, $usage->files_usage_percent) }}%"></div>
                                            </div>
                                            <span class="text-xs text-gray-600 whitespace-nowrap">
                                                {{ number_format($usage->files_usage_mb, 1) }} / {{ $usage->total_files_limit_mb }} MB
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-500">Ilimitado</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $usage && $usage->last_calculated_at ? $usage->last_calculated_at->format('d/m/Y H:i') : 'Nunca' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-6 text-center text-gray-500">
                                    Nenhum tenant encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="mt-4">
                {{ $tenants->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>

