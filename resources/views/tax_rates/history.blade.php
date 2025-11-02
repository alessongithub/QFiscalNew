<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Histórico de Alterações - {{ $tax_rate->name ?: 'Configuração #' . $tax_rate->id }}
            </h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('tax_rates.show', $tax_rate) }}" class="text-blue-600 hover:text-blue-800 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Ver Detalhes
                </a>
                <a href="{{ route('tax_rates.index') }}" class="text-gray-600 hover:text-gray-800 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-6">
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                <h3 class="text-white text-lg font-semibold">Timeline de Alterações</h3>
                <p class="text-green-100 text-sm">Histórico completo de todas as ações realizadas nesta configuração tributária</p>
            </div>

            <div class="p-6">
                @if($audits->count() > 0)
                    <div class="relative">
                        <!-- Linha vertical da timeline -->
                        <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                        
                        <ul class="space-y-6">
                            @foreach($audits as $audit)
                                @php
                                    $actionLabels = [
                                        'created' => 'criou',
                                        'updated' => 'atualizou',
                                        'deleted' => 'excluiu',
                                    ];
                                    $actionColors = [
                                        'created' => 'bg-green-100 text-green-800 border-green-300',
                                        'updated' => 'bg-blue-100 text-blue-800 border-blue-300',
                                        'deleted' => 'bg-red-100 text-red-800 border-red-300',
                                    ];
                                    $actionIcons = [
                                        'created' => 'M12 4v16m8-8H4',
                                        'updated' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
                                        'deleted' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
                                    ];
                                    $bgColor = $actionColors[$audit->action] ?? 'bg-gray-100 text-gray-800 border-gray-300';
                                    $iconPath = $actionIcons[$audit->action] ?? 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z';
                                @endphp
                                <li class="relative pl-16">
                                    <!-- Ícone da timeline -->
                                    <div class="absolute left-0 w-16 flex justify-center">
                                        <div class="flex items-center justify-center w-8 h-8 rounded-full {{ str_replace('text-', 'bg-', str_replace('-800', '-100', $bgColor)) }} border-2 {{ str_replace('border-', 'border-', str_replace('-300', '-300', $bgColor)) }}">
                                            <svg class="w-4 h-4 {{ str_replace('bg-', 'text-', str_replace('text-', '', explode(' ', $bgColor)[1])) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
                                            </svg>
                                        </div>
                                    </div>
                                    
                                    <!-- Conteúdo -->
                                    <div class="bg-white rounded-lg border {{ $bgColor }} shadow-sm p-4 hover:shadow-md transition-shadow">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-2 mb-2">
                                                    <span class="font-semibold text-gray-900">
                                                        {{ $audit->user->name ?? 'Sistema' }}
                                                    </span>
                                                    <span class="text-gray-600">
                                                        {{ $actionLabels[$audit->action] ?? $audit->action }}
                                                    </span>
                                                    <span class="text-gray-600">
                                                        esta configuração
                                                    </span>
                                                </div>
                                                
                                                @if($audit->notes)
                                                    <p class="text-sm text-gray-700 mb-3">{{ $audit->notes }}</p>
                                                @endif
                                                
                                                @if($audit->changes && is_array($audit->changes))
                                                    <div class="mt-3 bg-gray-50 rounded-lg p-3 border border-gray-200">
                                                        <h5 class="text-xs font-semibold text-gray-600 uppercase mb-2">Mudanças realizadas:</h5>
                                                        <div class="space-y-2">
                                                            @foreach($audit->changes as $field => $change)
                                                                @if(is_array($change) && isset($change['old']) && isset($change['new']))
                                                                    <div class="text-sm">
                                                                        <span class="font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                                                        <span class="text-red-600 line-through mr-2">{{ $change['old'] ?? '—' }}</span>
                                                                        <span class="text-green-600 font-semibold">→ {{ $change['new'] ?? '—' }}</span>
                                                                    </div>
                                                                @else
                                                                    <div class="text-sm">
                                                                        <span class="font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                                                        <span class="text-gray-900">{{ is_array($change) ? json_encode($change) : $change }}</span>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <div class="ml-4 text-right">
                                                <time class="text-xs text-gray-500 whitespace-nowrap" datetime="{{ $audit->created_at->toISOString() }}">
                                                    {{ $audit->created_at->format('d/m/Y') }}
                                                </time>
                                                <time class="text-xs text-gray-400 block" datetime="{{ $audit->created_at->toISOString() }}">
                                                    {{ $audit->created_at->format('H:i') }}
                                                </time>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma auditoria encontrada</h3>
                        <p class="text-sm text-gray-500">Esta configuração tributária ainda não possui histórico de alterações.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

