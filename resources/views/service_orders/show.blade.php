<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ordem de Serviço #{{ $serviceOrder->number }}</h2>
                    <p class="text-sm text-gray-500">{{ $serviceOrder->title }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('service_orders.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Voltar
                </a>
                @if($serviceOrder->status !== 'finished' && $serviceOrder->status !== 'canceled' && auth()->user()->hasPermission('service_orders.edit'))
                <a href="{{ route('service_orders.edit', $serviceOrder) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4l10.243-10.243a2.5 2.5 0 10-3.536-3.536L4 16v4z"/>
                    </svg>
                    Editar
                </a>
                @endif
                
                @if($serviceOrder->status !== 'finished' && $serviceOrder->status !== 'canceled' && auth()->user()->hasPermission('service_orders.finalize'))
                <a href="{{ route('service_orders.finalize_form', $serviceOrder) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Finalizar
                </a>
                @endif
                
                @if($serviceOrder->status === 'finished' && auth()->user()->hasPermission('service_orders.view'))
                <a href="{{ route('service_orders.delivery_receipt', $serviceOrder) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Recibo de Entrega
                </a>
                @endif
                
                @if($serviceOrder->is_warranty && auth()->user()->hasPermission('service_orders.view'))
                <a href="{{ route('service_orders.warranty_receipt', $serviceOrder) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Recibo de Garantia
                </a>
                
                @if(auth()->user()->hasPermission('service_orders.edit'))
                <button onclick="openRevertWarrantyModal()" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Reverter para OS Normal
                </button>
                @endif
                @endif
                
                @if($serviceOrder->status !== 'canceled' && auth()->user()->hasPermission('service_orders.cancel'))
                <button type="button" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors" onclick="openCancelModal()">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Cancelar OS
                </button>
                @endif
                
                <!-- Botões de Garantia -->
                @if($serviceOrder->status === 'finished' && auth()->user()->hasPermission('service_orders.create'))
                <button onclick="openWarrantyModal()" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Criar Garantia
                </button>
                @endif
                
                @if($serviceOrder->status === 'warranty' && auth()->user()->hasPermission('service_orders.edit'))
                <button onclick="openNotWarrantyModal()" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Não é Garantia
                </button>
                
                <button onclick="openExtendWarrantyModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Estender Garantia
                </button>
                @endif
            </div>
        </div>
    </x-slot>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Warning Alert -->
    @if(session('warning'))
    <div class="mb-6 mx-auto sm:px-6 lg:px-8">
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700 font-medium">{{ session('warning') }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                <!-- Header do Card -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Detalhes da Ordem de Serviço</h3>
                                <p class="text-blue-100 text-sm">Informações completas e auditoria</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="px-3 py-1 bg-white/20 rounded-lg">
                                <span class="text-white font-semibold text-sm">
                                    {{ ['open'=>'Em análise','in_progress'=>'Orçada','in_service'=>'Em andamento','service_finished'=>'Serviço Finalizado','warranty'=>'Garantia','no_repair'=>'Sem reparo','finished'=>'Finalizada','canceled'=>'Cancelada'][$serviceOrder->status] ?? $serviceOrder->status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Informações Básicas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cliente</label>
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span class="font-medium">{{ optional($serviceOrder->client)->name ?? 'Cliente não informado' }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Título</label>
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <span class="font-medium">{{ $serviceOrder->title }}</span>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <p class="text-gray-700">{{ $serviceOrder->description ?? 'Sem descrição' }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Valor Total</label>
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/>
                                        </svg>
                                        <span class="text-lg font-semibold text-green-600">R$ {{ number_format($serviceOrder->total_amount, 2, ',', '.') }}</span>
                                    </div>
                                    @if(($serviceOrder->discount_total ?? 0) != 0 || ($serviceOrder->addition_total ?? 0) != 0)
                                        <div class="text-sm text-gray-600 mt-1">
                                            @if(($serviceOrder->discount_total ?? 0) != 0)
                                                <span class="text-red-600">Desconto: R$ {{ number_format($serviceOrder->discount_total, 2, ',', '.') }}</span>
                                            @endif
                                            @if(($serviceOrder->addition_total ?? 0) != 0)
                                                <span class="text-green-600">Acréscimo: R$ {{ number_format($serviceOrder->addition_total, 2, ',', '.') }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Técnico Responsável</label>
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span>{{ optional($serviceOrder->technician)->name ?? 'Não atribuído' }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            @if($serviceOrder->warranty_until)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Garantia</label>
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>Até {{ \Carbon\Carbon::parse($serviceOrder->warranty_until)->format('d/m/Y') }}</span>
                                        @if(\Carbon\Carbon::parse($serviceOrder->warranty_until)->isFuture())
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">Ativa</span>
                                        @else
                                            <span class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded">Vencida</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Informações do Equipamento -->
                    @if($serviceOrder->equipment_brand || $serviceOrder->equipment_model || $serviceOrder->equipment_serial || $serviceOrder->equipment_description)
                    <div class="border-t border-gray-200 pt-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Informações do Equipamento</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if($serviceOrder->equipment_brand)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Marca</label>
                                <div class="p-3 bg-gray-50 rounded-lg">{{ $serviceOrder->equipment_brand }}</div>
                            </div>
                            @endif
                            
                            @if($serviceOrder->equipment_model)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Modelo</label>
                                <div class="p-3 bg-gray-50 rounded-lg">{{ $serviceOrder->equipment_model }}</div>
                            </div>
                            @endif
                            
                            @if($serviceOrder->equipment_serial)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Número de Série</label>
                                <div class="p-3 bg-gray-50 rounded-lg">{{ $serviceOrder->equipment_serial }}</div>
                            </div>
                            @endif
                            
                            @if($serviceOrder->equipment_description)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descrição do Equipamento</label>
                                <div class="p-3 bg-gray-50 rounded-lg">{{ $serviceOrder->equipment_description }}</div>
                            </div>
                            @endif
                        </div>
                        
                        @if($serviceOrder->defect_reported)
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Defeito Reclamado</label>
                            <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-red-800">{{ $serviceOrder->defect_reported }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Fotos do Equipamento -->
                    @if($serviceOrder->attachments->count() > 0)
                    <div class="border-t border-gray-200 pt-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Fotos do Equipamento</h3>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($serviceOrder->attachments as $attachment)
                            <div class="relative group">
                                <img src="{{ asset('storage/' . $attachment->path) }}" alt="{{ $attachment->original_name }}" class="w-full h-32 object-cover rounded-lg border border-gray-200">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100">
                                    <a href="{{ asset('storage/' . $attachment->path) }}" target="_blank" class="text-white hover:text-blue-300">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                        </svg>
                                    </a>
                                </div>
                                <p class="text-xs text-gray-600 mt-1 truncate">{{ $attachment->original_name }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Parecer Técnico -->
                    @if($serviceOrder->diagnosis)
                    <div class="border-t border-gray-200 pt-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Parecer Técnico</h3>
                        </div>
                        
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-blue-800 whitespace-pre-line">{{ $serviceOrder->diagnosis }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Orçamento -->
                    @if($serviceOrder->budget_amount || $serviceOrder->items->count() > 0)
                    <div class="border-t border-gray-200 pt-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Orçamento e Produtos/Serviços</h3>
                        </div>
                        
                        @if($serviceOrder->budget_amount)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Valor do Orçamento</label>
                            <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                    </svg>
                                    <span class="text-lg font-semibold text-green-600">R$ {{ number_format($serviceOrder->budget_amount, 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($serviceOrder->items->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Preço Unit.</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Desconto</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($serviceOrder->items as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $item->name }}</div>
                                            @if($item->description)
                                                <div class="text-xs text-gray-500">{{ $item->description }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-900">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-900">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-red-600">R$ {{ number_format($item->discount_value ?? 0, 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">R$ {{ number_format($item->line_total, 2, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="4" class="px-4 py-3 text-right text-sm font-medium text-gray-900">Subtotal:</td>
                                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">R$ {{ number_format($serviceOrder->items->sum('line_total'), 2, ',', '.') }}</td>
                                    </tr>
                                    @if(($serviceOrder->discount_total ?? 0) != 0)
                                    <tr>
                                        <td colspan="4" class="px-4 py-3 text-right text-sm font-medium text-red-600">Desconto Total:</td>
                                        <td class="px-4 py-3 text-right text-sm font-medium text-red-600">- R$ {{ number_format($serviceOrder->discount_total, 2, ',', '.') }}</td>
                                    </tr>
                                    @endif
                                    @if(($serviceOrder->addition_total ?? 0) != 0)
                                    <tr>
                                        <td colspan="4" class="px-4 py-3 text-right text-sm font-medium text-green-600">Acréscimo Total:</td>
                                        <td class="px-4 py-3 text-right text-sm font-medium text-green-600">+ R$ {{ number_format($serviceOrder->addition_total, 2, ',', '.') }}</td>
                                    </tr>
                                    @endif
                                    <tr class="border-t border-gray-300">
                                        <td colspan="4" class="px-4 py-3 text-right text-lg font-bold text-gray-900">Total Final:</td>
                                        <td class="px-4 py-3 text-right text-lg font-bold text-green-600">R$ {{ number_format($serviceOrder->total_amount, 2, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Auditoria Completa -->
                    <div class="border-t border-gray-200 pt-6">
                        <div class="flex items-center space-x-2 mb-6">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Auditoria Completa</h3>
                        </div>
                        
                        <!-- Seção de Finalização (apenas se finalizada) -->
                        @if($serviceOrder->status === 'finished')
                        <div class="bg-gradient-to-r from-green-50 to-green-100 border border-green-200 rounded-lg p-6 mb-6">
                            <div class="flex items-center mb-4">
                                <div class="p-2 bg-green-500 rounded-lg mr-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-green-800">OS Finalizada</h4>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-green-700 mb-1">Data de Finalização</label>
                                        <p class="text-green-800 font-semibold">{{ $serviceOrder->finalization_date ? $serviceOrder->finalization_date->format('d/m/Y') : 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-green-700 mb-1">Método de Entrega</label>
                                        <p class="text-green-800">
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
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-green-700 mb-1">Entregado por</label>
                                        <p class="text-green-800">{{ optional($serviceOrder->deliveredBy)->name ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-green-700 mb-1">Condição do Equipamento</label>
                                        <p class="text-green-800">
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
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-green-700 mb-1">Valor Final</label>
                                        <p class="text-green-800 font-semibold text-lg">R$ {{ number_format($serviceOrder->final_amount ?? 0, 2, ',', '.') }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-green-700 mb-1">Método de Pagamento</label>
                                        <p class="text-green-800">
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
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-green-700 mb-1">Pagamento Recebido</label>
                                        <p class="text-green-800">
                                            @if($serviceOrder->payment_received)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Sim
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Não
                                                </span>
                                            @endif
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-green-700 mb-1">Finalizada por</label>
                                        <p class="text-green-800">{{ optional($serviceOrder->finalizedBy)->name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            @if($serviceOrder->finalization_notes)
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-green-700 mb-1">Observações da Finalização</label>
                                <p class="text-green-800 bg-white p-3 rounded border">{{ $serviceOrder->finalization_notes }}</p>
                            </div>
                            @endif
                            
                            @if($serviceOrder->accessories_included)
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-green-700 mb-1">Acessórios Inclusos</label>
                                <p class="text-green-800 bg-white p-3 rounded border">{{ $serviceOrder->accessories_included }}</p>
                            </div>
                            @endif
                            
                            @if($serviceOrder->client_signature)
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-green-700 mb-1">Assinatura do Cliente</label>
                                <p class="text-green-800 bg-white p-3 rounded border">{{ $serviceOrder->client_signature }}</p>
                            </div>
                            @endif
                        </div>
                        @endif
                        
                        <!-- Seção de Cancelamento (apenas se cancelada) -->
                        @if($serviceOrder->status === 'canceled')
                        <div class="bg-gradient-to-r from-red-50 to-red-100 border border-red-200 rounded-lg p-6 mb-6">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="p-2 bg-red-500 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-red-800">OS Cancelada</h4>
                                    <p class="text-red-600 text-sm">Esta ordem de serviço foi cancelada</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-red-700 mb-1">Cancelada em</label>
                                    <p class="text-red-800">{{ $serviceOrder->cancelled_at ? $serviceOrder->cancelled_at->format('d/m/Y H:i') : 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-red-700 mb-1">Cancelada por</label>
                                    <p class="text-red-800">{{ optional($serviceOrder->cancelledBy)->name ?? 'N/A' }}</p>
                                </div>
                            </div>
                            
                            @if($serviceOrder->cancellation)
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-red-700 mb-1">Motivo do Cancelamento</label>
                                <p class="text-red-800 bg-white p-3 rounded border">{{ $serviceOrder->cancellation->cancellation_reason }}</p>
                            </div>
                            
                            @if($serviceOrder->cancellation->notes)
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-red-700 mb-1">Observações</label>
                                <p class="text-red-800 bg-white p-3 rounded border">{{ $serviceOrder->cancellation->notes }}</p>
                            </div>
                            @endif
                            
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="p-3 bg-white rounded border">
                                    <div class="text-sm font-medium text-red-700">Estoque Revertido</div>
                                    <div class="text-lg font-semibold text-red-800">
                                        {{ $serviceOrder->cancellation->stock_reversed ? 'Sim' : 'Não' }}
                                    </div>
                                </div>
                                <div class="p-3 bg-white rounded border">
                                    <div class="text-sm font-medium text-red-700">Pagamentos Revertidos</div>
                                    <div class="text-lg font-semibold text-red-800">
                                        {{ $serviceOrder->cancellation->payments_reversed ? 'Sim' : 'Não' }}
                                    </div>
                                </div>
                                <div class="p-3 bg-white rounded border">
                                    <div class="text-sm font-medium text-red-700">Garantias Canceladas</div>
                                    <div class="text-lg font-semibold text-red-800">
                                        {{ $serviceOrder->cancellation->warranties_cancelled ? 'Sim' : 'Não' }}
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            <!-- Botão de Impressão do Cancelamento -->
                            <div class="mt-6 flex justify-end">
                                <a href="{{ route('service_orders.cancellation_receipt', $serviceOrder) }}" 
                                   target="_blank"
                                   class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    Imprimir Cancelamento
                                </a>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Timeline de Eventos -->
                        <div class="space-y-4">
                            <!-- Criação -->
                            <div class="flex items-start space-x-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-medium text-blue-900">Ordem de Serviço Criada</h4>
                                        <span class="text-xs text-blue-600">{{ $serviceOrder->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <p class="text-sm text-blue-700 mt-1">
                                        Criada por <strong>{{ optional($serviceOrder->createdBy)->name ?? 'Usuário não encontrado' }}</strong>
                                    </p>
                                </div>
                            </div>

                            <!-- Orçamento -->
                            @if($serviceOrder->quoted_at)
                            <div class="flex items-start space-x-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-medium text-green-900">Orçamento Realizado</h4>
                                        <span class="text-xs text-green-600">{{ $serviceOrder->quoted_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <p class="text-sm text-green-700 mt-1">
                                        Orçada por <strong>{{ optional($serviceOrder->quotedBy)->name ?? 'Não informado' }}</strong>
                                        @if($serviceOrder->budget_amount)
                                            - Valor: <strong>R$ {{ number_format($serviceOrder->budget_amount, 2, ',', '.') }}</strong>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @endif

                            <!-- Última Atualização -->
                            @if($serviceOrder->updated_at != $serviceOrder->created_at)
                            <div class="flex items-start space-x-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-medium text-yellow-900">Última Atualização</h4>
                                        <span class="text-xs text-yellow-600">{{ $serviceOrder->updated_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <p class="text-sm text-yellow-700 mt-1">
                                        Atualizada por <strong>{{ optional($serviceOrder->updatedBy)->name ?? 'Não informado' }}</strong>
                                    </p>
                                </div>
                            </div>
                            @endif

                            <!-- Finalização -->
                            @if($serviceOrder->finalized_at)
                            <div class="flex items-start space-x-4 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-medium text-purple-900">Ordem Finalizada</h4>
                                        <span class="text-xs text-purple-600">{{ \Carbon\Carbon::parse($serviceOrder->finalized_at)->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <p class="text-sm text-purple-700 mt-1">
                                        Status atual: <strong>{{ ['open'=>'Em análise','in_progress'=>'Orçada','in_service'=>'Em andamento','service_finished'=>'Serviço Finalizado','warranty'=>'Garantia','no_repair'=>'Sem reparo','finished'=>'Finalizada','canceled'=>'Cancelada'][$serviceOrder->status] ?? $serviceOrder->status }}</strong>
                                    </p>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Histórico de Mudanças de Status -->
                        @if($serviceOrder->statusLogs->count() > 0)
                        <div class="mt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-md font-semibold text-gray-800">Histórico de Mudanças de Status</h4>
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-gray-600">Filtrar:</label>
                                    <select id="status-filter" class="text-sm border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Todos</option>
                                        <option value="open">Em análise</option>
                                        <option value="in_progress">Orçada</option>
                                        <option value="in_service">Em andamento</option>
                                        <option value="service_finished">Serviço Finalizado</option>
                                        <option value="warranty">Garantia</option>
                                        <option value="no_repair">Sem reparo</option>
                                        <option value="finished">Finalizada</option>
                                        <option value="canceled">Cancelada</option>
                                    </select>
                                </div>
                            </div>
                            <div class="space-y-3" id="status-timeline">
                                @foreach($serviceOrder->statusLogs as $log)
                                @php
                                    $new = $log->new_status;
                                    $bg = match($new){
                                        'open' => 'bg-gray-500',
                                        'in_progress' => 'bg-blue-500',
                                        'in_service' => 'bg-indigo-500',
                                        'service_finished' => 'bg-teal-500',
                                        'warranty' => 'bg-purple-500',
                                        'no_repair' => 'bg-yellow-500',
                                        'finished' => 'bg-green-600',
                                        'canceled' => 'bg-red-600',
                                        default => 'bg-gray-500'
                                    };
                                    $iconPath = match($new){
                                        'open' => 'M12 6v6l4 2',
                                        'in_progress' => 'M12 8v4m0 4h.01',
                                        'in_service' => 'M3 3h18M9 7h6m-9 4h12m-9 4h6',
                                        'service_finished' => 'M5 13l4 4L19 7',
                                        'warranty' => 'M9 12l2 2 4-4',
                                        'no_repair' => 'M6 18L18 6M6 6l12 12',
                                        'finished' => 'M5 13l4 4L19 7',
                                        'canceled' => 'M6 18L18 6M6 6l12 12',
                                        default => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'
                                    };
                                @endphp
                                <div class="flex items-start space-x-4 p-3 bg-gray-50 border border-gray-200 rounded-lg" data-status="{{ $log->new_status }}">
                                    <div class="flex-shrink-0">
                                        <div class="w-6 h-6 {{ $bg }} rounded-full flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-sm font-medium text-gray-900">
                                                @if($log->old_status)
                                                    Status alterado de <span class="text-orange-600">{{ $log->old_status_name }}</span> para <span class="text-green-600">{{ $log->new_status_name }}</span>
                                                @else
                                                    Status definido como <span class="text-green-600">{{ $log->new_status_name }}</span>
                                                @endif
                                            </h5>
                                            <span class="text-xs text-gray-500">{{ $log->changed_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1">
                                            Alterado por <strong>{{ optional($log->changedBy)->name ?? 'Usuário não encontrado' }}</strong>
                                            @if($log->reason)
                                                - Motivo: {{ $log->reason }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Timeline de Ocorrências -->
                        <div class="mt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-md font-semibold text-gray-800">Timeline de Ocorrências</h4>
                                @if(auth()->user()->hasPermission('service_orders.edit'))
                                <button onclick="openOccurrenceModal()" class="px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Adicionar Ocorrência
                                </button>
                                @endif
                            </div>
                            @if($serviceOrder->occurrences && $serviceOrder->occurrences->count() > 0)
                            <div class="space-y-3" id="occurrences-timeline">
                                @foreach($serviceOrder->occurrences as $occurrence)
                                @php
                                    $hasAttachment = false;
                                    if (isset($occurrence->attachment_url) && $occurrence->attachment_url) { $hasAttachment = true; }
                                    elseif (isset($occurrence->attachment) && $occurrence->attachment) { $hasAttachment = true; }
                                    elseif (isset($occurrence->files) && $occurrence->files) { try { $hasAttachment = count($occurrence->files) > 0; } catch (\Throwable $e) { $hasAttachment = false; } }
                                @endphp
                                <div class="flex items-start space-x-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-gray-500 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-1 text-xs rounded-full {{ $occurrence->type_color }}">{{ $occurrence->occurrence_type_name }}</span>
                                                <span class="px-2 py-1 text-xs rounded-full {{ $occurrence->priority_color }}">{{ $occurrence->priority_name }}</span>
                                                @if($occurrence->is_internal)
                                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Interna</span>
                                                @endif
                                                @if($hasAttachment)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-yellow-100 text-yellow-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79V7a2 2 0 00-2-2h-4.79M7 17l9-9m0 0l-3-3m3 3h-3"/></svg>
                                                    Anexo
                                                </span>
                                                @endif
                                            </div>
                                            <span class="text-xs text-gray-500">{{ $occurrence->created_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                        <p class="text-sm text-gray-700 mb-1">{{ $occurrence->description }}</p>
                                        <p class="text-xs text-gray-500">Por <strong>{{ $occurrence->createdBy ? $occurrence->createdBy->name : 'Sistema' }}</strong></p>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <div class="text-center py-8 text-gray-500" id="empty-occurrences">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p>Nenhuma ocorrência registrada ainda.</p>
                                <p class="text-sm">Adicione observações sobre o andamento da OS.</p>
                            </div>
                            <div class="space-y-3 hidden" id="occurrences-timeline"></div>
                            @endif
                        </div>

                        <script>
                        document.addEventListener('DOMContentLoaded', function(){
                            const sel = document.getElementById('status-filter');
                            if (sel) {
                                sel.addEventListener('change', function(){
                                    const val = this.value;
                                    document.querySelectorAll('#status-timeline [data-status]')?.forEach(function(el){
                                        if (!val) { el.classList.remove('hidden'); return; }
                                        el.classList.toggle('hidden', el.getAttribute('data-status') !== val);
                                    });
                                });
                            }
                        });
                        </script>

                        <!-- Informações de Garantia -->
                        @if($serviceOrder->is_warranty || $serviceOrder->warranty_until)
                        <div class="mt-6 border-t border-gray-200 pt-6">
                            <div class="flex items-center space-x-2 mb-4">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h4 class="text-md font-semibold text-gray-800">Informações de Garantia</h4>
                            </div>
                            
                            <div class="bg-gradient-to-r from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-purple-700 mb-1">Prazo de Garantia</label>
                                        <p class="text-purple-800 font-semibold">{{ $serviceOrder->warranty_days }} dias</p>
                                    </div>
                                    
                                    @if($serviceOrder->warranty_until)
                                    <div>
                                        <label class="block text-sm font-medium text-purple-700 mb-1">Garantia Válida Até</label>
                                        <p class="text-purple-800 font-semibold">{{ \Carbon\Carbon::parse($serviceOrder->warranty_until)->format('d/m/Y') }}</p>
                                    </div>
                                    
                                    @php
                                        $warrantyDate = \Carbon\Carbon::parse($serviceOrder->warranty_until);
                                        $daysRemaining = now()->diffInDays($warrantyDate, false);
                                        $isExpired = $daysRemaining < 0; // Negativo = vencida
                                        $daysRemaining = abs($daysRemaining);
                                    @endphp
                                    
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-purple-700 mb-1">Status da Garantia</label>
                                        @if($isExpired)
                                            <div class="inline-flex items-center px-3 py-2 rounded-lg bg-red-100 border border-red-300">
                                                <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span class="text-red-800 font-semibold">Garantia vencida há {{ round(abs($daysRemaining)) }} {{ round(abs($daysRemaining)) == 1 ? 'dia' : 'dias' }}</span>
                                            </div>
                                        @else
                                            <div class="inline-flex items-center px-3 py-2 rounded-lg bg-green-100 border border-green-300">
                                                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span class="text-green-800 font-semibold">Garantia ativa - Restam {{ round(abs($daysRemaining)) }} {{ round(abs($daysRemaining)) == 1 ? 'dia' : 'dias' }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    @endif
                                    
                                    @if($serviceOrder->warranty_notes)
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-purple-700 mb-1">Observações</label>
                                        <p class="text-purple-800 bg-white p-2 rounded border">{{ $serviceOrder->warranty_notes }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Resumo da Auditoria -->
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <div class="text-sm font-medium text-gray-700">Tempo Total</div>
                                <div class="text-lg font-semibold text-gray-900">
                                    @if($serviceOrder->finalized_at)
                                        {{ \Carbon\Carbon::parse($serviceOrder->finalized_at)->diffForHumans($serviceOrder->created_at) }}
                                    @else
                                        {{ $serviceOrder->created_at->diffForHumans() }}
                                    @endif
                                </div>
                            </div>
                            
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <div class="text-sm font-medium text-gray-700">Status Atual</div>
                                <div class="text-lg font-semibold text-gray-900">
                                    {{ ['open'=>'Em análise','in_progress'=>'Orçada','in_service'=>'Em andamento','service_finished'=>'Serviço Finalizado','warranty'=>'Garantia','no_repair'=>'Sem reparo','finished'=>'Finalizada','canceled'=>'Cancelada'][$serviceOrder->status] ?? $serviceOrder->status }}
                                </div>
                            </div>
                            
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <div class="text-sm font-medium text-gray-700">Última Atividade</div>
                                <div class="text-lg font-semibold text-gray-900">
                                    {{ $serviceOrder->updated_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Adicionar Ocorrência -->
    <div id="occurrenceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Adicionar Ocorrência</h3>
                </div>
                <form id="occurrenceForm" class="p-6">
                    @csrf
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="space-y-4">
                        <!-- Tipo de Ocorrência -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Ocorrência</label>
                            <select name="occurrence_type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="">Selecione o tipo</option>
                                <option value="client_contact">Contato com Cliente</option>
                                <option value="status_change">Mudança de Status</option>
                                <option value="technical_note">Nota Técnica</option>
                                <option value="warranty_issue">Problema na Garantia</option>
                                <option value="delivery_note">Nota de Entrega</option>
                                <option value="payment_note">Nota de Pagamento</option>
                                <option value="other">Outros</option>
                            </select>
                        </div>

                        <!-- Prioridade -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Prioridade</label>
                            <select name="priority" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="low">Baixa</option>
                                <option value="medium" selected>Média</option>
                                <option value="high">Alta</option>
                                <option value="urgent">Urgente</option>
                            </select>
                        </div>

                        <!-- Descrição -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                            <textarea name="description" rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Descreva a ocorrência..." required></textarea>
                        </div>

                        <!-- Nota Interna -->
                        <div class="flex items-center">
                            <input type="checkbox" name="is_internal" id="is_internal" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="is_internal" class="ml-2 text-sm text-gray-700">Nota interna (não visível ao cliente)</label>
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                        <button type="button" onclick="closeOccurrenceModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Adicionar Ocorrência
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Sistema de Toast Dinâmico
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toastId = 'toast-' + Date.now();
            
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };
            
            const icons = {
                success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
                error: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
                warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>',
                info: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
            };
            
            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = `${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 transform transition-all duration-300 ease-in-out translate-x-full opacity-0`;
            toast.innerHTML = `
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${icons[type]}
                </svg>
                <span class="flex-1">${message}</span>
                <button onclick="hideToast('${toastId}')" class="ml-4 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            `;
            
            container.appendChild(toast);
            
            // Animar entrada
            setTimeout(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
            }, 100);
            
            // Auto-remover após 5 segundos
            setTimeout(() => {
                hideToast(toastId);
            }, 5000);
        }
        
        function hideToast(toastId) {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }
        }
        
        // Verificar mensagens de sessão
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                showToast('{{ session('success') }}', 'success');
            @endif
            
            @if(session('error'))
                showToast('{{ session('error') }}', 'error');
            @endif
            
            @if(session('warning'))
                showToast('{{ session('warning') }}', 'warning');
            @endif
            
            @if(session('info'))
                showToast('{{ session('info') }}', 'info');
            @endif
        });
        
        // ===== FUNÇÕES DO MODAL DE OCORRÊNCIAS =====
        
        function openOccurrenceModal() {
            document.getElementById('occurrenceModal').classList.remove('hidden');
        }
        
        function closeOccurrenceModal() {
            document.getElementById('occurrenceModal').classList.add('hidden');
            document.getElementById('occurrenceForm').reset();
        }
        
        // Submissão do formulário de ocorrência
        document.getElementById('occurrenceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Mostrar loading
            submitButton.disabled = true;
            submitButton.textContent = 'Adicionando...';
            
            fetch('{{ route("service_orders.add_occurrence", $serviceOrder) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(JSON.stringify(errorData));
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeOccurrenceModal();
                    addOccurrenceToTimeline(data.occurrence);
                } else {
                    showToast('Erro ao adicionar ocorrência', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                try {
                    const errorData = JSON.parse(error.message);
                    if (errorData.errors) {
                        const firstError = Object.values(errorData.errors)[0][0];
                        showToast(firstError, 'error');
                    } else {
                        showToast(errorData.message || 'Erro ao adicionar ocorrência', 'error');
                    }
                } catch (e) {
                    showToast('Erro ao adicionar ocorrência', 'error');
                }
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        });
        
        function addOccurrenceToTimeline(occurrence) {
            const timeline = document.getElementById('occurrences-timeline');
            const emptyState = document.getElementById('empty-occurrences');
            
            // Se não há timeline ainda, criar
            if (!timeline) {
                const occurrencesSection = document.querySelector('.mt-6');
                const timelineDiv = document.createElement('div');
                timelineDiv.className = 'space-y-3';
                timelineDiv.id = 'occurrences-timeline';
                occurrencesSection.appendChild(timelineDiv);
            }
            
            // Remover estado vazio se existir
            if (emptyState) {
                emptyState.style.display = 'none';
            }
            
            // Mostrar timeline se estava escondida
            const timelineElement = document.getElementById('occurrences-timeline');
            if (timelineElement) {
                timelineElement.classList.remove('hidden');
            }
            
            // Criar novo item da timeline
            const occurrenceElement = document.createElement('div');
            occurrenceElement.className = 'flex items-start space-x-4 p-4 bg-gray-50 border border-gray-200 rounded-lg';
            occurrenceElement.innerHTML = `
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-gray-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-1 text-xs rounded-full ${occurrence.type_color}">${occurrence.type}</span>
                            <span class="px-2 py-1 text-xs rounded-full ${occurrence.priority_color}">${occurrence.priority}</span>
                            ${occurrence.is_internal ? '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Interna</span>' : ''}
                        </div>
                        <span class="text-xs text-gray-500">${occurrence.created_at}</span>
                    </div>
                    <p class="text-sm text-gray-700 mb-1">${occurrence.description}</p>
                    <p class="text-xs text-gray-500">Por <strong>${occurrence.created_by}</strong></p>
                </div>
            `;
            
            // Adicionar no início da timeline
            const finalTimeline = document.getElementById('occurrences-timeline');
            if (finalTimeline) {
                finalTimeline.insertBefore(occurrenceElement, finalTimeline.firstChild);
            }
        }
        
        // Fechar modal ao clicar fora
        document.getElementById('occurrenceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeOccurrenceModal();
            }
        });
    </script>

    <!-- Modal de Cancelamento -->
    <div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Header -->
                <div class="flex items-center justify-between pb-3 border-b">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center">
                        <svg class="w-6 h-6 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        Cancelar OS #{{ $serviceOrder->number }}
                    </h3>
                    <button onclick="closeCancelModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="mt-4">
                    @if($serviceOrder->status === 'finished' || $serviceOrder->status === 'warranty')
                        <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-4">
                            <div class="flex">
                                <svg class="w-5 h-5 text-red-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                                <div>
                                    <h4 class="text-sm font-medium text-red-800">ATENÇÃO: OS JÁ FINALIZADA!</h4>
                                    <p class="text-sm text-red-700 mt-1">
                                        Esta OS já foi finalizada e entregue ao cliente. O cancelamento irá:
                                    </p>
                                    <ul class="text-sm text-red-700 mt-2 list-disc list-inside">
                                        <li>Reversar todo o estoque utilizado</li>
                                        <li>Cancelar recebíveis e estornar pagamentos</li>
                                        <li>Cancelar garantias ativas</li>
                                        <li><strong>Será necessário recolher o equipamento do cliente</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-4">
                            <div class="flex">
                                <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                                <div>
                                    <h4 class="text-sm font-medium text-yellow-800">Atenção!</h4>
                                    <p class="text-sm text-yellow-700">
                                        Esta ação irá cancelar permanentemente a OS #{{ $serviceOrder->number }}. 
                                        Todas as reversões necessárias serão aplicadas automaticamente.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form id="cancelForm" action="{{ route('service_orders.cancel', $serviceOrder) }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 mb-2">
                                Motivo do Cancelamento <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                id="cancellation_reason" 
                                name="cancellation_reason" 
                                rows="4" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                placeholder="Descreva detalhadamente o motivo do cancelamento..."
                                required
                                minlength="10"
                                maxlength="1000"
                            ></textarea>
                            <p class="text-xs text-gray-500 mt-1">Mínimo 10 caracteres, máximo 1000 caracteres.</p>
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Observações Adicionais
                            </label>
                            <textarea 
                                id="notes" 
                                name="notes" 
                                rows="3" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                placeholder="Observações adicionais sobre o cancelamento..."
                            ></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="confirm_cancellation" 
                                    name="confirm_cancellation" 
                                    value="1"
                                    class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                                    required
                                >
                                <span class="ml-2 text-sm text-gray-700">
                                    @if($serviceOrder->status === 'finished' || $serviceOrder->status === 'warranty')
                                        <strong>Confirmo que desejo cancelar esta OS finalizada e entendo que será necessário recolher o equipamento do cliente.</strong>
                                    @else
                                        <strong>Confirmo que desejo cancelar esta OS e entendo que esta ação não pode ser desfeita.</strong>
                                    @endif
                                </span>
                            </label>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button 
                        onclick="closeCancelModal()" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button 
                        onclick="submitCancelForm()" 
                        id="submitCancelBtn"
                        disabled
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancelar OS
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openCancelModal() {
            document.getElementById('cancelModal').classList.remove('hidden');
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').classList.add('hidden');
            // Limpar formulário
            document.getElementById('cancelForm').reset();
            document.getElementById('submitCancelBtn').disabled = true;
        }

        function submitCancelForm() {
            const reason = document.getElementById('cancellation_reason').value;
            const confirmed = document.getElementById('confirm_cancellation').checked;
            
            if (!reason || reason.length < 10) {
                alert('Por favor, descreva o motivo do cancelamento (mínimo 10 caracteres).');
                return;
            }
            
            if (!confirmed) {
                alert('Por favor, confirme que desejo cancelar a OS.');
                return;
            }

            @if($serviceOrder->status === 'finished' || $serviceOrder->status === 'warranty')
                if (!confirm('ATENÇÃO: Esta OS já foi finalizada e entregue ao cliente!\n\nO cancelamento irá:\n- Reversar estoque\n- Cancelar recebíveis\n- Cancelar garantias\n- Será necessário recolher o equipamento\n\nTem certeza que deseja continuar?')) {
                    return;
                }
            @else
                if (!confirm('Tem certeza que deseja cancelar esta OS? Esta ação não pode ser desfeita.')) {
                    return;
                }
            @endif

            // Desabilitar botão e mostrar loading
            const submitBtn = document.getElementById('submitCancelBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg class="w-4 h-4 mr-2 inline animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>Cancelando...';

            // Fazer requisição AJAX
            const formData = new FormData(document.getElementById('cancelForm'));
            
            // Adicionar CSRF token manualmente
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            console.log('Enviando dados:', Object.fromEntries(formData));
            
            fetch('{{ route("service_orders.cancel", $serviceOrder) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Resposta recebida:', response.status);
                
                if (response.ok) {
                    // Se for um redirect (status 200 com HTML), recarregar a página
                    if (response.headers.get('content-type')?.includes('text/html')) {
                        window.location.reload();
                        return;
                    }
                    return response.text();
                }
                throw new Error('Erro na requisição');
            })
            .then(data => {
                // Fechar modal
                closeCancelModal();
                
                // Mostrar mensagem de sucesso
                showToast('OS cancelada com sucesso! Todas as reversões foram aplicadas.', 'success');
                
                // Recarregar a página para mostrar o novo status
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            })
            .catch(error => {
                console.error('Erro:', error);
                
                // Reabilitar botão
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Cancelar OS';
                
                showToast('Erro ao cancelar OS. Tente novamente.', 'error');
            });
        }

        // Habilitar/desabilitar botão baseado no checkbox
        document.getElementById('confirm_cancellation').addEventListener('change', function() {
            document.getElementById('submitCancelBtn').disabled = !this.checked;
        });

        // Fechar modal ao clicar fora
        document.getElementById('cancelModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCancelModal();
            }
        });
        
        // Funções de Garantia
        function openNotWarrantyModal() {
            document.getElementById('notWarrantyModal').classList.remove('hidden');
        }
        
        function closeNotWarrantyModal() {
            document.getElementById('notWarrantyModal').classList.add('hidden');
        }
        
        function openExtendWarrantyModal() {
            document.getElementById('extendWarrantyModal').classList.remove('hidden');
        }
        
        function closeExtendWarrantyModal() {
            document.getElementById('extendWarrantyModal').classList.add('hidden');
        }
        
        function openWarrantyModal() {
            document.getElementById('warrantyModal').classList.remove('hidden');
        }
        
        function closeWarrantyModal() {
            document.getElementById('warrantyModal').classList.add('hidden');
        }
        
        function openRevertWarrantyModal() {
            document.getElementById('revertWarrantyModal').classList.remove('hidden');
        }
        
        function closeRevertWarrantyModal() {
            document.getElementById('revertWarrantyModal').classList.add('hidden');
        }
    </script>
    
    <!-- Modal: Criar Garantia -->
    <div id="warrantyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Criar Garantia</h3>
                    
                    <form action="{{ route('service_orders.create_warranty', $serviceOrder) }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Relato do Cliente/Problema</label>
                            <textarea name="warranty_reason" required rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent" placeholder="Descreva o que o cliente relatou, o problema que está ocorrendo com o equipamento, etc..."></textarea>
                            <p class="text-xs text-gray-500 mt-1">Esta informação será registrada na auditoria da OS.</p>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeWarrantyModal()" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                                Criar Garantia
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal: Reverter para OS Normal -->
    @if($serviceOrder->is_warranty)
    <div id="revertWarrantyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full意外w-full">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-red-700 mb-4">Reverter Garantia para OS Normal</h3>
                    <p class="text-gray-700 mb-4">
                        Tem certeza que deseja reverter esta OS de garantia para uma OS normal?
                        O equipamento será tratado como uma nova ordem de serviço.
                    </p>
                    
                    <form action="{{ route('service_orders.revert_warranty', $serviceOrder) }}" method="POST">
                        @csrf
                        @method('POST')
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Motivo da Reversão</label>
                            <textarea name="revert_reason" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="Ex: Cliente informou que o defeito não é coberto pela garantia"></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeRevertWarrantyModal()" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                Reverter para OS Normal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Modal: Não é Garantia -->
    <div id="notWarrantyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Alterar Status - Não é Garantia</h3>
                    
                    <form action="{{ route('service_orders.mark_not_warranty', $serviceOrder) }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Novo Status</label>
                            <select name="new_status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="in_progress">Em Andamento</option>
                                <option value="service_finished">Serviço Finalizado</option>
                            </select>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Motivo da Alteração</label>
                            <textarea name="reason" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Explique por que não é garantia..."></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeNotWarrantyModal()" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                Confirmar Alteração
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal: Estender Garantia -->
    <div id="extendWarrantyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Estender Garantia</h3>
                    
                    <form action="{{ route('service_orders.extend_warranty', $serviceOrder) }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Dias Adicionais</label>
                            <input type="number" name="additional_days" required min="1" max="365" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ex: 30">
                            <p class="text-sm text-gray-500 mt-1">Garantia atual: {{ $serviceOrder->warranty_days }} dias</p>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Motivo da Extensão</label>
                            <textarea name="reason" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Explique o motivo da extensão..."></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeExtendWarrantyModal()" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Estender Garantia
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
