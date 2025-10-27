<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Ordem de Serviço #{{ $serviceOrder->number }}</h2>
                    <p class="text-sm text-gray-500">Modifique os dados da assistência técnica</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('service_orders.show', $serviceOrder) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Visualizar
                </a>
                <a href="{{ route('service_orders.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                <!-- Header do Card -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Editar Informações da Ordem de Serviço</h3>
                            <p class="text-blue-100 text-sm">Modifique os dados conforme necessário</p>
                        </div>
                    </div>
                </div>

                <!-- Formulário -->
                <form action="{{ route('service_orders.update', $serviceOrder) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                    @csrf @method('PUT')
                    
                    <!-- Campos ocultos -->
                    <input type="hidden" name="total_amount" value="{{ $serviceOrder->total_amount }}">
                    
                    <!-- Seção Cliente e Identificação -->
                    <div class="border-b border-gray-200 pb-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Cliente e Identificação</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        Cliente
                                        <span class="text-red-500 ml-1">*</span>
                                    </span>
                                </label>
                                <select name="client_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                                    <option value="">— Selecione um cliente —</option>
                                    @foreach($clients as $c)
                                        <option value="{{ $c->id }}" @selected(old('client_id', $serviceOrder->client_id)==$c->id)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        Número da OS
                                    </span>
                                </label>
                                <input type="text" name="number" value="{{ old('number', $serviceOrder->number) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed" readonly>
                                <p class="text-xs text-gray-500 mt-1">Número não pode ser alterado</p>
                            </div>
                        </div>
                    </div>

                    <!-- Seção Informações Gerais -->
                    <div class="border-b border-gray-200 pb-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Informações Gerais</h3>
                        </div>
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        Título do Serviço
                                        <span class="text-red-500 ml-1">*</span>
                                    </span>
                                </label>
                                <input type="text" name="title" value="{{ old('title', $serviceOrder->title) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Ex.: Reparo de smartphone" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Descrição Detalhada
                                    </span>
                                </label>
                                <textarea name="description" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Descreva detalhadamente o serviço a ser realizado...">{{ old('description', $serviceOrder->description) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Seção Equipamento -->
                    <div class="border-b border-gray-200 pb-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Informações do Equipamento</h3>
                        </div>
                        
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                            Marca
                                        </span>
                                    </label>
                                    <input type="text" name="equipment_brand" value="{{ old('equipment_brand', $serviceOrder->equipment_brand) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Ex.: Samsung">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                            Modelo
                                        </span>
                                    </label>
                                    <input type="text" name="equipment_model" value="{{ old('equipment_model', $serviceOrder->equipment_model) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Ex.: Galaxy A32">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                            Número de Série
                                        </span>
                                    </label>
                                    <input type="text" name="equipment_serial" value="{{ old('equipment_serial', $serviceOrder->equipment_serial) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Ex.: SN123456789">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Descrição do Equipamento
                                    </span>
                                </label>
                                <input type="text" name="equipment_description" value="{{ old('equipment_description', $serviceOrder->equipment_description) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Ex.: Smartphone preto 128GB, com capa protetora">
                            </div>
                        </div>
                    </div>

                    <!-- Seção Defeito e Diagnóstico -->
                    <div class="border-b border-gray-200 pb-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Defeito e Diagnóstico</h3>
                        </div>
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                        Defeito Reclamado pelo Cliente
                                    </span>
                                </label>
                                <textarea name="defect_reported" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Descreva o defeito relatado pelo cliente...">{{ old('defect_reported', $serviceOrder->defect_reported) }}</textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Parecer Técnico
                                    </span>
                                </label>
                                <textarea name="diagnosis" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Descreva o diagnóstico técnico e o que precisa ser feito...">{{ old('diagnosis', $serviceOrder->diagnosis) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Seção Orçamento e Itens -->
                    <div class="border-b border-gray-200 pb-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-800">Orçamento e Itens</h3>
                            </div>
                            <button type="button" onclick="openAddItemModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Adicionar Item
                            </button>
                        </div>
                        
                        <!-- Valor Total do Orçamento -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                    </svg>
                                    Valor Total do Orçamento
                                </span>
                            </label>
                            <div class="flex items-center space-x-4">
                                <input type="number" name="budget_amount" step="0.01" min="0" value="{{ old('budget_amount', $serviceOrder->budget_amount) }}" class="w-48 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="0,00" id="budget_amount_input">
                                <span class="text-sm text-gray-500">R$</span>
                                <div class="text-lg font-semibold text-gray-800" id="budget_amount_display">R$ {{ number_format(old('budget_amount', $serviceOrder->budget_amount), 2, ',', '.') }}</div>
                            </div>
                        </div>

                        <!-- Lista de Itens -->
                        <div class="space-y-4">
                            <h4 class="text-md font-medium text-gray-700">Itens do Orçamento</h4>
                            <div id="items_list" class="space-y-3">
                                @if($serviceOrder->items && $serviceOrder->items->count() > 0)
                                    @foreach($serviceOrder->items as $item)
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200" data-item-id="{{ $item->id }}">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-4">
                                                    <div class="flex-1">
                                                        <h5 class="font-medium text-gray-800">{{ $item->name }}</h5>
                                                        @if($item->description)
                                                            <p class="text-sm text-gray-600">{{ $item->description }}</p>
                                                        @endif
                                                    </div>
                                                    <div class="text-sm text-gray-600">
                                                        <span class="font-medium">{{ $item->quantity }}</span>
                                                        @if($item->unit)
                                                            <span>{{ $item->unit }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-sm text-gray-600">
                                                        R$ {{ number_format($item->unit_price, 2, ',', '.') }}
                                                    </div>
                                                    @if($item->discount_value > 0)
                                                        <div class="text-sm text-red-600">
                                                            -R$ {{ number_format($item->discount_value, 2, ',', '.') }}
                                                        </div>
                                                    @endif
                                                    <div class="text-sm font-medium text-gray-800">
                                                        R$ {{ number_format($item->line_total, 2, ',', '.') }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2 ml-4">
                                                <button type="button" onclick="editItem({{ $item->id }})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Editar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </button>
                                                <button type="button" onclick="removeItem({{ $item->id }})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Remover">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                @else
                                    <div class="text-center py-8 text-gray-500">
                                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                        <p>Nenhum item adicionado ainda</p>
                                        <p class="text-sm">Clique em "Adicionar Item" para começar o orçamento</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Seção Documentação -->
                    <div class="border-b border-gray-200 pb-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Documentação</h3>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Adicionar Fotos do Equipamento
                                </span>
                            </label>
                            <input type="file" name="photos[]" multiple accept="image/*" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <p class="text-xs text-gray-500 mt-1">Selecione até 10 fotos do equipamento (JPG, PNG, GIF)</p>
                            
                            @if($serviceOrder->attachments->count() > 0)
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fotos Existentes</label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    @foreach($serviceOrder->attachments as $attachment)
                                    <div class="relative group">
                                        <img src="{{ asset('storage/' . $attachment->path) }}" alt="{{ $attachment->original_name }}" class="w-full h-24 object-cover rounded-lg border border-gray-200">
                                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100">
                                            <a href="{{ asset('storage/' . $attachment->path) }}" target="_blank" class="text-white hover:text-blue-300">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        </div>
                    </div>

                    <!-- Seção Status e Aprovação -->
                    <div class="border-b border-gray-200 pb-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Status e Aprovação</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Status da Ordem
                                    </span>
                                </label>
                                <select name="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="open" @selected(old('status', $serviceOrder->status)==='open')>Em análise</option>
                                    <option value="in_progress" @selected(old('status', $serviceOrder->status)==='in_progress')>Orçada</option>
                                    <option value="in_service" @selected(old('status', $serviceOrder->status)==='in_service')>Em andamento</option>
                                    <option value="service_finished" @selected(old('status', $serviceOrder->status)==='service_finished')>Serviço Finalizado</option>
                                    <option value="warranty" @selected(old('status', $serviceOrder->status)==='warranty')>Garantia</option>
                                    <option value="no_repair" @selected(old('status', $serviceOrder->status)==='no_repair')>Sem reparo</option>
                                    <option value="finished" @selected(old('status', $serviceOrder->status)==='finished')>Finalizada</option>
                                    <option value="canceled" @selected(old('status', $serviceOrder->status)==='canceled')>Cancelada</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        Técnico Responsável
                                    </span>
                                </label>
                                <select name="technician_user_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="">— Selecione um técnico —</option>
                                    @foreach($technicians as $tech)
                                        <option value="{{ $tech->id }}" @selected(old('technician_user_id', $serviceOrder->technician_user_id)==$tech->id)>{{ $tech->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Status de Aprovação
                                    </span>
                                </label>
                                <div class="px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                                    @php
                                        $approvalStatus = old('approval_status', $serviceOrder->approval_status);
                                        $statusLabels = [
                                            'awaiting' => 'Aguardando',
                                            'customer_notified' => 'Cliente Avisado',
                                            'approved' => 'Aprovada',
                                            'not_approved' => 'Não Aprovada'
                                        ];
                                        $statusColors = [
                                            'awaiting' => 'bg-yellow-100 text-yellow-800',
                                            'customer_notified' => 'bg-blue-100 text-blue-800',
                                            'approved' => 'bg-green-100 text-green-800',
                                            'not_approved' => 'bg-red-100 text-red-800'
                                        ];
                                    @endphp
                                    <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$approvalStatus] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $statusLabels[$approvalStatus] ?? 'Não definido' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if($serviceOrder->approval_status && in_array($serviceOrder->approval_status, ['customer_notified', 'approved', 'not_approved']))
                        <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                            <h4 class="text-sm font-medium text-blue-800 mb-2">Informações de Aprovação</h4>
                            <div class="text-sm text-blue-700 space-y-1">
                                @if($serviceOrder->approved_at)
                                    <div><strong>Aprovada em:</strong> {{ \Carbon\Carbon::parse($serviceOrder->approved_at)->format('d/m/Y H:i') }}</div>
                                @endif
                                @if($serviceOrder->approved_by_email)
                                    <div><strong>Aprovada por:</strong> {{ $serviceOrder->approved_by_email }}</div>
                                @endif
                                @if($serviceOrder->rejected_at)
                                    <div><strong>Rejeitada em:</strong> {{ \Carbon\Carbon::parse($serviceOrder->rejected_at)->format('d/m/Y H:i') }}</div>
                                @endif
                                @if($serviceOrder->rejected_by_email)
                                    <div><strong>Rejeitada por:</strong> {{ $serviceOrder->rejected_by_email }}</div>
                                @endif
                                @if($serviceOrder->notified_at)
                                    <div><strong>Notificada em:</strong> {{ \Carbon\Carbon::parse($serviceOrder->notified_at)->format('d/m/Y H:i') }}</div>
                                @endif
                            </div>
                        </div>
                        @endif

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Observações de Aprovação
                                </span>
                            </label>
                            <textarea name="approval_notes" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Observações sobre a aprovação...">{{ old('approval_notes', $serviceOrder->approval_notes) }}</textarea>
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="flex items-center justify-end space-x-4 pt-6">
                        <a href="{{ route('service_orders.show', $serviceOrder) }}" class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancelar
                        </a>
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar/Editar Item -->
    <div id="itemModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-800" id="modalTitle">Adicionar Item</h3>
                        <button type="button" onclick="closeItemModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form id="itemForm" class="space-y-6">
                        @csrf
                        <input type="hidden" id="item_id" name="item_id">
                        <input type="hidden" id="service_order_id" name="service_order_id" value="{{ $serviceOrder->id }}">

                        <!-- Busca de Produto/Serviço -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    Produto/Serviço
                                    <span class="text-red-500 ml-1">*</span>
                                </span>
                            </label>
                            <div class="relative">
                                <input type="text" id="product_search" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Digite o nome do produto ou serviço..." autocomplete="off">
                                <div id="product_suggestions" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                            </div>
                            <input type="hidden" id="product_id" name="product_id">
                        </div>


                        <!-- Descrição -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Descrição
                                </span>
                            </label>
                            <textarea id="item_description" name="description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Descrição detalhada do item..."></textarea>
                        </div>

                        <!-- Quantidade e Preço -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        Quantidade
                                        <span class="text-red-500 ml-1">*</span>
                                    </span>
                                </label>
                                <div class="flex items-center space-x-2">
                                    <button type="button" onclick="decrementQuantity()" class="px-3 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                        </svg>
                                    </button>
                                    <input type="number" id="item_quantity" name="quantity" step="0.001" min="0.001" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="1" required>
                                    <button type="button" onclick="incrementQuantity()" class="px-3 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                    </button>
                                    <input type="text" id="item_unit" name="unit" class="w-20 px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="UN">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                        </svg>
                                        Preço Unitário
                                        <span class="text-red-500 ml-1">*</span>
                                    </span>
                                </label>
                                <input type="number" id="item_unit_price" name="unit_price" step="0.01" min="0" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="0,00" required>
                            </div>
                        </div>

                        <!-- Desconto -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                    </svg>
                                    Desconto por Item
                                </span>
                            </label>
                            <input type="number" id="item_discount" name="discount_value" step="0.01" min="0" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="0,00">
                        </div>

                        <!-- Valor Total -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700">Valor Total do Item:</span>
                                <span class="text-lg font-semibold text-gray-800" id="item_total_display">R$ 0,00</span>
                            </div>
                        </div>

                        <!-- Botões -->
                        <div class="flex items-center justify-end space-x-4 pt-6">
                            <button type="button" onclick="closeItemModal()" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <span id="saveButtonText">Adicionar Item</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variáveis globais
        let currentItemId = null;
        let products = [];

        // Carregar produtos ao inicializar
        document.addEventListener('DOMContentLoaded', function() {
            setupEventListeners();
        });

        // Configurar event listeners
        function setupEventListeners() {
            // Busca de produtos
            const productSearch = document.getElementById('product_search');
            productSearch.addEventListener('input', handleProductSearch);
            productSearch.addEventListener('blur', () => {
                setTimeout(() => {
                    hideSuggestions();
                }, 200);
            });

            // Cálculo automático
            document.getElementById('item_quantity').addEventListener('input', calculateTotal);
            document.getElementById('item_unit_price').addEventListener('input', calculateTotal);
            document.getElementById('item_discount').addEventListener('input', calculateTotal);

            // Formulário
            document.getElementById('itemForm').addEventListener('submit', handleItemSubmit);

            // Budget amount
            document.getElementById('budget_amount_input').addEventListener('input', updateBudgetDisplay);
        }

        // Busca de produtos
        async function handleProductSearch(e) {
            const query = e.target.value.trim();
            if (query.length < 2) {
                hideSuggestions();
                return;
            }

            try {
                const response = await fetch(`/api/products/search?term=${encodeURIComponent(query)}`);
                const products = await response.json();
                showSuggestions(products);
            } catch (error) {
                console.error('Erro ao buscar produtos:', error);
                hideSuggestions();
            }
        }

        // Mostrar sugestões
        function showSuggestions(products) {
            const container = document.getElementById('product_suggestions');
            container.innerHTML = '';

            if (products.length === 0) {
                const searchTerm = document.getElementById('product_search').value;
                container.innerHTML = `
                    <div class="p-3 text-gray-500">Nenhum produto encontrado</div>
                    <div class="p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 bg-blue-25" onclick="useAvulsoProduct('${searchTerm}')">
                        <div class="font-medium text-blue-800">+ Usar "${searchTerm}" como produto avulso</div>
                        <div class="text-sm text-blue-600">Produto não cadastrado</div>
                    </div>
                `;
            } else {
                products.forEach(product => {
                    const div = document.createElement('div');
                    div.className = 'p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0';
                    div.innerHTML = `
                        <div class="font-medium text-gray-800">${product.name}</div>
                        <div class="text-sm text-gray-600">
                            R$ ${parseFloat(product.price).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                            ${product.balance !== undefined ? ` | Saldo: ${product.balance}` : ''}
                        </div>
                    `;
                    div.addEventListener('click', () => selectProduct(product));
                    container.appendChild(div);
                });
            }

            container.classList.remove('hidden');
        }

        // Esconder sugestões
        function hideSuggestions() {
            document.getElementById('product_suggestions').classList.add('hidden');
        }

        // Usar produto avulso
        function useAvulsoProduct(name) {
            document.getElementById('product_search').value = name;
            document.getElementById('product_id').value = '';
            hideSuggestions();
            calculateTotal();
        }

        // Selecionar produto
        function selectProduct(product) {
            console.log('Produto selecionado:', product);
            document.getElementById('product_search').value = product.name;
            document.getElementById('product_id').value = product.id;
            document.getElementById('item_unit_price').value = product.price;
            document.getElementById('item_unit').value = product.unit || 'UN';
            hideSuggestions();
            calculateTotal();
            
            // Debug: verificar se o campo foi preenchido
            console.log('Product ID definido:', document.getElementById('product_id').value);
        }

        // Incrementar quantidade
        function incrementQuantity() {
            const quantityInput = document.getElementById('item_quantity');
            const currentValue = parseFloat(quantityInput.value) || 0;
            quantityInput.value = currentValue + 1;
            calculateTotal();
        }

        // Decrementar quantidade
        function decrementQuantity() {
            const quantityInput = document.getElementById('item_quantity');
            const currentValue = parseFloat(quantityInput.value) || 0;
            if (currentValue > 0.001) {
                quantityInput.value = currentValue - 1;
                calculateTotal();
            }
        }

        // Calcular total do item
        function calculateTotal() {
            const quantity = parseFloat(document.getElementById('item_quantity').value) || 0;
            const unitPrice = parseFloat(document.getElementById('item_unit_price').value) || 0;
            const discount = parseFloat(document.getElementById('item_discount').value) || 0;
            
            const subtotal = quantity * unitPrice;
            const total = subtotal - discount;
            
            document.getElementById('item_total_display').textContent = 
                'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        }

        // Atualizar display do budget
        function updateBudgetDisplay() {
            const value = parseFloat(document.getElementById('budget_amount_input').value) || 0;
            document.getElementById('budget_amount_display').textContent = 
                'R$ ' + value.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        }

        // Abrir modal para adicionar item
        function openAddItemModal() {
            currentItemId = null;
            document.getElementById('modalTitle').textContent = 'Adicionar Item';
            document.getElementById('saveButtonText').textContent = 'Adicionar Item';
            document.getElementById('itemForm').reset();
            document.getElementById('product_id').value = '';
            document.getElementById('item_total_display').textContent = 'R$ 0,00';
            document.getElementById('itemModal').classList.remove('hidden');
        }

        // Editar item
        function editItem(itemId) {
            // Implementar edição de item
            console.log('Editar item:', itemId);
        }

        // Remover item
        function removeItem(itemId) {
            if (confirm('Tem certeza que deseja remover este item?')) {
                // Implementar remoção de item
                console.log('Remover item:', itemId);
            }
        }

        // Fechar modal
        function closeItemModal() {
            document.getElementById('itemModal').classList.add('hidden');
        }

        // Submeter formulário
        async function handleItemSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            // Debug: verificar dados antes do envio
            console.log('Dados do formulário:', data);
            console.log('Product ID no formulário:', data.product_id);
            
            // Definir o nome do item
            if (data.product_id) {
                // Se tem product_id, usar o nome do produto selecionado
                data.name = document.getElementById('product_search').value;
            } else {
                // Se não tem product_id, usar o valor da busca como nome
                data.name = document.getElementById('product_search').value;
            }
            
            try {
                const response = await fetch(`/service_orders/${data.service_order_id}/items`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(data)
                });

                if (response.ok) {
                    location.reload(); // Recarregar página para mostrar novo item
                } else {
                    alert('Erro ao adicionar item');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao adicionar item');
            }
        }
    </script>

    <!-- Sistema de Toast Simplificado -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    <script>
        // Função simplificada de toast
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;
            
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
            
            toast.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 transform translate-x-full transition-transform duration-300`;
            toast.innerHTML = `
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="font-medium">${message}</span>
            `;
            
            container.appendChild(toast);
            
            // Animar entrada
            setTimeout(() => toast.classList.remove('translate-x-full'), 100);
            
            // Remover após 3 segundos
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Verificar mensagens de sessão quando a página carrega
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                showToast('{{ session('success') }}', 'success');
                setTimeout(() => {
                    window.location.href = '{{ route('service_orders.index') }}';
                }, 2000);
            @endif
            
            @if(session('error'))
                showToast('{{ session('error') }}', 'error');
            @endif
            
            @if($errors->any())
                @foreach($errors->all() as $error)
                    showToast('{{ $error }}', 'error');
                @endforeach
            @endif
        });
    </script>
</x-app-layout>