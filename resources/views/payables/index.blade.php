<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Contas a Pagar</h2>
            <a href="{{ route('payables.create') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Nova Conta
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif

    <div class="bg-white p-4 rounded shadow">
        @php
            $today = now()->toDateString();
            $weekStart = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString();
            $weekEnd = \Carbon\Carbon::now()->endOfWeek(\Carbon\Carbon::SUNDAY)->toDateString();
            $isOverdue = request('overdue');
            $isToday = request('date_from') === $today && request('date_to') === $today && !$isOverdue;
            $isWeek = request('date_from') === $weekStart && request('date_to') === $weekEnd && !$isOverdue;
        @endphp
        <div class="mb-4 flex flex-wrap gap-2">
            <a href="{{ route('payables.index', ['date_from'=>$today,'date_to'=>$today,'status'=>'open']) }}"
               class="inline-flex items-center px-3 py-1.5 rounded-full border text-sm {{ $isToday ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                Hoje
            </a>
            <a href="{{ route('payables.index', ['date_from'=>$weekStart,'date_to'=>$weekEnd,'status'=>'open']) }}"
               class="inline-flex items-center px-3 py-1.5 rounded-full border text-sm {{ $isWeek ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                Esta semana
            </a>
            <a href="{{ route('payables.index', ['overdue'=>1]) }}"
               class="inline-flex items-center px-3 py-1.5 rounded-full border text-sm {{ $isOverdue ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                Vencidos
            </a>
        </div>
        <form method="GET" class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-4">
                <label class="block text-xs text-gray-600">Status</label>
                <select name="status" class="w-full border rounded p-2">
                    <option value="">Todos</option>
                    @foreach(['open' => 'Em aberto','paid' => 'Pago','canceled' => 'Estornado'] as $k=>$v)
                        <option value="{{ $k }}" @selected(($status ?? '') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600">Somente vencidos</label>
                <label class="inline-flex items-center gap-2 p-2 border rounded">
                    <input type="checkbox" name="overdue" value="1" {{ request('overdue') ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700">Vencidos</span>
                </label>
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs text-gray-600">Data de</label>
                <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs text-gray-600">Data até</label>
                <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs text-gray-600">Ordenar por</label>
                <select name="sort" class="w-full border rounded p-2 min-w-[200px]">
                    <option value="due_date" @selected((request('sort','due_date')==='due_date'))>Vencimento</option>
                    <option value="amount" @selected(request('sort')==='amount')>Valor</option>
                    <option value="created_at" @selected(request('sort')==='created_at')>Cadastro</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600">Direção</label>
                <select name="direction" class="w-full border rounded p-2 min-w-[200px]">
                    <option value="desc" @selected(request('direction','desc')==='desc')>Decrescente</option>
                    <option value="asc" @selected(request('direction')==='asc')>Crescente</option>
                </select>
            </div>
            <div class="md:col-span-3 md:col-start-10">
                <label class="block text-xs text-gray-600">Mostrar</label>
                <select name="per_page" class="w-full border rounded p-2 min-w-[200px]">
                    @foreach([10,12,25,50,100,200] as $opt)
                        <option value="{{ $opt }}" @selected((int)request('per_page',12)===$opt)>{{ $opt }} por página</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-12 flex items-end justify-end gap-2">
                <button class="px-3 py-2 bg-gray-800 text-white rounded">Filtrar</button>
                <a href="{{ route('payables.index') }}" class="px-3 py-2 border rounded text-gray-700 bg-white">Limpar</a>
            </div>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <div class="p-3 border rounded bg-yellow-50">
                <div class="text-xs text-gray-600">Em aberto</div>
                <div class="text-lg font-semibold">R$ {{ number_format($totalOpen ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="p-3 border rounded bg-red-50">
                <div class="text-xs text-gray-600">Vencido</div>
                <div class="text-lg font-semibold">R$ {{ number_format($totalOverdue ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="p-3 border rounded bg-green-50">
                <div class="text-xs text-gray-600">Pago</div>
                <div class="text-lg font-semibold">R$ {{ number_format($totalPaid ?? 0, 2, ',', '.') }}</div>
            </div>
        </div>
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left border-b">
                <th class="py-2">Fornecedor</th>
                <th>Descrição</th>
                <th>Vencimento</th>
                <th>Valor</th>
                <th>Status</th>
                <th class="text-right">Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($payables as $p)
                @php
                    $isEstornoAuto = str_contains($p->supplier_name, 'Estorno Financeiro') && str_contains($p->description, 'Estorno de recebimento');
                    $isEstornoManual = str_contains($p->supplier_name, 'Estorno Financeiro') && str_contains($p->description, 'Estorno de pagamento');
                    
                    // Verificar se este payable foi estornado (tem um estorno manual correspondente)
                    $isEstornado = false;
                    if (!$isEstornoAuto && !$isEstornoManual && $p->status !== 'canceled') {
                        // Verificar se este payable específico tem um estorno manual
                        // Busca por estornos que mencionam exatamente este fornecedor E este ID
                        $estornoManual = \App\Models\Payable::where('tenant_id', $p->tenant_id)
                            ->where('supplier_name', 'Estorno Financeiro')
                            ->where('description', 'like', '%🔄 Estorno Manual - ' . $p->supplier_name . ' (ID: ' . $p->id . ')%')
                            ->first();
                        
                        $isEstornado = $estornoManual ? true : false;
                    }
                @endphp
                
                @if($isEstornoAuto)
                    <!-- Estorno Automático -->
                    <tr class="border-b bg-red-50">
                        <td class="py-2">
                            <span class="text-red-700 font-medium">Estorno Automático</span>
                        </td>
                        <td>
                            <div class="text-red-600">{{ $p->description }}</div>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($p->due_date)->format('d/m/Y') }}</td>
                        <td>
                            <span class="text-red-600 font-medium">
                                R$ {{ number_format($p->amount, 2, ',', '.') }}
                            </span>
                        </td>
                        <td>
                            <span class="px-2 py-1 rounded text-white text-xs bg-green-600">Pago</span>
                        </td>
                        <td class="text-right">
                            <!-- Sem ações para estornos automáticos -->
                        </td>
                    </tr>
                @elseif($isEstornoManual)
                    <!-- Estorno Manual -->
                    <tr class="border-b bg-orange-50">
                        <td class="py-2">
                            <span class="text-orange-700 font-medium">Estorno Manual</span>
                        </td>
                        <td>
                            <div class="text-orange-600">{{ $p->description }}</div>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($p->due_date)->format('d/m/Y') }}</td>
                        <td>
                            <span class="text-orange-600 font-medium">
                                R$ {{ number_format($p->amount, 2, ',', '.') }}
                            </span>
                        </td>
                        <td>
                            <span class="px-2 py-1 rounded text-white text-xs bg-green-600">Pago</span>
                        </td>
                        <td class="text-right">
                            <!-- Sem ações para estornos manuais -->
                        </td>
                    </tr>
                @else
                    <!-- Payable Normal -->
                    @php
                        $isOverdue = $p->status === 'open' && \Carbon\Carbon::parse($p->due_date)->isPast();
                        $isCanceled = $p->status === 'canceled';
                        $isReversed = $p->status === 'reversed';
                    @endphp
                    <tr class="border-b {{ $isEstornado ? 'bg-gray-100' : ($isOverdue ? 'bg-red-50' : ($isCanceled ? 'bg-gray-50' : ($isReversed ? 'bg-orange-50' : ''))) }}">
                        <td class="py-2">
                            @if($isEstornado)
                                <span class="text-gray-600 font-medium">{{ $p->supplier_name }}</span>
                            @elseif($isReversed)
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                    </svg>
                                    <span class="text-orange-700 font-medium">{{ $p->supplier_name }}</span>
                                </div>
                            @elseif($isCanceled)
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    <span class="text-gray-600 font-medium">{{ $p->supplier_name }}</span>
                                </div>
                            @elseif($isOverdue)
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                    <span class="text-red-700 font-medium">{{ $p->supplier_name }}</span>
                                </div>
                            @else
                                <span class="text-gray-900">{{ $p->supplier_name }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="{{ $isEstornado ? 'text-gray-500' : ($isReversed ? 'text-orange-700' : ($isCanceled ? 'text-gray-500' : ($isOverdue ? 'text-red-700' : 'text-gray-900'))) }}">{{ $p->description }}</div>
                        </td>
                        <td>
                            @if($isOverdue)
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-red-700 font-medium">{{ \Carbon\Carbon::parse($p->due_date)->format('d/m/Y') }}</span>
                                </div>
                            @else
                                {{ \Carbon\Carbon::parse($p->due_date)->format('d/m/Y') }}
                            @endif
                        </td>
                        <td>
                            <span class="{{ $isEstornado ? 'text-gray-500' : ($isReversed ? 'text-orange-700 font-medium' : ($isCanceled ? 'text-gray-500' : ($isOverdue ? 'text-red-700 font-medium' : 'text-gray-900'))) }}">
                                R$ {{ number_format($p->amount, 2, ',', '.') }}
                            </span>
                        </td>
                        <td>
                            @php 
                                $statusMap = [
                                    'open' => 'Em aberto',
                                    'paid' => 'Pago',
                                    'canceled' => 'Cancelado',
                                    'reversed' => 'Estornado'
                                ];
                                $statusClass = $p->status === 'paid' ? 'bg-green-600' : ($p->status === 'open' ? 'bg-yellow-600' : ($p->status === 'canceled' ? 'bg-red-600' : ($p->status === 'reversed' ? 'bg-orange-600' : 'bg-gray-600')));
                            @endphp
                            <span class="px-2 py-1 rounded text-white text-xs {{ $statusClass }}">{{ $statusMap[$p->status] ?? $p->status }}</span>
                        </td>
                        <td class="text-right">
                            <div class="inline-flex items-center gap-2">
                                @if(auth()->user()->hasPermission('payables.view'))
                                <a href="{{ route('payables.show', $p) }}" title="Visualizar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-gray-50 hover:bg-gray-100 text-gray-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                @endif
                                @if(auth()->user()->hasPermission('payables.edit') && !$isEstornado && !$isCanceled && !$isReversed && $p->status !== 'paid')
                                <a href="{{ route('payables.edit', $p) }}" title="Editar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-blue-50 hover:bg-blue-100 text-blue-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4l10.243-10.243a2.5 2.5 0 10-3.536-3.536L4 16v4z"/></svg>
                                </a>
                                @endif
                                @if(auth()->user()->hasPermission('payables.edit') && $p->status === 'open')
                                <button onclick="openCancelModal{{ $p->id }}()" title="Cancelar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-red-50 hover:bg-red-100 text-red-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                @endif
                                @if($p->status === 'open' && auth()->user()->hasPermission('payables.pay') && !$isEstornado && !$isCanceled && !$isReversed)
                                <form action="{{ route('payables.pay', $p) }}" method="POST" class="inline" onsubmit="return confirmPayment({{ $p->id }}, '{{ $p->supplier_name }}', {{ $p->amount }})">
                                    @csrf
                                    <button title="Pagar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-green-50 hover:bg-green-100 text-green-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </form>
                                @endif
                                @if($p->status === 'paid' && 
                                    auth()->user()->hasPermission('payables.create') &&
                                    !$isEstornado &&
                                    !$isReversed &&
                                    !str_contains($p->supplier_name, 'Estorno Financeiro') && 
                                    !str_contains($p->description, '⚡ Estorno Automático') &&
                                    !str_contains($p->description, '🔄 Estorno Manual'))
                                <button onclick="openReverseModal{{ $p->id }}()" title="Estornar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-orange-50 hover:bg-orange-100 text-orange-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="6" class="py-6 text-center text-gray-500">Nenhum registro</td></tr>
            @endforelse
            </tbody>
        </table>

        <div class="mt-4">{{ $payables->links() }}</div>
    </div>

    <!-- Modais de Estorno -->
    @foreach($payables as $p)
        @if($p->status === 'paid' && 
            auth()->user()->hasPermission('payables.create') &&
            !$isEstornado &&
            !$isReversed &&
            !str_contains($p->supplier_name, 'Estorno Financeiro') && 
            !str_contains($p->description, '⚡ Estorno Automático') &&
            !str_contains($p->description, '🔄 Estorno Manual'))
        <div id="reverseModal{{ $p->id }}" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Estornar Pagamento</h3>
                    </div>
                    <form action="{{ route('payables.reverse', $p) }}" method="POST">
                        @csrf
                        <div class="px-6 py-4">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Fornecedor: <span class="font-semibold">{{ $p->supplier_name }}</span>
                                </label>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Valor: <span class="font-semibold">R$ {{ number_format($p->amount, 2, ',', '.') }}</span>
                                </label>
                            </div>
                            <div class="mb-4">
                                <label for="reverse_reason{{ $p->id }}" class="block text-sm font-medium text-gray-700 mb-2">
                                    Motivo do Estorno <span class="text-red-500">*</span>
                                </label>
                                <textarea 
                                    id="reverse_reason{{ $p->id }}" 
                                    name="reverse_reason" 
                                    rows="3" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Descreva o motivo do estorno (mínimo 10 caracteres)"
                                    required
                                    minlength="10"
                                    maxlength="500"
                                ></textarea>
                                <div class="text-xs text-gray-500 mt-1">
                                    <span id="charCount{{ $p->id }}">0</span>/500 caracteres
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                            <button type="button" onclick="closeReverseModal{{ $p->id }}()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 border border-transparent rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                                Confirmar Estorno
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function openReverseModal{{ $p->id }}() {
            document.getElementById('reverseModal{{ $p->id }}').classList.remove('hidden');
        }
        
        function closeReverseModal{{ $p->id }}() {
            document.getElementById('reverseModal{{ $p->id }}').classList.add('hidden');
            document.getElementById('reverse_reason{{ $p->id }}').value = '';
            document.getElementById('charCount{{ $p->id }}').textContent = '0';
        }
        
        // Contador de caracteres
        document.getElementById('reverse_reason{{ $p->id }}').addEventListener('input', function() {
            const count = this.value.length;
            document.getElementById('charCount{{ $p->id }}').textContent = count;
        });
        </script>
        @endif
        
        <!-- Modal de Cancelamento -->
        @if(auth()->user()->hasPermission('payables.edit') && $p->status === 'open')
        <div id="cancelModal{{ $p->id }}" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Cancelar Conta a Pagar</h3>
                    </div>
                    <form action="{{ route('payables.cancel', $p) }}" method="POST">
                        @csrf
                        <div class="px-6 py-4">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Fornecedor: <span class="font-semibold">{{ $p->supplier_name }}</span>
                                </label>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Valor: <span class="font-semibold">R$ {{ number_format($p->amount, 2, ',', '.') }}</span>
                                </label>
                            </div>
                            <div class="mb-4">
                                <label for="cancel_reason{{ $p->id }}" class="block text-sm font-medium text-gray-700 mb-2">
                                    Motivo do Cancelamento <span class="text-red-500">*</span>
                                </label>
                                <textarea 
                                    id="cancel_reason{{ $p->id }}" 
                                    name="cancel_reason" 
                                    rows="3" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                    placeholder="Descreva o motivo do cancelamento (mínimo 10 caracteres)"
                                    required
                                    minlength="10"
                                    maxlength="500"
                                ></textarea>
                                <div class="text-xs text-gray-500 mt-1">
                                    <span id="cancelCharCount{{ $p->id }}">0</span>/500 caracteres
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                            <button type="button" onclick="closeCancelModal{{ $p->id }}()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-red-500">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                Confirmar Cancelamento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function openCancelModal{{ $p->id }}() {
            document.getElementById('cancelModal{{ $p->id }}').classList.remove('hidden');
        }
        
        function closeCancelModal{{ $p->id }}() {
            document.getElementById('cancelModal{{ $p->id }}').classList.add('hidden');
            document.getElementById('cancel_reason{{ $p->id }}').value = '';
            document.getElementById('cancelCharCount{{ $p->id }}').textContent = '0';
        }
        
        // Contador de caracteres
        document.getElementById('cancel_reason{{ $p->id }}').addEventListener('input', function() {
            const count = this.value.length;
            document.getElementById('cancelCharCount{{ $p->id }}').textContent = count;
        });
        </script>
        @endif
    @endforeach

    <script>
        function confirmPayment(id, supplier, amount) {
            const formattedAmount = new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(amount);
            
            return confirm(`Confirma o pagamento desta conta?\n\nFornecedor: ${supplier}\nValor: ${formattedAmount}\n\nEsta ação será registrada na auditoria.`);
        }
    </script>
</x-app-layout>


