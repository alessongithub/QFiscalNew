<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resposta - Ordem de Serviço</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full mx-4">
        <div class="text-center">
            <div class="mb-6">
                @if(strpos($message, 'aprovada') !== false)
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                @else
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                        <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                @endif
            </div>
            
            <h1 class="text-2xl font-bold text-gray-900 mb-4">
                {{ strpos($message, 'aprovada') !== false ? 'Aprovação Confirmada' : 'Reprovação Confirmada' }}
            </h1>
            
            <p class="text-gray-600 mb-6">
                {{ $message }}
            </p>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-800 mb-2">Detalhes da Ordem de Serviço</h3>
                <div class="text-sm text-gray-600 space-y-1">
                    <p><strong>Número:</strong> #{{ $serviceOrder->number }}</p>
                    <p><strong>Título:</strong> {{ $serviceOrder->title }}</p>
                    <p><strong>Cliente:</strong> {{ $serviceOrder->client->name ?? 'N/A' }}</p>
                    <p><strong>Status:</strong> 
                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                            {{ ['open'=>'Em análise','in_progress'=>'Orçada','in_service'=>'Em andamento','service_finished'=>'Serviço Finalizado','warranty'=>'Garantia','no_repair'=>'Sem reparo','finished'=>'Finalizada','canceled'=>'Cancelada'][$serviceOrder->status] ?? $serviceOrder->status }}
                        </span>
                    </p>
                </div>
            </div>
            
            <p class="text-sm text-gray-500">
                Obrigado por utilizar nossos serviços!
            </p>
        </div>
    </div>
</body>
</html>

