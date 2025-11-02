<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Comprar Espaço Adicional
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if(session('success'))
                        <div class="mb-4 p-3 bg-green-50 text-green-700 rounded">{{ session('success') }}</div>
                    @endif

                    <h3 class="text-lg font-semibold mb-4">Escolha o tipo de espaço adicional</h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="border rounded-lg p-6">
                            <h4 class="font-semibold mb-2">Espaço de Dados</h4>
                            <p class="text-sm text-gray-600 mb-4">+50 MB adicionais para dados (clientes, produtos, vendas)</p>
                            @php
                                $features = is_array($plan->features) ? $plan->features : (json_decode($plan->features ?? '{}', true) ?? []);
                                $dataPrice = (float)($features['additional_data_price'] ?? 9.90);
                            @endphp
                            <p class="text-2xl font-bold text-green-600 mb-4">
                                R$ {{ number_format($dataPrice, 2, ',', '.') }}/mês
                            </p>
                            <form method="POST" action="{{ route('storage.purchase-addon') }}">
                                @csrf
                                <input type="hidden" name="type" value="data">
                                <input type="hidden" name="quantity_mb" value="50">
                                <button type="submit" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">
                                    Solicitar
                                </button>
                            </form>
                        </div>

                        <div class="border rounded-lg p-6">
                            <h4 class="font-semibold mb-2">Espaço de Arquivos</h4>
                            <p class="text-sm text-gray-600 mb-4">+500 MB adicionais para arquivos (XMLs, imagens, PDFs)</p>
                            @php
                                $featuresFiles = is_array($plan->features) ? $plan->features : (json_decode($plan->features ?? '{}', true) ?? []);
                                $filesPrice = (float)($featuresFiles['additional_files_price'] ?? 9.90);
                            @endphp
                            <p class="text-2xl font-bold text-green-600 mb-4">
                                R$ {{ number_format($filesPrice, 2, ',', '.') }}/mês
                            </p>
                            <form method="POST" action="{{ route('storage.purchase-addon') }}">
                                @csrf
                                <input type="hidden" name="type" value="files">
                                <input type="hidden" name="quantity_mb" value="500">
                                <button type="submit" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">
                                    Solicitar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


