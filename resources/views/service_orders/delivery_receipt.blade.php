<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Entrega - OS #{{ $serviceOrder->number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { 
                margin: 0; 
                font-size: 12px; 
                background: white !important;
                color: black !important;
            }
            .no-print { display: none !important; }
            .print-break { page-break-after: always; }
            .print-container { 
                max-width: none !important; 
                margin: 0 !important; 
                padding: 15px !important;
                background: white !important;
            }
            .compact-section { 
                margin-bottom: 12px !important; 
                padding: 8px !important; 
                background: #f9fafb !important;
                border: 1px solid #e5e7eb !important;
            }
            .compact-text { font-size: 11px !important; }
            .compact-title { font-size: 14px !important; margin-bottom: 6px !important; }
            table { 
                border-collapse: collapse !important; 
                width: 100% !important;
            }
            th, td { 
                border: 1px solid #374151 !important; 
                padding: 4px !important;
                text-align: left !important;
            }
            th { 
                background: #f3f4f6 !important; 
                font-weight: bold !important;
            }
            .bg-green-50 { background: #f0fdf4 !important; }
            .bg-gray-100 { background: #f3f4f6 !important; }
            .text-gray-800 { color: #1f2937 !important; }
            .text-gray-600 { color: #4b5563 !important; }
            .font-semibold { font-weight: 600 !important; }
            .font-bold { font-weight: 700 !important; }
            .text-lg { font-size: 18px !important; }
            .border-t-2 { border-top: 2px solid #374151 !important; }
            .border-gray-400 { border-color: #9ca3af !important; }
        }
        .receipt-border {
            border: 2px solid #000;
            border-radius: 8px;
        }
        .compact-section {
            margin-bottom: 16px;
            padding: 12px;
        }
        .compact-text {
            font-size: 12px;
        }
        .compact-title {
            font-size: 16px;
            margin-bottom: 8px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Botões de controle (não imprimem) -->
    <div class="no-print fixed top-4 right-4 z-50 space-x-2">
        <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-lg">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Imprimir
        </button>
        <button onclick="window.close()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 shadow-lg">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Fechar
        </button>
    </div>

    <!-- Container principal -->
    <div class="min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Recibo Principal -->
            <div class="receipt-border bg-white p-8 mb-8">
                <!-- Cabeçalho -->
                <div class="text-center mb-6">
                    <div class="flex justify-center mb-3">
                        @if($serviceOrder->tenant->logo_path)
                            <img src="{{ $serviceOrder->tenant->logo_url }}" alt="Logo" class="h-12 w-auto">
                        @else
                            <div class="h-12 w-12 bg-blue-600 rounded-lg flex items-center justify-center">
                                <span class="text-white font-bold text-lg">Q</span>
                            </div>
                        @endif
                    </div>
                    <h1 class="text-xl font-bold text-gray-800 mb-1">ORDEM DE SERVIÇO - RECIBO DE ENTREGA</h1>
                    <p class="text-sm text-gray-600">OS #{{ $serviceOrder->number }} - {{ $serviceOrder->title }}</p>
                </div>

                <!-- Informações da Empresa -->
                <div class="compact-section bg-gray-50 rounded-lg">
                    <h3 class="compact-title font-semibold text-gray-800">Dados da Empresa</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 compact-text">
                        <div>
                            <span class="font-medium text-gray-600">Razão Social:</span>
                            <span class="text-gray-800">{{ $serviceOrder->tenant->name }}</span>
                        </div>
                        @if($serviceOrder->tenant->cnpj)
                        <div>
                            <span class="font-medium text-gray-600">CNPJ:</span>
                            <span class="text-gray-800">{{ $serviceOrder->tenant->cnpj }}</span>
                        </div>
                        @endif
                        @if($serviceOrder->tenant->phone)
                        <div>
                            <span class="font-medium text-gray-600">Telefone:</span>
                            <span class="text-gray-800">{{ $serviceOrder->tenant->phone }}</span>
                        </div>
                        @endif
                        @if($serviceOrder->tenant->email)
                        <div>
                            <span class="font-medium text-gray-600">Email:</span>
                            <span class="text-gray-800">{{ $serviceOrder->tenant->email }}</span>
                        </div>
                        @endif
                        @if($serviceOrder->tenant->address)
                        <div class="md:col-span-2">
                            <span class="font-medium text-gray-600">Endereço:</span>
                            <span class="text-gray-800">{{ $serviceOrder->tenant->address }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Informações do Cliente -->
                <div class="compact-section bg-blue-50 rounded-lg">
                    <h3 class="compact-title font-semibold text-gray-800">Dados do Cliente</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 compact-text">
                        <div>
                            <span class="font-medium text-gray-600">Nome:</span>
                            <span class="text-gray-800">{{ $serviceOrder->client->name }}</span>
                        </div>
                        @if($serviceOrder->client->document)
                        <div>
                            <span class="font-medium text-gray-600">CPF/CNPJ:</span>
                            <span class="text-gray-800">{{ $serviceOrder->client->document }}</span>
                        </div>
                        @endif
                        @if($serviceOrder->client->phone)
                        <div>
                            <span class="font-medium text-gray-600">Telefone:</span>
                            <span class="text-gray-800">{{ $serviceOrder->client->phone }}</span>
                        </div>
                        @endif
                        @if($serviceOrder->client->email)
                        <div>
                            <span class="font-medium text-gray-600">Email:</span>
                            <span class="text-gray-800">{{ $serviceOrder->client->email }}</span>
                        </div>
                        @endif
                        @if($serviceOrder->client->address)
                        <div class="md:col-span-2">
                            <span class="font-medium text-gray-600">Endereço:</span>
                            <span class="text-gray-800">{{ $serviceOrder->client->address }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Produtos/Serviços -->
                @if($serviceOrder->items && $serviceOrder->items->count() > 0)
                <div class="compact-section bg-green-50 rounded-lg">
                    <h3 class="compact-title font-semibold text-gray-800">Produtos/Serviços Realizados</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full compact-text">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="text-left py-1 px-2 font-medium text-gray-600">Item</th>
                                    <th class="text-left py-1 px-2 font-medium text-gray-600">Descrição</th>
                                    <th class="text-center py-1 px-2 font-medium text-gray-600">Qtd</th>
                                    <th class="text-right py-1 px-2 font-medium text-gray-600">Valor Unit.</th>
                                    <th class="text-right py-1 px-2 font-medium text-gray-600">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($serviceOrder->items as $item)
                                <tr class="border-b border-gray-200">
                                    <td class="py-1 px-2 text-gray-800">{{ $item->name }}</td>
                                    <td class="py-1 px-2 text-gray-800">{{ $item->description ?? '-' }}</td>
                                    <td class="py-1 px-2 text-center text-gray-800">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                                    <td class="py-1 px-2 text-right text-gray-800">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                    <td class="py-1 px-2 text-right text-gray-800 font-medium">R$ {{ number_format($item->line_total, 2, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-gray-400 bg-gray-100">
                                    <td colspan="4" class="py-2 px-2 text-right font-semibold text-gray-800">TOTAL GERAL:</td>
                                    <td class="py-2 px-2 text-right font-bold text-gray-800 text-lg">R$ {{ number_format($serviceOrder->total_amount, 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Informações do Equipamento -->
                <div class="compact-section bg-yellow-50 rounded-lg">
                    <h3 class="compact-title font-semibold text-gray-800">Equipamento Entregue</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 compact-text">
                        <div>
                            <span class="font-medium text-gray-600">Marca:</span>
                            <span class="text-gray-800">{{ $serviceOrder->equipment_brand ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-600">Modelo:</span>
                            <span class="text-gray-800">{{ $serviceOrder->equipment_model ?? 'N/A' }}</span>
                        </div>
                        @if($serviceOrder->equipment_serial)
                        <div>
                            <span class="font-medium text-gray-600">Número de Série:</span>
                            <span class="text-gray-800">{{ $serviceOrder->equipment_serial }}</span>
                        </div>
                        @endif
                        <div>
                            <span class="font-medium text-gray-600">Condição:</span>
                            <span class="text-gray-800">
                                @if($serviceOrder->equipment_condition)
                                    @switch($serviceOrder->equipment_condition)
                                        @case('perfect') Perfeito @break
                                        @case('good') Bom @break
                                        @case('damaged') Danificado @break
                                        @default {{ $serviceOrder->equipment_condition }} @break
                                    @endswitch
                                @else
                                    N/A
                                @endif
                            </span>
                        </div>
                        @if($serviceOrder->accessories_included)
                        <div class="md:col-span-2">
                            <span class="font-medium text-gray-600">Acessórios:</span>
                            <span class="text-gray-800">{{ $serviceOrder->accessories_included }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Informações do Serviço -->
                <div class="compact-section bg-purple-50 rounded-lg">
                    <h3 class="compact-title font-semibold text-gray-800">Serviço Realizado</h3>
                    <div class="compact-text">
                        @if($serviceOrder->description)
                        <div class="mb-2">
                            <span class="font-medium text-gray-600">Descrição:</span>
                            <p class="text-gray-800 mt-1">{{ $serviceOrder->description }}</p>
                        </div>
                        @endif
                        @if($serviceOrder->diagnosis)
                        <div class="mb-2">
                            <span class="font-medium text-gray-600">Diagnóstico:</span>
                            <p class="text-gray-800 mt-1">{{ $serviceOrder->diagnosis }}</p>
                        </div>
                        @endif
                        @if($serviceOrder->finalization_notes)
                        <div class="mb-2">
                            <span class="font-medium text-gray-600">Observações:</span>
                            <p class="text-gray-800 mt-1">{{ $serviceOrder->finalization_notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Informações Financeiras e Entrega -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Informações Financeiras -->
                    @if($serviceOrder->final_amount > 0)
                    <div class="compact-section bg-indigo-50 rounded-lg">
                        <h3 class="compact-title font-semibold text-gray-800">Informações Financeiras</h3>
                        <div class="compact-text space-y-1">
                            <div>
                                <span class="font-medium text-gray-600">Valor Total:</span>
                                <span class="text-gray-800 font-semibold text-lg">R$ {{ number_format($serviceOrder->final_amount, 2, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-600">Pagamento:</span>
                                <span class="text-gray-800">
                                    @if($serviceOrder->payment_method)
                                        @switch($serviceOrder->payment_method)
                                            @case('cash') Dinheiro @break
                                            @case('card') Cartão @break
                                            @case('pix') PIX @break
                                            @case('transfer') Transferência @break
                                            @default {{ $serviceOrder->payment_method }} @break
                                        @endswitch
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-600">Status:</span>
                                <span class="text-gray-800">
                                    @if($serviceOrder->payment_received)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            ✓ Recebido
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            ✗ Pendente
                                        </span>
                                    @endif
                                </span>
                            </div>
                            @if($serviceOrder->warranty_days)
                            <div>
                                <span class="font-medium text-gray-600">Garantia:</span>
                                <span class="text-gray-800">{{ $serviceOrder->warranty_days }} dias</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Informações de Entrega -->
                    <div class="compact-section bg-orange-50 rounded-lg">
                        <h3 class="compact-title font-semibold text-gray-800">Informações de Entrega</h3>
                        <div class="compact-text space-y-1">
                            <div>
                                <span class="font-medium text-gray-600">Data:</span>
                                <span class="text-gray-800">{{ $serviceOrder->finalization_date ? $serviceOrder->finalization_date->format('d/m/Y') : 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-600">Método:</span>
                                <span class="text-gray-800">
                                    @if($serviceOrder->delivery_method)
                                        @switch($serviceOrder->delivery_method)
                                            @case('pickup') Retirada pelo Cliente @break
                                            @case('delivery') Entrega @break
                                            @case('shipping') Envio por Transportadora @break
                                            @default {{ $serviceOrder->delivery_method }} @break
                                        @endswitch
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </div>
                            @if($serviceOrder->deliveredBy)
                            <div>
                                <span class="font-medium text-gray-600">Entregado por:</span>
                                <span class="text-gray-800">{{ $serviceOrder->deliveredBy->name }}</span>
                            </div>
                            @endif
                            <div>
                                <span class="font-medium text-gray-600">Finalizada por:</span>
                                <span class="text-gray-800">{{ $serviceOrder->finalizedBy->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assinaturas -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="text-center">
                        <div class="border-t-2 border-gray-400 pt-2 mb-2">
                            <span class="compact-text font-medium text-gray-600">Assinatura do Cliente</span>
                        </div>
                        @if($serviceOrder->client_signature)
                        <p class="compact-text text-gray-800">{{ $serviceOrder->client_signature }}</p>
                        @endif
                    </div>
                    <div class="text-center">
                        <div class="border-t-2 border-gray-400 pt-2 mb-2">
                            <span class="compact-text font-medium text-gray-600">Assinatura do Técnico</span>
                        </div>
                        <p class="compact-text text-gray-800">{{ $serviceOrder->finalizedBy->name ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- Rodapé -->
                <div class="mt-6 text-center compact-text text-gray-500">
                    <p>Este recibo comprova a entrega do equipamento e a conclusão do serviço.</p>
                    <p>Data de impressão: {{ now()->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-print quando a página carrega (opcional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
