<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="bg-green-100 p-2 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900">Histórico de Vendas</h2>
                    <p class="text-sm text-gray-600">Gerencie todas as vendas realizadas no PDV</p>
                </div>
            </div>
            @php
                $lm = (bool) config('app.limited_mode', false);
                $isFree = optional(auth()->user()->tenant?->plan)->slug === 'free';
                $disabled = ($lm || $isFree);
            @endphp
            <a href="{{ route('pos.index') }}" class="inline-flex items-center px-5 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg shadow-lg hover:from-green-700 hover:to-green-800 transition-all transform hover:scale-105 {{ $disabled ? 'opacity-60 pointer-events-none cursor-not-allowed' : '' }}" title="{{ $disabled ? 'Indisponível no seu plano' : '' }}">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nova Venda
            </a>
        </div>
    </x-slot>

    @php $printerType = \App\Models\Setting::get('print.printer_type','thermal_80'); @endphp
    
    <!-- Filtros -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <form method="GET" action="{{ route('pos.sales') }}" class="space-y-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filtros de Busca
                </h3>
                @if(request()->anyFilled(['search', 'status', 'client_id', 'date_from', 'date_to', 'min_amount', 'max_amount']))
                <a href="{{ route('pos.sales') }}" class="text-sm text-red-600 hover:text-red-800 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Limpar Filtros
                </a>
                @endif
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Busca -->
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Número do pedido, cliente ou CPF/CNPJ" class="w-full border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                
                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">Todos</option>
                        <option value="paid" @selected(request('status')==='paid')>Pago</option>
                        <option value="pending" @selected(request('status')==='pending')>Pendente</option>
                        <option value="canceled" @selected(request('status')==='canceled')>Cancelado</option>
                    </select>
                </div>
                
                <!-- Cliente -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                    <select name="client_id" class="w-full border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">Todos</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected(request('client_id')==$client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Data De -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data De</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                
                <!-- Data Até -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Até</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                
                <!-- Valor Mínimo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor Mínimo</label>
                    <input type="number" step="0.01" min="0" name="min_amount" value="{{ request('min_amount') }}" placeholder="0,00" class="w-full border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                
                <!-- Valor Máximo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor Máximo</label>
                    <input type="number" step="0.01" min="0" name="max_amount" value="{{ request('max_amount') }}" placeholder="0,00" class="w-full border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-3 pt-4 border-t">
                <button type="submit" class="inline-flex items-center px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Filtrar
                </button>
            </div>
        </form>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">#</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Data/Hora</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orders as $o)
                    @php
                        $hasPaid = \App\Models\Receivable::where('tenant_id', auth()->user()->tenant_id)->where('order_id', $o->id)->where('status','paid')->exists();
                        $hasReturns = \App\Models\ReturnModel::where('tenant_id', auth()->user()->tenant_id)->where('order_id', $o->id)->exists();
                        $isCanceled = $o->status === 'canceled' || !empty($o->canceled_at);
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg bg-green-100">
                                    <span class="text-green-700 font-bold text-sm">#{{ $o->id }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div class="text-sm font-medium text-gray-900">{{ optional($o->client)->name ?? 'Consumidor Final' }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ optional($o->created_at)->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500">{{ optional($o->created_at)->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-bold text-green-600">R$ {{ number_format($o->total_amount, 2, ',', '.') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($isCanceled)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    Cancelado
                                </span>
                            @elseif($hasPaid)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Pago
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                    Pendente
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                <a href="{{ route('pos.receipt', $o) }}" target="_blank" class="inline-flex items-center px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition-colors duration-150" title="Visualizar Recibo">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </a>
                                @php $printRoute = in_array($printerType, ['thermal_58','thermal_80']) ? route('pos.print80', $o) : route('pos.print', $o); @endphp
                                <a href="{{ $printRoute }}" target="_blank" class="inline-flex items-center px-3 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-lg transition-colors duration-150" title="Imprimir">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                </a>
                                @if(!$isCanceled)
                                <a href="{{ route('returns.create', ['order'=>$o->id]) }}" class="inline-flex items-center px-3 py-2 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 rounded-lg transition-colors duration-150" title="Devolução">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h4l3 10h8l3-10h-4M5 6h14M7 6l1-2h8l1 2"/>
                                    </svg>
                                </a>
                                @endif
                                @if(!$hasPaid && !$hasReturns)
                                <a href="{{ route('pos.reopen', $o) }}" class="inline-flex items-center px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg transition-colors duration-150" title="Reabrir no PDV">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19l14-14"/>
                                    </svg>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-gray-600 font-medium text-lg">Nenhuma venda encontrada</p>
                                <p class="text-gray-500 text-sm mt-1">Realize vendas no PDV para visualizá-las aqui</p>
                            </div>
                    </td>
                </tr>
                    @endforelse
            </tbody>
        </table>
        </div>
        
        @if($orders->hasPages())
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</x-app-layout>


