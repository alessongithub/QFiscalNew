<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Relatórios Gerenciais
            </h2>
            <a href="{{ route('reports.print', ['from'=>$from->toDateString(),'to'=>$to->toDateString(),'include_receivables'=>$includeReceivables,'include_payables'=>$includePayables,'include_clients'=>$includeClients,'include_products'=>$includeProducts,'include_suppliers'=>request('include_suppliers'),'include_categories'=>request('include_categories')]) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H9.5a2 2 0 01-2-2v-2a2 2 0 012-2H17"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12h.01"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12h.01"/>
                </svg>
                Imprimir Relatório
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Filtros -->
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                <h3 class="text-white text-lg font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filtros de Relatório
                </h3>
                <p class="text-green-100 text-sm">Configure o período e dados que deseja incluir no relatório</p>
            </div>
            
            <div class="p-6">
                <form method="GET" class="space-y-6">
                    <!-- Período -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M3 11h18M5 7h14a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z"/>
                            </svg>
                            Período do Relatório
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Início</label>
                                <input type="date" name="from" value="{{ old('from', $from->toDateString()) }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" required>
            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                                <input type="date" name="to" value="{{ old('to', $to->toDateString()) }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" required>
            </div>
            </div>
            </div>

                    <!-- Seções do Relatório -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-7 4h8M7 8h10M5 6h14l-1 12a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6z"/>
                            </svg>
                            Seções do Relatório
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="flex items-center p-3 bg-white rounded-lg border hover:border-green-300 transition-colors">
                                <input type="checkbox" name="include_receivables" value="1" {{ $includeReceivables ? 'checked' : '' }} id="receivables" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded mr-3">
                                <label for="receivables" class="text-sm text-gray-700 font-medium cursor-pointer">
                                    💰 A Receber (detalhado)
                                </label>
            </div>
                            
                            <div class="flex items-center p-3 bg-white rounded-lg border hover:border-green-300 transition-colors">
                                <input type="checkbox" name="include_payables" value="1" {{ $includePayables ? 'checked' : '' }} id="payables" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded mr-3">
                                <label for="payables" class="text-sm text-gray-700 font-medium cursor-pointer">
                                    💸 A Pagar (detalhado)
                                </label>
            </div>
                            
                            <div class="flex items-center p-3 bg-white rounded-lg border hover:border-green-300 transition-colors">
                                <input type="checkbox" name="include_clients" value="1" {{ $includeClients ? 'checked' : '' }} id="clients" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded mr-3">
                                <label for="clients" class="text-sm text-gray-700 font-medium cursor-pointer">
                                    👥 Clientes
                                </label>
            </div>
                            
                            <div class="flex items-center p-3 bg-white rounded-lg border hover:border-green-300 transition-colors">
                                <input type="checkbox" name="include_products" value="1" {{ $includeProducts ? 'checked' : '' }} id="products" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded mr-3">
                                <label for="products" class="text-sm text-gray-700 font-medium cursor-pointer">
                                    📦 Produtos
                                </label>
            </div>
                            
                            <div class="flex items-center p-3 bg-white rounded-lg border hover:border-green-300 transition-colors">
                                <input type="checkbox" name="include_suppliers" value="1" {{ request('include_suppliers') ? 'checked' : '' }} id="suppliers" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded mr-3">
                                <label for="suppliers" class="text-sm text-gray-700 font-medium cursor-pointer">
                                    🏭 Fornecedores
                                </label>
    </div>

                            <div class="flex items-center p-3 bg-white rounded-lg border hover:border-green-300 transition-colors">
                                <input type="checkbox" name="include_categories" value="1" {{ request('include_categories') ? 'checked' : '' }} id="categories" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded mr-3">
                                <label for="categories" class="text-sm text-gray-700 font-medium cursor-pointer">
                                    🏷️ Categorias
                                </label>
                            </div>
            </div>
        </div>

                    <!-- Botão de Aplicar -->
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Atualizar Relatório
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resumo Executivo -->
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                <h3 class="text-white text-lg font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Resumo Executivo
                </h3>
                <p class="text-green-100 text-sm">Visão geral dos principais indicadores financeiros</p>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- A Receber -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6 border border-blue-200">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-semibold text-blue-800">💰 A Receber</h4>
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-blue-700">Em aberto</span>
                                <span class="font-semibold text-blue-800">R$ {{ number_format($recSummary['open'], 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-blue-700">Pago</span>
                                <span class="font-semibold text-green-600">R$ {{ number_format($recSummary['paid'], 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-red-700">Vencido</span>
                                <span class="font-semibold text-red-600">R$ {{ number_format($recSummary['overdue'], 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- A Pagar -->
                    <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-6 border border-red-200">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-semibold text-red-800">💸 A Pagar</h4>
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-red-700">Em aberto</span>
                                <span class="font-semibold text-red-800">R$ {{ number_format($paySummary['open'], 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-red-700">Pago</span>
                                <span class="font-semibold text-green-600">R$ {{ number_format($paySummary['paid'], 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-red-700">Vencido</span>
                                <span class="font-semibold text-red-600">R$ {{ number_format($paySummary['overdue'], 2, ',', '.') }}</span>
                            </div>
            </div>
        </div>

                    <!-- Ordens de Serviço -->
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-6 border border-purple-200">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-semibold text-purple-800">⚙️ Ordens de Serviço</h4>
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-purple-700">Abertas</span>
                                <span class="font-semibold text-purple-800">{{ $osSummary['open'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-purple-700">Em andamento</span>
                                <span class="font-semibold text-purple-800">{{ $osSummary['in_progress'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-purple-700">Finalizadas</span>
                                <span class="font-semibold text-green-600">{{ $osSummary['finished'] }}</span>
                            </div>
                            <div class="pt-2 border-t border-purple-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-purple-800">Valor total</span>
                                    <span class="font-bold text-purple-800">R$ {{ number_format($osSummary['total_value'], 2, ',', '.') }}</span>
                                </div>
            </div>
        </div>
    </div>

                    <!-- Pedidos -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-6 border border-green-200">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-semibold text-green-800">📦 Pedidos</h4>
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-green-700">Em aberto</span>
                                <span class="font-semibold text-green-800">{{ $ordersSummary['open'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-green-700">Finalizados</span>
                                <span class="font-semibold text-green-800">{{ $ordersSummary['fulfilled'] }}</span>
                            </div>
                            <div class="pt-2 border-t border-green-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-green-800">Valor total</span>
                                    <span class="font-bold text-green-800">R$ {{ number_format($ordersSummary['total_value'], 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Relatórios Detalhados -->
    @if($includeReceivables && $receivablesDetailed->count())
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-blue-600 px-6 py-4">
                <h3 class="text-white text-lg font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    A Receber - Detalhado
                </h3>
                <p class="text-blue-100 text-sm">Listagem completa das contas a receber no período</p>
            </div>
            
        <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                @foreach($receivablesDetailed as $r)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $r->description }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($r->status === 'paid')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">✓ Pago</span>
                                @elseif($r->status === 'open')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">⏳ Em aberto</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $r->status }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-mono">R$ {{ number_format($r->amount, 2, ',', '.') }}</td>
                        </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($includePayables && $payablesDetailed->count())
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-red-600 px-6 py-4">
                <h3 class="text-white text-lg font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    A Pagar - Detalhado
                </h3>
                <p class="text-red-100 text-sm">Listagem completa das contas a pagar no período</p>
            </div>
            
        <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                @foreach($payablesDetailed as $p)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($p->due_date)->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $p->description }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($p->status === 'paid')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">✓ Pago</span>
                                @elseif($p->status === 'open')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">⏳ Em aberto</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $p->status }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-mono">R$ {{ number_format($p->amount, 2, ',', '.') }}</td>
                        </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($includeClients && $clients->count())
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-indigo-600 px-6 py-4">
                <h3 class="text-white text-lg font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                    </svg>
                    Clientes
                </h3>
                <p class="text-indigo-100 text-sm">Listagem dos clientes cadastrados</p>
            </div>
            
        <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-mail</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefone</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                @foreach($clients as $c)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $c->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">{{ $c->cpf_cnpj }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $c->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $c->phone }}</td>
                        </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(($includeSuppliers ?? false) && ($suppliers->count() ?? 0))
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-orange-600 px-6 py-4">
                <h3 class="text-white text-lg font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Fornecedores
                </h3>
                <p class="text-orange-100 text-sm">Listagem dos fornecedores cadastrados</p>
            </div>
            
        <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-mail</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefone</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                @foreach($suppliers as $s)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $s->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">{{ $s->cpf_cnpj }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $s->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $s->phone }}</td>
                        </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(($includeCategories ?? false) && ($categories->count() ?? 0))
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-purple-600 px-6 py-4">
                <h3 class="text-white text-lg font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Categorias
                </h3>
                <p class="text-purple-100 text-sm">Listagem das categorias de produtos</p>
            </div>
            
        <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria Pai</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                @foreach($categories as $c)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $c->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ optional($c->parent)->name ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($c->active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">✓ Ativa</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">✗ Inativa</span>
                                @endif
                            </td>
                        </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($includeProducts && $products->count())
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-green-600 px-6 py-4">
                <h3 class="text-white text-lg font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    Produtos
                </h3>
                <p class="text-green-100 text-sm">Listagem dos produtos cadastrados</p>
            </div>
            
        <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unidade</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Preço</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                @foreach($products as $p)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $p->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">{{ $p->sku }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $p->unit }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-mono">R$ {{ number_format($p->price, 2, ',', '.') }}</td>
                        </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    </div>
</x-app-layout>