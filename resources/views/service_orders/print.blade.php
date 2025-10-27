<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OS #{{ $order->number }} - Recebimento</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Figtree', sans-serif; }
        @media print { 
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
            body { margin: 0; padding: 0; }
            .container { max-width: 100%; margin: 0; padding: 10px; }
        }
        .os-card { 
            border: 2px solid #374151; 
            margin-bottom: 20px; 
            page-break-inside: avoid;
        }
    </style>
    <script>
        function doPrint(){ window.print(); }
    </script>
</head>
<body class="bg-white">
    <div class="container mx-auto p-4">
        <!-- Primeira OS -->
        <div class="os-card p-4">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4 border-b pb-2">
                <div>
                    <h1 class="text-lg font-bold text-gray-800">ORDEM DE SERVIÇO - RECEBIMENTO</h1>
                    <p class="text-sm text-gray-600">OS #{{ $order->number }}</p>
                </div>
                @php
                    $logoUrl = $order->tenant && $order->tenant->logo_path
                        ? $order->tenant->logo_url
                        : asset('logo.png');
                @endphp
                <img class="h-10 w-auto" src="{{ $logoUrl }}" alt="Logo">
            </div>

            <!-- Dados da Empresa -->
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">EMPRESA:</h3>
                <div class="text-xs text-gray-800">
                    <div class="font-bold">{{ optional($order->tenant)->name }}</div>
                    @if($order->tenant && $order->tenant->fantasy_name)
                        <div class="font-bold">{{ $order->tenant->fantasy_name }}</div>
                    @endif
                    <div>
                        @if($order->tenant && $order->tenant->address)
                            {{ $order->tenant->address }}, {{ $order->tenant->number ?? '' }}
                            @if($order->tenant->neighborhood)
                                - {{ $order->tenant->neighborhood }}
                            @endif
                            @if($order->tenant->city)
                                - {{ $order->tenant->city }}/{{ $order->tenant->state ?? '' }}
                            @endif
                        @endif
                        @if($order->tenant && $order->tenant->phone)
                            | Tel: {{ $order->tenant->phone }}
                        @endif
                    </div>
                    <div>
                        @if($order->tenant && $order->tenant->email)
                            Email: {{ $order->tenant->email }}
                        @endif
                        @if($order->tenant && $order->tenant->cnpj)
                            @if($order->tenant && $order->tenant->email) | @endif
                            CNPJ: {{ $order->tenant->cnpj }}
                        @endif
                    </div>
                </div>
            </div>

            <!-- Dados do Cliente -->
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">CLIENTE:</h3>
                <div class="text-xs text-gray-800">
                    <div class="font-bold">{{ optional($order->client)->name }}</div>
                    @if($order->client)
                        <div>
                            @if($order->client->address)
                                {{ $order->client->address }}, {{ $order->client->number ?? '' }}
                                @if($order->client->neighborhood)
                                    - {{ $order->client->neighborhood }}
                                @endif
                                @if($order->client->city)
                                    - {{ $order->client->city }}/{{ $order->client->state ?? '' }}
                                @endif
                            @endif
                            @if($order->client->phone)
                                @if($order->client->address) | @endif
                                Tel: {{ $order->client->phone }}
                            @endif
                        </div>
                        <div>
                            @if($order->client->email)
                                Email: {{ $order->client->email }}
                            @endif
                            @if($order->client->cnpj)
                                @if($order->client->email) | @endif
                                CNPJ: {{ $order->client->cnpj }}
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Dados do Equipamento -->
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">EQUIPAMENTO:</h3>
                <div class="grid grid-cols-3 gap-2 text-xs">
                    <div><strong>Marca:</strong> {{ $order->equipment_brand ?? '-' }}</div>
                    <div><strong>Modelo:</strong> {{ $order->equipment_model ?? '-' }}</div>
                    <div><strong>Série:</strong> {{ $order->equipment_serial ?? '-' }}</div>
                </div>
                @if($order->equipment_description)
                <p class="text-xs mt-1"><strong>Descrição:</strong> {{ $order->equipment_description }}</p>
                @endif
            </div>

            <!-- Defeito Reclamado -->
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">DEFEITO RECLAMADO:</h3>
                <p class="text-xs text-gray-800">{{ $order->defect_reported ?? '-' }}</p>
            </div>

            <!-- Observações -->
            @if($order->diagnosis)
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">OBSERVAÇÕES:</h3>
                <p class="text-xs text-gray-800">{{ $order->diagnosis }}</p>
            </div>
            @endif

            <!-- Assinaturas -->
            <div class="mt-4 border-t pt-3">
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <div class="border-b border-gray-400 h-8 mb-1"></div>
                        <p class="text-xs font-medium text-gray-700">Cliente</p>
                        <p class="text-xs text-gray-500">{{ optional($order->client)->name }}</p>
                    </div>
                    <div class="text-center">
                        <div class="border-b border-gray-400 h-8 mb-1"></div>
                        <p class="text-xs font-medium text-gray-700">Responsável</p>
                        <p class="text-xs text-gray-500">{{ optional($order->tenant)->name }}</p>
                    </div>
                </div>
                <div class="text-center mt-2">
                    <p class="text-xs text-gray-500">Data: {{ now()->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Segunda OS (duplicada para duas por folha) -->
        <div class="os-card p-4 page-break">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4 border-b pb-2">
                <div>
                    <h1 class="text-lg font-bold text-gray-800">ORDEM DE SERVIÇO - RECEBIMENTO</h1>
                    <p class="text-sm text-gray-600">OS #{{ $order->number }}</p>
                </div>
                <img class="h-10 w-auto" src="{{ $logoUrl }}" alt="Logo">
            </div>

            <!-- Dados da Empresa -->
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">EMPRESA:</h3>
                <div class="text-xs text-gray-800">
                    <div class="font-bold">{{ optional($order->tenant)->name }}</div>
                    @if($order->tenant && $order->tenant->fantasy_name)
                        <div class="font-bold">{{ $order->tenant->fantasy_name }}</div>
                    @endif
                    <div>
                        @if($order->tenant && $order->tenant->address)
                            {{ $order->tenant->address }}, {{ $order->tenant->number ?? '' }}
                            @if($order->tenant->neighborhood)
                                - {{ $order->tenant->neighborhood }}
                            @endif
                            @if($order->tenant->city)
                                - {{ $order->tenant->city }}/{{ $order->tenant->state ?? '' }}
                            @endif
                        @endif
                        @if($order->tenant && $order->tenant->phone)
                            | Tel: {{ $order->tenant->phone }}
                        @endif
                    </div>
                    <div>
                        @if($order->tenant && $order->tenant->email)
                            Email: {{ $order->tenant->email }}
                        @endif
                        @if($order->tenant && $order->tenant->cnpj)
                            @if($order->tenant && $order->tenant->email) | @endif
                            CNPJ: {{ $order->tenant->cnpj }}
                        @endif
                    </div>
                </div>
            </div>

            <!-- Dados do Cliente -->
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">CLIENTE:</h3>
                <div class="text-xs text-gray-800">
                    <div class="font-bold">{{ optional($order->client)->name }}</div>
                    @if($order->client)
                        <div>
                            @if($order->client->address)
                                {{ $order->client->address }}, {{ $order->client->number ?? '' }}
                                @if($order->client->neighborhood)
                                    - {{ $order->client->neighborhood }}
                                @endif
                                @if($order->client->city)
                                    - {{ $order->client->city }}/{{ $order->client->state ?? '' }}
                                @endif
                            @endif
                            @if($order->client->phone)
                                @if($order->client->address) | @endif
                                Tel: {{ $order->client->phone }}
                            @endif
                        </div>
                        <div>
                            @if($order->client->email)
                                Email: {{ $order->client->email }}
                            @endif
                            @if($order->client->cnpj)
                                @if($order->client->email) | @endif
                                CNPJ: {{ $order->client->cnpj }}
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Dados do Equipamento -->
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">EQUIPAMENTO:</h3>
                <div class="grid grid-cols-3 gap-2 text-xs">
                    <div><strong>Marca:</strong> {{ $order->equipment_brand ?? '-' }}</div>
                    <div><strong>Modelo:</strong> {{ $order->equipment_model ?? '-' }}</div>
                    <div><strong>Série:</strong> {{ $order->equipment_serial ?? '-' }}</div>
                </div>
                @if($order->equipment_description)
                <p class="text-xs mt-1"><strong>Descrição:</strong> {{ $order->equipment_description }}</p>
                @endif
            </div>

            <!-- Defeito Reclamado -->
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">DEFEITO RECLAMADO:</h3>
                <p class="text-xs text-gray-800">{{ $order->defect_reported ?? '-' }}</p>
            </div>

            <!-- Observações -->
            @if($order->diagnosis)
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">OBSERVAÇÕES:</h3>
                <p class="text-xs text-gray-800">{{ $order->diagnosis }}</p>
            </div>
            @endif

            <!-- Assinaturas -->
            <div class="mt-4 border-t pt-3">
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <div class="border-b border-gray-400 h-8 mb-1"></div>
                        <p class="text-xs font-medium text-gray-700">Cliente</p>
                        <p class="text-xs text-gray-500">{{ optional($order->client)->name }}</p>
                    </div>
                    <div class="text-center">
                        <div class="border-b border-gray-400 h-8 mb-1"></div>
                        <p class="text-xs font-medium text-gray-700">Responsável</p>
                        <p class="text-xs text-gray-500">{{ optional($order->tenant)->name }}</p>
                    </div>
                </div>
                <div class="text-center mt-2">
                    <p class="text-xs text-gray-500">Data: {{ now()->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="no-print mt-6 text-center">
            <button onclick="doPrint(); return false;" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors mr-4">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimir
            </button>
            <a href="{{ route('service_orders.index') }}" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar
            </a>
        </div>
    </div>
</body>
</html>