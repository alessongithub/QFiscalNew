<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelamento de OS #{{ $serviceOrder->number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page {
                margin: 0.2in;
                size: A4;
            }
            body { 
                margin: 0; 
                font-size: 7px;
                line-height: 1.0;
                font-family: Arial, sans-serif;
            }
            .no-print { display: none !important; }
            .print-page { 
                width: 100%;
                height: 100vh;
                padding: 0;
                box-sizing: border-box;
                overflow: hidden;
            }
            .print-header {
                border-bottom: 1px solid #dc2626;
                padding-bottom: 1px;
                margin-bottom: 1px;
            }
            .print-section {
                margin-bottom: 0px;
                padding: 0px;
                border: 0px solid #e5e7eb;
                background-color: #fef2f2;
            }
            .print-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 0px;
                font-size: 6px;
            }
            .print-table th,
            .print-table td {
                border: 1px solid #d1d5db;
                padding: 0px;
                text-align: left;
            }
            .print-table th {
                background-color: #f3f4f6;
                font-weight: bold;
                font-size: 5px;
            }
            .print-footer {
                margin-top: 1px;
                border-top: 1px solid #dc2626;
                padding-top: 1px;
            }
            .signature-line {
                border-bottom: 1px solid #000;
                width: 60px;
                margin: 1px 0 0px 0;
            }
            h1 { font-size: 8px; margin: 0px 0; }
            h2 { font-size: 7px; margin: 0px 0; }
            h3 { font-size: 6px; margin: 0px 0; }
            p { margin: 0px 0; font-size: 6px; }
            .grid { gap: 0px; }
            .mb-6 { margin-bottom: 1px; }
            .mb-4 { margin-bottom: 0px; }
            .mb-2 { margin-bottom: 0px; }
            .mt-4 { margin-top: 0px; }
            .mt-3 { margin-top: 0px; }
            .mt-2 { margin-top: 0px; }
            .p-1 { padding: 0px; }
            .py-2 { padding-top: 0px; padding-bottom: 0px; }
            .px-4 { padding-left: 0px; padding-right: 0px; }
            .text-xs { font-size: 5px; }
            .text-sm { font-size: 6px; }
            .text-base { font-size: 7px; }
            .text-lg { font-size: 8px; }
            .text-xl { font-size: 9px; }
            .text-2xl { font-size: 10px; }
            .leading-relaxed { line-height: 1.1; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <!-- Botão de Impressão -->
        <div class="no-print mb-6">
            <button onclick="window.print()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimir Cancelamento
            </button>
        </div>

        <!-- Conteúdo do Recibo -->
        <div class="print-page bg-white shadow-lg rounded-lg p-1">
            <!-- Cabeçalho da Empresa -->
            <div class="print-header text-center mb-4">
                <h1 class="text-base font-bold text-gray-800 mb-1">{{ $serviceOrder->tenant->name ?? 'Empresa' }}</h1>
                @if($serviceOrder->tenant->cnpj)
                <p class="text-xs text-gray-600">CNPJ: {{ $serviceOrder->tenant->cnpj }}</p>
                @endif
                @if($serviceOrder->tenant->address)
                <p class="text-xs text-gray-600">{{ $serviceOrder->tenant->address }}</p>
                @endif
                @if($serviceOrder->tenant->phone)
                <p class="text-xs text-gray-600">Tel: {{ $serviceOrder->tenant->phone }}</p>
                @endif
                
                <div class="mt-2">
                    <h2 class="text-sm font-bold text-red-600 mb-1">CANCELAMENTO DE ORDEM DE SERVIÇO</h2>
                    <p class="text-xs text-gray-700">OS #{{ $serviceOrder->number }}</p>
                </div>
            </div>

            <!-- Dados do Cliente -->
            <div class="print-section mb-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">DADOS DO CLIENTE</h3>
                <div class="grid grid-cols-4 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Nome/Razão Social</label>
                        <p class="text-gray-900 font-semibold text-xs">{{ $serviceOrder->client->name ?? 'N/A' }}</p>
                    </div>
                    @if($serviceOrder->client && $serviceOrder->client->document)
                    <div>
                        <label class="block text-xs font-medium text-gray-700">CPF/CNPJ</label>
                        <p class="text-gray-900 text-xs">{{ $serviceOrder->client->document }}</p>
                    </div>
                    @endif
                    @if($serviceOrder->client && $serviceOrder->client->phone)
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Telefone</label>
                        <p class="text-gray-900 text-xs">{{ $serviceOrder->client->phone }}</p>
                    </div>
                    @endif
                    @if($serviceOrder->client && $serviceOrder->client->email)
                    <div>
                        <label class="block text-xs font-medium text-gray-700">E-mail</label>
                        <p class="text-gray-900 text-xs">{{ $serviceOrder->client->email }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Dados do Equipamento -->
            @if($serviceOrder->equipment_brand || $serviceOrder->equipment_model || $serviceOrder->equipment_serial)
            <div class="print-section mb-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">DADOS DO EQUIPAMENTO</h3>
                <div class="grid grid-cols-4 gap-2">
                    @if($serviceOrder->equipment_brand)
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Marca</label>
                        <p class="text-gray-900 text-xs">{{ $serviceOrder->equipment_brand }}</p>
                    </div>
                    @endif
                    @if($serviceOrder->equipment_model)
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Modelo</label>
                        <p class="text-gray-900 text-xs">{{ $serviceOrder->equipment_model }}</p>
                    </div>
                    @endif
                    @if($serviceOrder->equipment_serial)
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Número de Série</label>
                        <p class="text-gray-900 text-xs">{{ $serviceOrder->equipment_serial }}</p>
                    </div>
                    @endif
                    @if($serviceOrder->equipment_description)
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Descrição</label>
                        <p class="text-gray-900 text-xs">{{ $serviceOrder->equipment_description }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Serviços Realizados -->
            @if($serviceOrder->items && $serviceOrder->items->count() > 0)
            <div class="print-section mb-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">SERVIÇOS E PRODUTOS</h3>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Descrição</th>
                            <th class="text-center">Qtd</th>
                            <th class="text-right">Valor Unit.</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($serviceOrder->items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->description ?? '-' }}</td>
                            <td class="text-center">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                            <td class="text-right">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                            <td class="text-right">R$ {{ number_format($item->line_total, 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100">
                            <td colspan="4" class="text-right font-semibold">VALOR TOTAL:</td>
                            <td class="text-right font-bold text-lg">R$ {{ number_format($serviceOrder->total_amount, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif

            <!-- Informações de Pagamento -->
            <div class="print-section mb-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">INFORMAÇÕES DE PAGAMENTO</h3>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Valor Pago</label>
                        <p class="text-gray-900 font-semibold text-sm">R$ {{ number_format($serviceOrder->total_amount, 2, ',', '.') }}</p>
                    </div>
                    @if($serviceOrder->payment_method)
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Forma de Pagamento</label>
                        <p class="text-gray-900 text-xs">
                            @switch($serviceOrder->payment_method)
                                @case('cash')
                                    Dinheiro
                                    @break
                                @case('card')
                                    Cartão
                                    @break
                                @case('pix')
                                    PIX
                                    @break
                                @case('transfer')
                                    Transferência
                                    @break
                                @case('boleto')
                                    Boleto Bancário
                                    @break
                                @case('mixed')
                                    Pagamento Misto
                                    @break
                                @default
                                    {{ ucfirst($serviceOrder->payment_method) }}
                            @endswitch
                        </p>
                    </div>
                    @endif
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Data do Cancelamento</label>
                        <p class="text-gray-900 text-xs">{{ $serviceOrder->cancelled_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Motivo do Cancelamento -->
            <div class="print-section mb-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">MOTIVO DO CANCELAMENTO</h3>
                <div class="bg-white p-4 rounded border border-gray-300">
                    <p class="text-gray-900">{{ $serviceOrder->cancellation->cancellation_reason ?? 'Não informado' }}</p>
                </div>
            </div>

            <!-- Declaração de Cancelamento -->
            <div class="print-section mb-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">DECLARAÇÃO</h3>
                <div class="bg-white p-4 rounded border border-gray-300">
                    <p class="text-gray-900 text-justify leading-relaxed">
                        A <strong>{{ $serviceOrder->tenant->name ?? 'Empresa' }}</strong> declara que está cancelando a Ordem de Serviço #{{ $serviceOrder->number }} 
                        conforme motivo descrito acima. 
                        
                        @if($serviceOrder->total_amount > 0)
                        O valor de <strong>R$ {{ number_format($serviceOrder->total_amount, 2, ',', '.') }}</strong> será devolvido ao cliente 
                        {{ $serviceOrder->client->name ?? '' }} através do mesmo método de pagamento utilizado.
                        @endif
                        
                        @if($serviceOrder->items && $serviceOrder->items->count() > 0)
                        Os produtos utilizados no serviço serão devolvidos ao estoque da empresa.
                        @endif
                        
                        Este cancelamento foi realizado em {{ $serviceOrder->cancelled_at->format('d/m/Y H:i') }} por {{ $serviceOrder->cancelledBy->name ?? 'N/A' }}.
                    </p>
                </div>
            </div>

            <!-- Observações -->
            @if($serviceOrder->cancellation && $serviceOrder->cancellation->notes)
            <div class="print-section mb-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">OBSERVAÇÕES</h3>
                <div class="bg-white p-4 rounded border border-gray-300">
                    <p class="text-gray-900">{{ $serviceOrder->cancellation->notes }}</p>
                </div>
            </div>
            @endif

            <!-- Assinaturas -->
            <div class="print-footer">
                <div class="grid grid-cols-2 gap-6 mt-4">
                    <div class="text-center">
                        <div class="signature-line"></div>
                        <p class="text-xs text-gray-600">Responsável pela Empresa</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $serviceOrder->cancelledBy->name ?? 'N/A' }}</p>
                    </div>
                    <div class="text-center">
                        <div class="signature-line"></div>
                        <p class="text-xs text-gray-600">Cliente</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $serviceOrder->client->name ?? 'N/A' }}</p>
                    </div>
                </div>
                
                <div class="mt-4 text-center text-xs text-gray-500">
                    <p>Documento gerado em {{ now()->format('d/m/Y H:i') }}</p>
                    <p>{{ $serviceOrder->tenant->name ?? 'Empresa' }} - OS #{{ $serviceOrder->number }}</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
