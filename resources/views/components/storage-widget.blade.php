@php
    $tenant = auth()->user()->tenant ?? null;
    $usage = $tenant?->storageUsage;
    $plan = $tenant?->plan;
@endphp

@if($usage)
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <h3 class="text-lg font-semibold mb-4">Armazenamento</h3>

        <div class="mb-4">
            <div class="flex justify-between mb-1">
                <span class="text-sm text-gray-600">Dados</span>
                <span class="text-sm font-medium">
                    {{ number_format($usage->data_usage_mb, 1) }} MB
                    @if($usage->total_data_limit_mb !== -1)
                        / {{ $usage->total_data_limit_mb }} MB
                    @else
                        / Ilimitado
                    @endif
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full {{ $usage->data_usage_percent >= 90 ? 'bg-red-500' : ($usage->data_usage_percent >= 75 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min(100, $usage->data_usage_percent) }}%"></div>
            </div>
            @if($usage->data_usage_percent >= 90)
                <p class="text-xs text-red-600 mt-1">⚠️ Limite quase atingido!</p>
            @endif
        </div>

        <div class="mb-4">
            <div class="flex justify-between mb-1">
                <span class="text-sm text-gray-600">Arquivos</span>
                <span class="text-sm font-medium">
                    {{ number_format($usage->files_usage_mb, 1) }} MB
                    @if($usage->total_files_limit_mb !== -1)
                        / {{ $usage->total_files_limit_mb }} MB
                    @else
                        / Ilimitado
                    @endif
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full {{ $usage->files_usage_percent >= 90 ? 'bg-red-500' : ($usage->files_usage_percent >= 75 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min(100, $usage->files_usage_percent) }}%"></div>
            </div>
            @if($usage->files_usage_percent >= 90)
                <p class="text-xs text-red-600 mt-1">⚠️ Limite quase atingido!</p>
            @endif
        </div>

        <div class="flex gap-2">
            <a href="{{ route('storage.index') }}" class="flex-1 text-center px-3 py-2 bg-gray-100 rounded hover:bg-gray-200 text-sm">
                Ver Detalhes
            </a>
            @if($usage->data_usage_percent >= 75 || $usage->files_usage_percent >= 75)
                <a href="{{ route('storage.upgrade') }}" class="flex-1 text-center px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                    Expandir Espaço
                </a>
            @endif
        </div>
    </div>
@endif


