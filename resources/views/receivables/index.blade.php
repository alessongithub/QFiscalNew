<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Contas a Receber</h2>
            <a href="{{ route('receivables.create') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Novo Recebível
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif

    <div class="bg-white p-4 rounded shadow" x-data="{ showReceive:false }">
        @php
            $today = now()->toDateString();
            $weekStart = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString();
            $weekEnd = \Carbon\Carbon::now()->endOfWeek(\Carbon\Carbon::SUNDAY)->toDateString();
            $isOverdue = request('overdue');
            $isToday = request('date_from') === $today && request('date_to') === $today && !$isOverdue;
            $isWeek = request('date_from') === $weekStart && request('date_to') === $weekEnd && !$isOverdue;
        @endphp
        <div class="mb-4 flex flex-wrap gap-2">
            <a href="{{ route('receivables.index', ['date_from'=>$today,'date_to'=>$today,'status'=>['open','partial']]) }}"
               class="inline-flex items-center px-3 py-1.5 rounded-full border text-sm {{ $isToday ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                Hoje
            </a>
            <a href="{{ route('receivables.index', ['date_from'=>$weekStart,'date_to'=>$weekEnd,'status'=>['open','partial']]) }}"
               class="inline-flex items-center px-3 py-1.5 rounded-full border text-sm {{ $isWeek ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                Esta semana
            </a>
            <a href="{{ route('receivables.index', ['overdue'=>1]) }}"
               class="inline-flex items-center px-3 py-1.5 rounded-full border text-sm {{ $isOverdue ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                Vencidos
            </a>
        </div>
        <form method="GET" class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-4">
                <label class="block text-xs text-gray-600">Status</label>
                <select name="status" class="w-full border rounded p-2">
                    <option value="">Todos</option>
                    @foreach(['open' => 'Em aberto','paid' => 'Pago','reversed' => 'Estornado'] as $k=>$v)
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
                <a href="{{ route('receivables.index') }}" class="px-3 py-2 border rounded text-gray-700 bg-white">Limpar</a>
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
                <th class="py-2 w-8"><input type="checkbox" onclick="toggleAll(this)"></th>
                <th class="py-2">Descrição</th>
                <th>Cliente</th>
                <th>Vencimento</th>
                <th>Valor</th>
                <th>Status</th>
                <th class="text-right">Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($receivables as $r)
                @php
                    $isOverdue = $r->status === 'open' && \Carbon\Carbon::parse($r->due_date)->isPast();
                    $isCanceled = $r->status === 'canceled';
                    $isReversed = $r->status === 'reversed';
                    $isFromOrder = !empty($r->order_id) || 
                                   str_contains($r->description, 'Pedido') || 
                                   str_contains($r->description, 'PDV') ||
                                   str_contains($r->description, 'pagamento à vista');
                @endphp
                <tr class="border-b {{ $isOverdue ? 'bg-red-50' : ($isCanceled ? 'bg-gray-50' : ($isReversed ? 'bg-orange-50' : '')) }}">
                    <td class="py-2">
                        @if(in_array($r->status, ['open','partial']))
                        <input type="checkbox" name="ids[]" value="{{ $r->id }}">
                        @endif
                    </td>
                    <td class="py-2">
                        <div class="{{ $isReversed ? 'text-orange-700' : ($isCanceled ? 'text-gray-500' : ($isOverdue ? 'text-red-700' : 'text-gray-900')) }}">{{ $r->description }}</div>
                        @if($isFromOrder)
                            <div class="text-xs text-blue-600 mt-1">📋 Vinculado a pedido</div>
                        @endif
                    </td>
                    <td>
                        @if($isReversed)
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                                <span class="text-orange-700 font-medium">{{ optional($r->client)->name ?? '—' }}</span>
                            </div>
                        @elseif($isCanceled)
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <span class="text-gray-600 font-medium">{{ optional($r->client)->name ?? '—' }}</span>
                            </div>
                        @elseif($isOverdue)
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                                <span class="text-red-700 font-medium">{{ optional($r->client)->name ?? '—' }}</span>
                            </div>
                        @else
                            <span class="text-gray-900">{{ optional($r->client)->name ?? '—' }}</span>
                        @endif
                    </td>
                    <td>
                        @if($isOverdue)
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-red-700 font-medium">{{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }}</span>
                            </div>
                        @else
                            {{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }}
                        @endif
                    </td>
                    <td>
                        <span class="{{ $isReversed ? 'text-orange-700 font-medium' : ($isCanceled ? 'text-gray-500' : ($isOverdue ? 'text-red-700 font-medium' : 'text-gray-900')) }}">
                            R$ {{ number_format($r->amount, 2, ',', '.') }}
                        </span>
                    </td>
                    <td>
                        @php 
                            $statusMap = [
                                'open' => 'Em aberto',
                                'partial' => 'Parcial',
                                'paid' => 'Pago',
                                'canceled' => 'Cancelado',
                                'reversed' => 'Estornado'
                            ];
                            $statusClass = $r->status === 'paid' ? 'bg-green-600' : ($r->status === 'open' ? 'bg-yellow-600' : ($r->status === 'canceled' ? 'bg-red-600' : ($r->status === 'reversed' ? 'bg-orange-600' : 'bg-gray-600')));
                        @endphp
                        <span class="px-2 py-1 rounded text-white text-xs {{ $statusClass }}">{{ $statusMap[$r->status] ?? $r->status }}</span>
                    </td>
                    <td class="text-right">
                        <div class="inline-flex items-center gap-2">
                            @if(auth()->user()->hasPermission('receivables.view'))
                            <a href="{{ route('receivables.show', $r) }}" title="Visualizar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-gray-50 hover:bg-gray-100 text-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            @endif
                            
                            @if($r->status !== 'canceled' && $r->status !== 'reversed')
                                @if(auth()->user()->hasPermission('receivables.edit') && $r->status !== 'paid' && !$isFromOrder)
                                <a href="{{ route('receivables.edit', $r) }}" title="Editar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-blue-50 hover:bg-blue-100 text-blue-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4l10.243-10.243a2.5 2.5 0 10-3.536-3.536L4 16v4z"/></svg>
                                </a>
                                @endif
                                @if($r->status !== 'paid' && auth()->user()->hasPermission('receivables.receive'))
                                @php
                                    $lm = (bool) config('app.limited_mode', false);
                                    $isFree = optional(auth()->user()->tenant?->plan)->slug === 'free';
                                    $boletoDisabled = ($lm || $isFree);
                                @endphp
                                <button type="button"
                                        title="Emitir boleto"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded bg-purple-50 hover:bg-purple-100 text-purple-700 {{ $boletoDisabled ? 'opacity-60 cursor-not-allowed' : '' }}"
                                        data-id="{{ $r->id }}"
                                        data-due="{{ \Carbon\Carbon::parse($r->due_date)->toDateString() }}"
                                        data-fine="{{ (float) (\App\Models\Setting::get('boleto.fine_percent', 0)) }}"
                                        data-interest="{{ (float) (\App\Models\Setting::get('boleto.interest_month_percent', 0)) }}"
                                        data-action="{{ route('receivables.emit_boleto', $r) }}"
                                        {{ $boletoDisabled ? 'disabled' : '' }}
                                        onclick="{{ $boletoDisabled ? 'return false;' : 'openBoletoModal(this)' }}"
                                        title="{{ $boletoDisabled ? 'Indisponível no seu plano' : 'Emitir boleto' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 2h12a2 2 0 012 2v16a2 2 0 01-2 2H6a2 2 0 01-2-2V4a2 2 0 012-2zm2 6h8m-8 4h8m-8 4h8"/></svg>
                                </button>
                                @if($boletoDisabled)
                                    <a href="{{ route('plans.upgrade') }}" class="text-xs text-green-700 hover:underline">Upgrade</a>
                                @endif
                                @if(!empty($r->boleto_url))
                                    <a href="{{ $r->boleto_url }}" target="_blank" rel="noopener" title="Ver boleto" class="inline-flex items-center justify-center w-8 h-8 rounded bg-purple-50 hover:bg-purple-100 text-purple-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3v4a1 1 0 001 1h4M5 13l4 4L19 7"/></svg>
                                    </a>
                                @endif
                                @endif
                                @if($r->status !== 'paid' && auth()->user()->hasPermission('receivables.receive'))
                                <button type="button"
                                        title="Baixar"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded bg-green-50 hover:bg-green-100 text-green-700"
                                        data-action="{{ route('receivables.receive', $r) }}"
                                        data-desc="{{ e($r->description) }}"
                                        data-client="{{ e(optional($r->client)->name ?? '—') }}"
                                        data-amount="{{ number_format($r->amount, 2, ',', '.') }}"
                                        onclick="openReceiveModalFromButton(this)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                @endif
                                @if($r->status === 'paid' && 
                                    auth()->user()->hasPermission('receivables.create') &&
                                    !$isReversed &&
                                    !$isFromOrder)
                                <button onclick="openReverseModal{{ $r->id }}()" title="Estornar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-orange-50 hover:bg-orange-100 text-orange-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('receivables.delete') && $r->status === 'open' && !$isFromOrder)
                                <button onclick="openCancelModal{{ $r->id }}()" title="Cancelar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-red-50 hover:bg-red-100 text-red-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 text-center text-gray-500">Nenhum registro</td></tr>
            @endforelse
            </tbody>
        </table>

        </table>

        <div class="mt-4 flex items-center justify-between">
            <div class="text-sm text-gray-600">Selecione títulos em aberto para baixa em lote.</div>
            <button type="button" onclick="openBulkModal()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Baixar selecionados</button>
        </div>

        <!-- Form dedicado para baixa em lote (inputs adicionados dinamicamente) -->
        <form id="bulkForm" method="POST" action="{{ route('receivables.bulk_receive') }}">@csrf</form>

        <div class="mt-4">{{ $receivables->links() }}</div>

        <!-- Modal Boleto -->
        <x-modal name="boleto-modal" maxWidth="md">
            <div class="p-4">
                <div class="text-lg font-semibold mb-3">Emitir boleto</div>
                <form id="boletoForm" action="#" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600">Vencimento</label>
                        <input id="boleto_due" type="date" name="due_date" class="w-full border rounded p-2" required>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Multa (%)</label>
                        <input id="boleto_fine" type="number" step="0.01" min="0" max="2" name="fine_percent" class="w-full border rounded p-2" placeholder="0">
                        <div class="text-[11px] text-gray-500 mt-1">Até 2% conforme legislação.</div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Juros ao mês (%)</label>
                        <input id="boleto_interest" type="number" step="0.01" min="0" max="1" name="interest_month_percent" class="w-full border rounded p-2" placeholder="0">
                        <div class="text-[11px] text-gray-500 mt-1">Máx. 1% ao mês (~0,033% ao dia).</div>
                    </div>
                    <div class="md:col-span-2 flex items-center gap-2">
                        <input type="hidden" name="send_email" value="1">
                        <input id="boleto_send_email" type="checkbox" value="1" checked>
                        <label for="boleto_send_email" class="text-sm text-gray-700">Enviar boleto por e-mail ao cliente</label>
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-2 pt-1">
                        <button type="button" class="px-3 py-2 border rounded text-gray-700 bg-white" onclick="window.dispatchEvent(new CustomEvent('close-modal', { detail: 'boleto-modal' }))">Cancelar</button>
                        <button class="px-3 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">Emitir</button>
                    </div>
                </form>
            </div>
        </x-modal>

        <!-- Modal de Baixa -->
        <x-modal name="receive-modal" maxWidth="md">
            <div class="p-4">
                <div class="text-lg font-semibold mb-3">Confirmar baixa</div>

                <div class="mb-3 text-sm text-gray-700">
                    <div><span class="font-semibold">Descrição:</span> <span id="receiveDesc">—</span></div>
                    <div><span class="font-semibold">Cliente:</span> <span id="receiveClient">—</span></div>
                    <div><span class="font-semibold">Valor:</span> R$ <span id="receiveAmount">0,00</span></div>
                </div>

                <form id="receiveForm" action="#" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-600">Recebido em</label>
                        <input id="receiveAt" type="datetime-local" name="received_at" class="w-full border rounded p-2">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Forma de pagamento</label>
                        <select name="payment_method" class="w-full border rounded p-2">
                            <option value="">—</option>
                            <option value="cash">Dinheiro</option>
                            <option value="card">Cartão</option>
                            <option value="pix">PIX</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-2 pt-1">
                        <button type="button" class="px-3 py-2 border rounded text-gray-700 bg-white"
                                onclick="window.dispatchEvent(new CustomEvent('close-modal', { detail: 'receive-modal' }))">Cancelar</button>
                        <button class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700">Confirmar baixa</button>
                    </div>
                </form>
            </div>
        </x-modal>

        <!-- Modal de Baixa em Lote -->
        <x-modal name="bulk-receive-modal" maxWidth="md">
            <div class="p-4">
                <div class="text-lg font-semibold mb-3">Baixar títulos selecionados</div>
                <div class="text-sm text-gray-600 mb-3">Todos os títulos marcados serão baixados como <strong>Pago</strong>. Opcionalmente, informe uma taxa de antecipação (será lançada como despesa paga hoje).</div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs text-gray-600">Recebido em</label>
                        <input id="bulk_received_at" type="datetime-local" name="received_at" form="bulkForm" class="w-full border rounded p-2">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Forma de pagamento</label>
                        <select name="payment_method" form="bulkForm" class="w-full border rounded p-2">
                            <option value="">—</option>
                            <option value="cash">Dinheiro</option>
                            <option value="card">Cartão</option>
                            <option value="pix">PIX</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Taxa de antecipação (opcional)</label>
                        <input type="number" min="0" step="0.01" name="fee_amount" form="bulkForm" class="w-full border rounded p-2" placeholder="0,00">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Descrição da taxa</label>
                        <input type="text" name="fee_description" form="bulkForm" class="w-full border rounded p-2" placeholder="Taxa de antecipação">
                    </div>
                </div>

                <div class="md:col-span-2 flex justify-end gap-2 pt-1">
                    <button type="button" class="px-3 py-2 border rounded text-gray-700 bg-white"
                            onclick="window.dispatchEvent(new CustomEvent('close-modal', { detail: 'bulk-receive-modal' }))">Cancelar</button>
                    <button type="button" onclick="submitBulk()" class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700">Confirmar baixa em lote</button>
                </div>
            </div>
        </x-modal>

        <!-- Modais de Estorno e Cancelamento -->
        @foreach($receivables as $r)
            @php
                $isReversed = $r->status === 'reversed';
                $isFromOrder = !empty($r->order_id) || 
                               str_contains($r->description, 'Pedido') || 
                               str_contains($r->description, 'PDV') ||
                               str_contains($r->description, 'pagamento à vista');
            @endphp
            
            <!-- Modal de Estorno -->
            @if($r->status === 'paid' && 
                auth()->user()->hasPermission('receivables.create') &&
                !$isReversed &&
                !$isFromOrder)
            <div id="reverseModal{{ $r->id }}" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Estornar Recebimento</h3>
                        </div>
                        <form action="{{ route('receivables.reverse', $r) }}" method="POST">
                            @csrf
                            <div class="px-6 py-4">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Cliente: <span class="font-semibold">{{ $r->client->name ?? '—' }}</span>
                                    </label>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Valor: <span class="font-semibold">R$ {{ number_format($r->amount, 2, ',', '.') }}</span>
                                    </label>
                                </div>
                                <div class="mb-4">
                                    <label for="reverse_reason{{ $r->id }}" class="block text-sm font-medium text-gray-700 mb-2">
                                        Motivo do Estorno <span class="text-red-500">*</span>
                                    </label>
                                    <textarea 
                                        id="reverse_reason{{ $r->id }}" 
                                        name="reverse_reason" 
                                        rows="3" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                        placeholder="Descreva o motivo do estorno (mínimo 10 caracteres)"
                                        required
                                        minlength="10"
                                        maxlength="500"
                                    ></textarea>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <span id="charCount{{ $r->id }}">0</span>/500 caracteres
                                    </div>
                                </div>
                            </div>
                            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                                <button type="button" onclick="closeReverseModal{{ $r->id }}()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-orange-500">
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
            function openReverseModal{{ $r->id }}() {
                document.getElementById('reverseModal{{ $r->id }}').classList.remove('hidden');
            }
            
            function closeReverseModal{{ $r->id }}() {
                document.getElementById('reverseModal{{ $r->id }}').classList.add('hidden');
                document.getElementById('reverse_reason{{ $r->id }}').value = '';
                document.getElementById('charCount{{ $r->id }}').textContent = '0';
            }
            
            // Contador de caracteres
            document.getElementById('reverse_reason{{ $r->id }}').addEventListener('input', function() {
                const count = this.value.length;
                document.getElementById('charCount{{ $r->id }}').textContent = count;
            });
            </script>
            @endif
            
            <!-- Modal de Cancelamento -->
            @if(auth()->user()->hasPermission('receivables.delete') && $r->status === 'open' && !$isFromOrder)
            <div id="cancelModal{{ $r->id }}" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Cancelar Recebimento</h3>
                        </div>
                        <form action="{{ route('receivables.cancel', $r) }}" method="POST">
                            @csrf
                            <div class="px-6 py-4">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Cliente: <span class="font-semibold">{{ $r->client->name ?? '—' }}</span>
                                    </label>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Valor: <span class="font-semibold">R$ {{ number_format($r->amount, 2, ',', '.') }}</span>
                                    </label>
                                </div>
                                <div class="mb-4">
                                    <label for="cancel_reason{{ $r->id }}" class="block text-sm font-medium text-gray-700 mb-2">
                                        Motivo do Cancelamento <span class="text-red-500">*</span>
                                    </label>
                                    <textarea 
                                        id="cancel_reason{{ $r->id }}" 
                                        name="cancel_reason" 
                                        rows="3" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                        placeholder="Descreva o motivo do cancelamento (mínimo 10 caracteres)"
                                        required
                                        minlength="10"
                                        maxlength="500"
                                    ></textarea>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <span id="cancelCharCount{{ $r->id }}">0</span>/500 caracteres
                                    </div>
                                </div>
                            </div>
                            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                                <button type="button" onclick="closeCancelModal{{ $r->id }}()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-red-500">
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
            function openCancelModal{{ $r->id }}() {
                document.getElementById('cancelModal{{ $r->id }}').classList.remove('hidden');
            }
            
            function closeCancelModal{{ $r->id }}() {
                document.getElementById('cancelModal{{ $r->id }}').classList.add('hidden');
                document.getElementById('cancel_reason{{ $r->id }}').value = '';
                document.getElementById('cancelCharCount{{ $r->id }}').textContent = '0';
            }
            
            // Contador de caracteres
            document.getElementById('cancel_reason{{ $r->id }}').addEventListener('input', function() {
                const count = this.value.length;
                document.getElementById('cancelCharCount{{ $r->id }}').textContent = count;
            });
            </script>
            @endif
        @endforeach
    </div>
    <script>
    function toggleAll(master){
        document.querySelectorAll('input[name="ids[]"]').forEach(cb=>{ cb.checked = master.checked; });
    }
    function openBulkModal(){
        const any = document.querySelector('input[name="ids[]"]:checked');
        if(!any){ alert('Selecione ao menos um título em aberto.'); return; }
        // prefill date now
        const now = new Date(); now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        const dt = document.getElementById('bulk_received_at'); if (dt) dt.value = now.toISOString().slice(0,16);
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'bulk-receive-modal' }));
    }
    function submitBulk(){
        const form = document.getElementById('bulkForm');
        if(!form){ alert('Formulário não encontrado.'); return; }
        // Garantir que os campos do modal estejam dentro do form (clonar valores)
        // Campos: received_at, payment_method, fee_amount, fee_description
        const fields = ['bulk_received_at','payment_method','fee_amount','fee_description'];
        fields.forEach(id => {
            const src = document.getElementById(id);
            if(src){
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = (id==='bulk_received_at') ? 'received_at' : id;
                input.value = src.value;
                form.appendChild(input);
            }
        });
        // Clonar os ids selecionados
        document.querySelectorAll('input[name="ids[]"]:checked').forEach(cb => {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'ids[]';
            hidden.value = cb.value;
            form.appendChild(hidden);
        });
        form.submit();
    }
    function openReceiveModalFromButton(btn){
        try{
            var action = btn.getAttribute('data-action');
            var desc = btn.getAttribute('data-desc') || '—';
            var client = btn.getAttribute('data-client') || '—';
            var amount = btn.getAttribute('data-amount') || '0,00';

            var form = document.getElementById('receiveForm');
            if(form){ form.setAttribute('action', action); }
            var d = document.getElementById('receiveDesc'); if(d){ d.textContent = desc; }
            var c = document.getElementById('receiveClient'); if(c){ c.textContent = client; }
            var a = document.getElementById('receiveAmount'); if(a){ a.textContent = amount; }

            var dt = document.getElementById('receiveAt');
            if(dt){
                var now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                dt.value = now.toISOString().slice(0,16);
            }

            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'receive-modal' }));
        }catch(e){ console.error(e); }
    }
    
    function confirmReceive(id, client, amount) {
        const formattedAmount = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(amount);
        
        return confirm(`Confirma o recebimento desta conta?\n\nCliente: ${client}\nValor: ${formattedAmount}\n\nEsta ação será registrada na auditoria.`);
    }
    function openBoletoModal(btn){
        try{
            var id = btn.getAttribute('data-id');
            var due = btn.getAttribute('data-due');
            var fine = btn.getAttribute('data-fine') || 0;
            var interest = btn.getAttribute('data-interest') || 0;
            var action = btn.getAttribute('data-action');
            var form = document.getElementById('boletoForm');
            if(form){
                form.setAttribute('action', action || ('/receivables/' + id + '/emit-boleto'));
            }
            var d = document.getElementById('boleto_due'); if(d){ d.value = due; }
            var f = document.getElementById('boleto_fine'); if(f){ f.value = fine; }
            var i = document.getElementById('boleto_interest'); if(i){ i.value = interest; }
            var cb = document.getElementById('boleto_send_email');
            if(cb && form){
                var hidden = form.querySelector('input[name="send_email"][type="hidden"]');
                if(!hidden){ hidden = document.createElement('input'); hidden.type='hidden'; hidden.name='send_email'; form.appendChild(hidden); }
                hidden.value = cb.checked ? '1' : '0';
                cb.addEventListener('change', function(){ hidden.value = cb.checked ? '1' : '0'; });
            }
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'boleto-modal' }));
        }catch(e){ console.error(e); }
    }
    </script>
</x-app-layout>


