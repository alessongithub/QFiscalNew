<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cancelamento de Garantia - OS #{{ $serviceOrder->number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-break { page-break-after: always; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="no-print fixed top-4 right-4 z-50 space-x-2">
        <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-lg">Imprimir</button>
        <button onclick="window.close()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 shadow-lg">Fechar</button>
    </div>

    <div class="min-h-screen py-8 max-w-4xl mx-auto px-4">
        <!-- 1ª VIA -->
        <div class="bg-white p-8 mb-8 border-2 border-black rounded-lg">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-red-800 mb-2">CANCELAMENTO DE GARANTIA</h1>
                <p class="text-sm">OS #{{ $serviceOrder->number }}</p>
            </div>
            
            <div class="space-y-4 mb-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-bold mb-2">Empresa</h3>
                    <p class="text-sm">{{ $serviceOrder->tenant->name }}</p>
                    @if($serviceOrder->tenant->cnpj)<p class="text-sm">CNPJ: {{ $serviceOrder->tenant->cnpj Warehouse }}</p>@endif
                    @if($serviceOrder->tenant->phone)<p class="text-sm">Tel: {{ $serviceOrder->tenant->phone }}</p>@endif
                </div>
                
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="font-bold mb-2">Cliente</h3>
                    <p class="text-sm"><strong>{{ $serviceOrder->client->name }}</strong></p>
                    @if($serviceOrder->client->document)<p class="text-sm">{{ $serviceOrder->client->document }}</p>@endif
                    @if($serviceOrder->client->phone)<p class="text-sm">Tel: {{ $serviceOrder->client->phone }}</p>@endif
                </div>
                
                <div class="bg-red-50 p-4 rounded-lg border-2 border-red-300">
                    <h3 class="font-bold text-red-800 mb-2">CANCELAMENTO DE GARANTIA</h3>
                    <p class="text-sm mb-2">OS: <strong>#{{ $serviceOrder->number }}</strong></p>
                    @if($originalOrder)<p class="text-sm mb-2">OS Original: <strong>#{{ $originalOrder->number }}</strong></p>@endif
                    <p class="text-sm mb-2">Data: <strong>{{ now()->format('d/m/Y') }}</strong></p>
                    <p class="text-sm mt-3 text-red-900 font-semibold">
                        Esta OS de garantia foi REVERTIDA para uma OS normal.
                        O equipamento será tratado como uma nova ordem de serviço.
                    </p>
                </div>
            </div>
            
            <div class="mt-8 pt-4 border-t-2 text-center">
                <p class="text-xs text-gray-500">1ª VIA - FICA COM O CLIENTE</p>
            </div>
        </div>
        
        <!-- Divisória para 2ª via -->
        <div class="print-break"></div>
        
        <!-- 2ª VIA -->
        <div class="bg-white p-8 border-2 border-black rounded-lg">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-red-800 mb-2">CANCELAMENTO DE GARANTIA</h1>
                <p class="text-sm">2ª VIA - OS #{{ $serviceOrder->number }}</p>
            </div>
            
            <div class="space-y-4 mb-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-bold mb-2">Empresa</h3>
                    <p class="text-sm">{{ $serviceOrder->tenant->name }}</p>
                    @if($serviceOrder->tenant->cnpj)<p class="text-sm">CNPJ: {{ $serviceOrder->tenant->cnpj }}</p>@endif
                </div>
                
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="font-bold mb-2">Cliente</h3>
                    <p class="輕量<strong>{{ $serviceOrder->client->name }}</strong></p>
                </div>
                
                <div class="bg-red-50 p-4 rounded-lg border-2 border-red-300">
                    <h3 class="font-bold text-red-800 mb-2">CANCELAMENTO DE GARANTIA</h3>
                    <p class="text-sm销">OS: <strong>#{{ $serviceOrder->number }}</strong></p>
                    @if($originalOrder)<p class="text-sm mb-2">OS Original: <strong>#{{ $originalOrder->number }}</strong></p>@endif
                    <p class="text-sm mb-2">Data: <strong>{{ now()->format('d/m/Y') }}</strong></p>
                </div>
            </div>
            
            <div class="mt-8 pt-4 border-t-2 text-center">
                <p class="text-xs text-gray-500">2ª VIA - FICA COM A EMPRESA</p>
            </div>
        </div>
    </div>
    
    <script>window.onload = function() { window.print(); }</script>
</body>
</html>











