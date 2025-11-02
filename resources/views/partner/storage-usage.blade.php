<x-partner-layout>
    <div class="bg-white rounded shadow">
        <div class="p-4 border-b">
            <h2 class="font-semibold text-gray-800">Consumo de Armazenamento dos Clientes</h2>
        </div>
        <div class="p-4">
            <!-- Filtros -->
            <form method="GET" action="{{ route('partner.storage-usage') }}" class="mb-4">
                <div class="flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nome, fantasia, email..." class="flex-1 border rounded p-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Buscar</button>
                    <a href="{{ route('partner.storage-usage') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Limpar</a>
                </div>
            </form>

            <!-- Tabela -->
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600">
                            <th class="text-left px-4 py-2">Cliente</th>
                            <th class="text-left px-4 py-2">Plano</th>
                            <th class="text-left px-4 py-2">Dados</th>
                            <th class="text-left px-4 py-2">Arquivos</th>
                            <th class="text-left px-4 py-2">Última Atualização</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenants as $tenant)
                            @php
                                $usage = $tenant->storageUsage;
                            @endphp
                            <tr class="border-b">
                                <td class="px-4 py-2">
                                    <div class="font-medium text-gray-800">{{ $tenant->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $tenant->fantasy_name }}</div>
                                </td>
                                <td class="px-4 py-2 text-gray-700">
                                    {{ optional($tenant->plan)->name ?? '—' }}
                                </td>
                                <td class="px-4 py-2">
                                    @if($usage && $usage->total_data_limit_mb !== -1)
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-gray-200 rounded-full h-2" style="min-width: 100px;">
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
                                <td class="px-4 py-2">
                                    @if($usage && $usage->total_files_limit_mb !== -1)
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-gray-200 rounded-full h-2" style="min-width: 100px;">
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
                                <td class="px-4 py-2 text-gray-500 text-xs">
                                    {{ $usage && $usage->last_calculated_at ? $usage->last_calculated_at->format('d/m/Y H:i') : 'Nunca' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                    Nenhum cliente encontrado.
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
</x-partner-layout>

