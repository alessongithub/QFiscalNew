<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gerenciar Armazenamento
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="mb-4 p-3 bg-green-50 text-green-700 rounded">{{ session('success') }}</div>
                    @endif

                    @if(!$usage)
                        <p class="text-sm text-gray-600">Ainda não há dados de uso calculados. Volte em alguns instantes.</p>
                    @else
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold mb-4">Uso de Armazenamento</h3>

                            <div class="mb-6">
                                <div class="flex justify-between mb-2">
                                    <span class="font-medium">Armazenamento de Dados</span>
                                    <span class="text-sm">
                                        {{ number_format($usage->data_usage_mb, 2) }} MB
                                        @if($usage->total_data_limit_mb !== -1)
                                            / {{ $usage->total_data_limit_mb }} MB
                                        @else
                                            / Ilimitado
                                        @endif
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                                    <div class="h-3 rounded-full {{ $usage->data_usage_percent >= 90 ? 'bg-red-500' : ($usage->data_usage_percent >= 75 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min(100, $usage->data_usage_percent) }}%"></div>
                                </div>
                                <p class="text-xs text-gray-500">Dados estruturados: clientes, produtos, vendas, etc.</p>
                            </div>

                            <div class="mb-6">
                                <div class="flex justify-between mb-2">
                                    <span class="font-medium">Armazenamento de Arquivos</span>
                                    <span class="text-sm">
                                        {{ number_format($usage->files_usage_mb, 2) }} MB
                                        @if($usage->total_files_limit_mb !== -1)
                                            / {{ $usage->total_files_limit_mb }} MB
                                        @else
                                            / Ilimitado
                                        @endif
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                                    <div class="h-3 rounded-full {{ $usage->files_usage_percent >= 90 ? 'bg-red-500' : ($usage->files_usage_percent >= 75 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min(100, $usage->files_usage_percent) }}%"></div>
                                </div>
                                <p class="text-xs text-gray-500">Arquivos: XMLs NF-e, imagens, documentos PDF, etc.</p>
                            </div>
                        </div>

                        @if(($usage->additional_data_mb ?? 0) > 0 || ($usage->additional_files_mb ?? 0) > 0)
                            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                                <h4 class="font-semibold mb-2">Espaço Adicional Ativo</h4>
                                @if(($usage->additional_data_mb ?? 0) > 0)
                                    <p class="text-sm">+{{ $usage->additional_data_mb }} MB de dados</p>
                                @endif
                                @if(($usage->additional_files_mb ?? 0) > 0)
                                    <p class="text-sm">+{{ $usage->additional_files_mb }} MB de arquivos</p>
                                @endif
                            </div>
                        @endif

                        <div class="flex gap-3">
                            <a href="{{ route('storage.upgrade') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Expandir Espaço
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


