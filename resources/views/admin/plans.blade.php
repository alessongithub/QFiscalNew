<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gerenciamento de Planos') }}
            </h2>
            <a href="{{ route('admin.plans.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Novo Plano
            </a>
        </div>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nome
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Preço
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Limites
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Recursos
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($plans as $plan)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $plan->name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $plan->slug }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    R$ {{ number_format($plan->price, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @php $f = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []); @endphp
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Clientes: {{ isset($f['max_clients']) ? ($f['max_clients'] === -1 ? 'Ilimitado' : $f['max_clients']) : '—' }}
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Usuários: {{ isset($f['max_users']) ? ($f['max_users'] === -1 ? 'Ilimitado' : $f['max_users']) : '—' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @php
                                        $flags = [];
                                        if (($f['has_erp'] ?? false)) $flags[] = 'ERP';
                                        if (($f['has_emissor'] ?? false)) $flags[] = 'Emissor';
                                        if (($f['has_api_access'] ?? false)) $flags[] = 'API';
                                        if (isset($f['support_type'])) $flags[] = 'Suporte: ' . $f['support_type'];
                                    @endphp
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        @forelse(($flags ?? []) as $flag)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $flag }}</span>
                                        @empty
                                            <span class="text-gray-400">Sem recursos especiais</span>
                                        @endforelse
                                    </div>
                                    @if(!empty($f['display_features']) && is_array($f['display_features']))
                                        <div class="text-xs text-gray-500 line-clamp-2">
                                            {{ implode(' • ', array_slice($f['display_features'], 0, 4)) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $plan->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $plan->active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('admin.plans.edit', $plan) }}" class="text-indigo-600 hover:text-indigo-900">
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('admin.plans.toggle', $plan) }}" class="inline">
                                            @csrf
                                                                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                            {{ $plan->active ? 'Desativar' : 'Ativar' }}
                                        </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $plans->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
