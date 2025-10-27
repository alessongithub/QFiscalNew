<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
            <div class="flex items-center space-x-3">
                <div class="bg-blue-100 p-2 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-xl text-gray-900">Calendário</h2>
                    <p class="text-sm text-gray-600">Gerencie eventos, contas a receber e a pagar</p>
                </div>
            </div>
            
            <!-- Navegação de Mês/Ano -->
            <div class="flex items-center space-x-4">
                <form method="GET" class="flex items-center space-x-2">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="order" value="{{ $orderNumber }}">
                    <input type="hidden" name="month" value="{{ $month == 1 ? 12 : $month - 1 }}">
                    <input type="hidden" name="year" value="{{ $month == 1 ? $year - 1 : $year }}">
                    <button type="submit" class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                </form>
                
                <div class="text-center">
                    <div class="font-semibold text-lg text-gray-900">
                        {{ ucfirst(\Carbon\Carbon::create($year, $month, 1)->locale('pt_BR')->translatedFormat('F Y')) }}
                    </div>
                </div>
                
                <form method="GET" class="flex items-center space-x-2">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="order" value="{{ $orderNumber }}">
                    <input type="hidden" name="month" value="{{ $month == 12 ? 1 : $month + 1 }}">
                    <input type="hidden" name="year" value="{{ $month == 12 ? $year + 1 : $year }}">
                    <button type="submit" class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="mt-6 bg-white p-4 rounded-xl shadow-sm border border-gray-200">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mês</label>
                    <select name="month" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($month == $m)>
                                {{ ucfirst(\Carbon\Carbon::create(2024, $m, 1)->locale('pt_BR')->translatedFormat('F')) }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
                    <input type="number" min="2020" max="2030" name="year" value="{{ $year }}" 
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">A Receber</option>
                        <option value="open" @selected(($status ?? '')==='open')>Em aberto</option>
                        <option value="paid" @selected(($status ?? '')==='paid')>Pago</option>
                        <option value="partial" @selected(($status ?? '')==='partial')>Parcial</option>
                        <option value="canceled" @selected(($status ?? '')==='canceled')>Cancelado</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número do Pedido</label>
                    <input type="text" name="order" value="{{ $orderNumber ?? '' }}" placeholder="Ex.: 000123" 
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        <span>Filtrar</span>
                    </button>
                </div>
            </form>
        </div>
    </x-slot>

    <div class="grid lg:grid-cols-4 gap-6">
        <!-- Calendar Grid -->
        <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Calendar Header -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
                <div class="grid grid-cols-7 gap-2 text-center">
                    @php $weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']; @endphp
                    @foreach($weekDays as $day)
                        <div class="text-sm font-semibold text-gray-700 py-2">{{ $day }}</div>
                    @endforeach
                </div>
            </div>
            
            <!-- Calendar Body -->
            <div class="p-4">
            <?php
                $firstWeekday = (int) $start->dayOfWeek; // 0=Dom
                $daysInMonth = (int) $end->day;
                    $today = now()->format('Y-m-d');
            ?>
            <div class="grid grid-cols-7 gap-2">
                @for ($i = 0; $i < $firstWeekday; $i++)
                        <div class="h-32 bg-gray-50 rounded-lg opacity-50"></div>
                @endfor
                @for ($d = 1; $d <= $daysInMonth; $d++)
                    <?php $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d); ?>
                        <div class="h-32 bg-white border-2 border-gray-100 hover:border-blue-200 rounded-lg p-2 overflow-y-auto transition-all duration-200 {{ $dateStr === $today ? 'border-blue-300 bg-blue-50' : '' }}">
                            <!-- Day Number -->
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm font-semibold {{ $dateStr === $today ? 'text-blue-600' : 'text-gray-900' }}">
                                    {{ $d }}
                                </div>
                                @if($dateStr === $today)
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                @endif
                            </div>
                            
                            @php 
                                $rc = $receivables->get($dateStr) ?? collect(); 
                                $pc = $payables->get($dateStr) ?? collect(); 
                                $ev = $events->get($dateStr) ?? collect(); 
                            @endphp
                            
                            <!-- Receivables -->
                        @if($rc->count())
                                <div class="mb-1 text-xs bg-green-100 text-green-800 px-2 py-1 rounded-md border border-green-200">
                                    <div class="flex items-center space-x-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>R$ {{ number_format($rc->sum('amount'), 0, ',', '.') }}</span>
                                    </div>
                                </div>
                        @endif
                            
                            <!-- Payables -->
                        @if($pc->count())
                                <div class="mb-1 text-xs bg-red-100 text-red-800 px-2 py-1 rounded-md border border-red-200">
                                    <div class="flex items-center space-x-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>R$ {{ number_format($pc->sum('amount'), 0, ',', '.') }}</span>
                                    </div>
                                </div>
                        @endif
                            
                            <!-- Events -->
                        @foreach($ev as $e)
                                <div class="mb-1 text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded-md border border-indigo-200 group">
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center space-x-1 min-w-0 flex-1">
                                            <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="truncate" title="{{ $e->notes }}">
                                                {{ $e->start_time ? substr($e->start_time,0,5).' ' : '' }}{{ $e->title }}
                                            </span>
                                        </div>
                                @if(auth()->user()->hasPermission('calendar.delete'))
                                            <form action="{{ route('calendar.events.destroy', $e) }}" method="POST" 
                                                  onsubmit="return confirm('Remover evento?')" class="ml-1">
                                    @csrf @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </button>
                                </form>
                                @endif
                                    </div>
                            </div>
                        @endforeach
                    </div>
                @endfor
            </div>
        </div>
        </div>
        <!-- Sidebar -->
        <div class="space-y-6">
            @if(auth()->user()->hasPermission('calendar.create'))
            <!-- New Event Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="bg-indigo-100 p-2 rounded-lg">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900">Novo Evento</h3>
                    </div>
                    </div>
                
                <div class="p-6">
                    <form method="POST" action="{{ route('calendar.events.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Título</label>
                            <input type="text" name="title" 
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                                   placeholder="Digite o título do evento" required />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data</label>
                            <input type="date" name="start_date" value="{{ $start->toDateString() }}" 
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Início</label>
                                <input type="time" name="start_time" 
                                       class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fim</label>
                                <input type="time" name="end_time" 
                                       class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                            <textarea name="notes" rows="3" 
                                      class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                                      placeholder="Adicione detalhes sobre o evento"></textarea>
                    </div>
                        
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span>Adicionar Evento</span>
                        </button>
                </form>
                </div>
            </div>
            @endif
            
            <!-- Legend -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-gray-50 to-slate-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="bg-gray-100 p-2 rounded-lg">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900">Legenda</h3>
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center justify-center w-8 h-8 bg-green-100 rounded-lg border border-green-200">
                                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">Contas a Receber</div>
                                <div class="text-xs text-gray-600">Valores pendentes de recebimento</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center justify-center w-8 h-8 bg-red-100 rounded-lg border border-red-200">
                                <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">Contas a Pagar</div>
                                <div class="text-xs text-gray-600">Valores pendentes de pagamento</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center justify-center w-8 h-8 bg-indigo-100 rounded-lg border border-indigo-200">
                                <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">Eventos</div>
                                <div class="text-xs text-gray-600">Compromissos e lembretes</div>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex items-center space-x-3">
                                <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-lg border border-blue-200">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">Hoje</div>
                                    <div class="text-xs text-gray-600">{{ now()->format('d/m/Y') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


