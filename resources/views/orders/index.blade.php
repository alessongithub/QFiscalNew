<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Pedidos
            </h2>
            <a href="{{ route('orders.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Novo Pedido
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Card principal -->
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                <!-- Header da tabela -->
                <div class="bg-gray-50 dark:bg-gray-700 px-6 py-3 border-b border-gray-200 dark:border-gray-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Lista de Pedidos</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Gerencie todos os pedidos de vendas</p>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Total: {{ $orders->total() }} pedidos
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="px-6 py-3 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-600">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-8 gap-4 items-end">
            <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Buscar
                            </label>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Número, título ou cliente" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                Status
                            </label>
                            <select name="status" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todos os status</option>
                    @foreach(['open'=>'Aberto','fulfilled'=>'Finalizado','canceled'=>'Cancelado'] as $k=>$v)
                        <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Por página</label>
                            <select name="per_page" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @foreach([10,12,25,50,100] as $opt)
                                    <option value="{{ $opt }}" @selected((int)request('per_page',12)===$opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
                        <div class="md:col-span-8 flex justify-end gap-2 mt-4">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150 ease-in-out flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Filtrar
                            </button>
                            <a href="{{ route('orders.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150 ease-in-out flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Limpar
                            </a>
                        </div>
        </form>
                </div>

                <!-- Tabela -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                        </svg>
                                        Número
                                    </div>
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        Cliente
                                    </div>
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        Título
                                    </div>
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                        </svg>
                                        Total
                                    </div>
                                </th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <div class="flex items-center justify-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Status
                                    </div>
                                </th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <div class="flex items-center justify-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        Ações
                                    </div>
                                </th>
            </tr>
            </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
            @forelse($orders as $o)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <div class="font-mono text-sm font-medium text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                            #{{ $o->number }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate max-w-[200px]" title="{{ optional($o->client)->name ?: 'Cliente não informado' }}">
                                            {{ optional($o->client)->name ?: 'Cliente não informado' }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="text-sm text-gray-900 dark:text-gray-100 truncate max-w-[200px]" title="{{ $o->title }}">
                                            {{ $o->title }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Líquido</div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            @php
                                                // Cálculo correto: line_total já é líquido (já tem desconto aplicado)
                                                // net = sum(line_total) - discount_total + addition_total + frete + seguro + outras
                                                $gross = (float) ($o->items ? $o->items->sum('line_total') : 0);
                                                $net = max(0.0,
                                                    ($gross - (float)($o->discount_total ?? 0))
                                                    + (float)($o->addition_total ?? 0)
                                                    + (float)($o->freight_cost ?? 0)
                                                    + (float)($o->valor_seguro ?? 0)
                                                    + (float)($o->outras_despesas ?? 0)
                                                );
                                            @endphp
                                            R$ {{ number_format($net, 2, ',', '.') }}
                                        </div>
                                        
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-center">
                        @php $map = ['open'=>'Aberto','fulfilled'=>'Finalizado','canceled'=>'Cancelado']; @endphp
                                        @switch($o->status)
                                            @case('open')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <i class="fas fa-clock mr-1"></i>Aberto
                                                </span>
                                                @break
                                            @case('fulfilled')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-check mr-1"></i>Finalizado
                                                </span>
                                                @break
                                            @case('canceled')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <i class="fas fa-times mr-1"></i>Cancelado
                                                </span>
                                                @break
                                            @default
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        {{ $map[$o->status] ?? $o->status }}
                                                </span>
                                        @endswitch
                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center space-x-1">
                            @php $olocked = in_array(strtolower(trim((string) $o->status)), ['fulfilled','canceled']); @endphp
                            @php
                                $__cli = optional($o->client);
                                $__phone = preg_replace('/\D/', '', (string) ($__cli->phone ?? ''));
                            @endphp
                                            
                            @if(!empty($__phone))
                                                <a href="{{ route('orders.whatsapp', $o) }}" target="_blank" rel="noopener" title="WhatsApp" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 transition duration-150 ease-in-out">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 32 32" aria-hidden="true"><path d="M19.11 17.67c-.26-.13-1.53-.75-1.77-.83-.24-.09-.42-.13-.6.13-.18.26-.69.83-.85 1.01-.16.18-.31.2-.57.07-.26-.13-1.09-.4-2.08-1.28-.77-.69-1.29-1.54-1.44-1.8-.15-.26-.02-.4.11-.53.11-.11.26-.29.4-.44.13-.15.18-.26.27-.44.09-.18.04-.33-.02-.46-.07-.13-.6-1.44-.82-1.97-.22-.53-.44-.46-.6-.46-.16 0-.33-.02-.51-.02-.18 0-.46.07-.7.33-.24.26-.92.9-.92 2.19 0 1.29.95 2.54 1.08 2.71.13.18 1.87 3.05 4.53 4.28.63.27 1.12.43 1.5.55.63.2 1.21.17 1.66.1.51-.08 1.53-.62 1.74-1.22.22-.6.22-1.12.15-1.22-.07-.11-.24-.18-.49-.31z"/><path d="M16 3C8.82 3 3 8.82 3 16c0 2.29.61 4.44 1.67 6.3L3 29l6.86-1.8C11.75 28.4 13.81 29 16 29c7.18 0 13-5.82 13-13S23.18 3 16 3zm0 23.75c-2.17 0-4.18-.66-5.84-1.78l-.42-.27-4.06 1.07 1.09-3.96-.28-.41A10.66 10.66 0 015.25 16c0-5.93 4.82-10.75 10.75-10.75S26.75 10.07 26.75 16 21.93 26.75 16 26.75z"/></svg>
                            </a>
                            @endif
                                            
                            @if(auth()->user()->hasPermission('orders.view'))
                                                <a href="{{ route('orders.email_form', $o) }}" title="E-mail" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition duration-150 ease-in-out">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-18 8h18a2 2 0 002-2V8a2 2 0 00-2-2H3a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                </svg>
                            </a>
                            @endif
                                            
                            @php $missingCpf = empty(optional($o->client)->cpf_cnpj); @endphp
                            @if(auth()->user()->hasPermission('orders.edit') && (!$olocked || $missingCpf))
                                                <a href="{{ route('orders.edit', $o) }}" title="Editar" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition duration-150 ease-in-out">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4l10.243-10.243a2.5 2.5 0 10-3.536-3.536L4 16v4z"/></svg>
                                                </a>
                                            @endif
                                            
                            {{-- Botão de Pagamento removido conforme regra: apenas edição para pedidos em aberto --}}
                                            
                            @php
                                $latestNfe = $o->latestNfeNoteCompat;
                                $nfeStatus = strtolower((string) ($latestNfe->status ?? ''));
                                $hasCancelled = (bool) $o->has_cancelled_nfe;
                                $hasSuccessful = (bool) $o->has_successful_nfe;
                                // Só pode emitir quando finalizado e não há NFe autorizada e nem cancelada pendente de reabertura
                                $canIssue = ($o->status==='fulfilled') && !$hasSuccessful && !$hasCancelled;
                                $canRetry = in_array($nfeStatus, ['error','rejeitada','rejected']);
                                $isTransmitted = $hasSuccessful;
                                $hasLatest = !empty($latestNfe);
                                $canEditFreightPayment = ($o->status==='fulfilled') && (
                                    (!$hasLatest && empty($o->nfe_issued_at))
                                    || in_array($nfeStatus, ['error','rejeitada','rejected','cancelada','cancelled'])
                                );
                            @endphp
                            @if(auth()->user()->hasPermission('orders.view'))
                                                <a href="{{ route('orders.show', $o) }}" title="Visualizar Pedido" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition duration-150 ease-in-out">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </a>
                            @endif
                            @if(auth()->user()->hasPermission('orders.edit') && auth()->user()->hasPermission('nfe.emit'))
                                @if($canIssue)
                                                <a href="{{ route('orders.edit', $o) }}?auto_open_nfe=1" title="Configurar NF-e (tpNF, finalidade, CFOP, devolução, referência)" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition duration-150 ease-in-out" aria-label="Configurar e emitir NF-e">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h10M11 9h10M11 13h10M4 7h.01M4 11h.01M4 15h.01"/></svg>
                                                </a>
                                                @php $isConsumerFinal = optional($o->client)->name === 'Consumidor Final' || optional($o->client)->consumidor_final === 'S'; @endphp
                                                @if($isConsumerFinal)
                                                    <form action="{{ route('orders.issue_nfce', $o) }}" method="POST" class="inline" onsubmit="return confirm('Confirmar emissão da NFC-e para este pedido?');">
                                                        @csrf
                                                        <button type="submit" title="Emitir NFC-e (Consumidor Final)" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 transition duration-150 ease-in-out" aria-label="Emitir NFC-e">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m-4-4h8M5 7h14a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z"/></svg>
                                                        </button>
                                                    </form>
                                                @endif
                                @elseif($latestNfe)
                                                <a href="{{ route('nfe.show', $latestNfe) }}" title="Gerenciar NF-e (detalhes, XML, DANFE, eventos)" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition duration-150 ease-in-out" aria-label="Gerenciar NF-e">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                </a>
                                @endif
                            @endif
                                            
                            {{-- Botão de Frete removido para pedidos em aberto conforme regra --}}
                                            
                            @if(auth()->user()->hasPermission('orders.edit') && $o->status==='fulfilled' && !$isTransmitted)
                                <button type="button" title="Reabrir" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-sm font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 transition duration-150 ease-in-out" onclick="window.QF_openReopenModal{{ $o->id }}()">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8 2 2 0 01-2 2H5a2 2 0 01-2-2V10z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6l3-3m0 0l3 3m-3-3v4"/></svg>
                                </button>
                                <div id="reopenModal{{ $o->id }}" class="fixed inset-0 bg-black/30 hidden flex items-center justify-center z-50">
                                    <div class="bg-white rounded shadow p-4 w-full max-w-md">
                                        <h3 class="font-semibold mb-2">Reabrir pedido #{{ $o->number }}</h3>
                                        <form action="{{ route('orders.reopen', $o) }}" method="POST">
                                            @csrf
                                            <div class="mb-2">
                                                <label class="block text-xs text-gray-600">Justificativa</label>
                                                <textarea name="justification" class="w-full border rounded p-2" required minlength="10" maxlength="500" placeholder="Descreva o motivo"></textarea>
                                            </div>
                                            <div class="mb-2">
                                                <label class="inline-flex items-center"><input type="checkbox" name="estornar" value="1" class="mr-2">Estornar financeiro deste pedido agora</label>
                                            </div>
                                            <div class="text-right space-x-2 mt-3">
                                                <button type="button" class="px-3 py-1 border rounded" onclick="document.getElementById('reopenModal{{ $o->id }}').classList.add('hidden')">Cancelar</button>
                                                <button type="submit" class="px-3 py-1 bg-amber-600 text-white rounded">Reabrir</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <script>
                                    window.QF_openReopenModal{{ $o->id }} = function(){ document.getElementById('reopenModal{{ $o->id }}').classList.remove('hidden'); };
                                </script>
                            @endif

                            @if(auth()->user()->hasPermission('orders.delete') && !$olocked)
                                <button type="button" onclick="openCancelModal{{ $o->id }}()" title="Cancelar" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 transition duration-150 ease-in-out">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center">
                                        <div class="text-gray-400">
                                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m0 0L17 18m0 0v4a2 2 0 01-2 2H9a2 2 0 01-2-2v-4m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v9.02"/>
                                            </svg>
                                            <p class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Nenhum pedido encontrado</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Comece criando seu primeiro pedido de venda</p>
                                            <a href="{{ route('orders.create') }}" 
                                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150 ease-in-out">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                Criar primeiro pedido
                                            </a>
                                        </div>
                                    </td>
                                </tr>
            @endforelse
            </tbody>
        </table>
                </div>

                <!-- Paginação -->
                @if($orders->hasPages())
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-2 border-t border-gray-200 dark:border-gray-600">
                    {{ $orders->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modais de Cancelamento -->
    @foreach($orders as $o)
        @if(auth()->user()->hasPermission('orders.delete'))
            <div id="cancelOrderModal{{ $o->id }}" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Cancelar Pedido #{{ $o->number }}</h3>
                        <button type="button" onclick="closeCancelModal{{ $o->id }}()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded">
                        <p class="text-sm text-amber-800">Este cancelamento irá gerar estorno no Caixa do Dia para valores já recebidos e cancelará os títulos em aberto. Os itens serão devolvidos ao estoque.</p>
                    </div>
                    
                    <form id="cancelOrderForm{{ $o->id }}" method="POST" action="{{ route('orders.destroy', $o) }}">
                        @csrf 
                        <input type="hidden" name="_method" value="DELETE">
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Justificativa <span class="text-red-500">*</span>
                            </label>
                            <textarea name="cancel_reason" id="cancelReason{{ $o->id }}" rows="4" required minlength="15" maxlength="500"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                placeholder="Descreva o motivo do cancelamento (mínimo 15 caracteres)"></textarea>
                            <p class="text-xs text-gray-500 mt-1">Este motivo será registrado permanentemente no histórico do pedido.</p>
                        </div>
                        
                        <div class="flex space-x-3">
                            <button type="button" onclick="closeCancelModal{{ $o->id }}()" class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Voltar
                            </button>
                            <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                Confirmar Cancelamento
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
            function openCancelModal{{ $o->id }}() {
                console.log('Abrindo modal de cancelamento para pedido {{ $o->id }}...');
                const modal = document.getElementById('cancelOrderModal{{ $o->id }}');
                console.log('Modal encontrado:', modal);
                
                if (modal) {
                    modal.classList.remove('hidden');
                    document.getElementById('cancelReason{{ $o->id }}').focus();
                    console.log('Modal aberto com sucesso!');
                } else {
                    console.error('Modal não encontrado!');
                    alert('Erro: Modal de cancelamento não encontrado!');
                }
            }

            function closeCancelModal{{ $o->id }}() {
                document.getElementById('cancelOrderModal{{ $o->id }}').classList.add('hidden');
                document.getElementById('cancelReason{{ $o->id }}').value = '';
            }

            // Fechar ao clicar fora
            document.getElementById('cancelOrderModal{{ $o->id }}')?.addEventListener('click', function(e) {
                if (e.target === this) closeCancelModal{{ $o->id }}();
            });

            // Validação no submit
            document.getElementById('cancelOrderForm{{ $o->id }}')?.addEventListener('submit', function(e) {
                const reason = document.getElementById('cancelReason{{ $o->id }}').value.trim();
                console.log('Justificativa:', reason, 'Tamanho:', reason.length);
                
                if (reason.length < 15) {
                    e.preventDefault();
                    alert('A justificativa deve ter pelo menos 15 caracteres.');
                    document.getElementById('cancelReason{{ $o->id }}').focus();
                    return false;
                }
                
                // Confirmar cancelamento
                if (!confirm('Tem certeza que deseja cancelar este pedido? Esta ação não pode ser desfeita.')) {
                    e.preventDefault();
                    return false;
                }
                
                // Debug: verificar todos os campos do formulário
                const formData = new FormData(this);
                console.log('Dados do formulário:');
                for (let [key, value] of formData.entries()) {
                    console.log(key + ':', value);
                }
                
                console.log('Formulário sendo enviado com justificativa:', reason);
                return true;
            });
            </script>
        @endif
    @endforeach
</x-app-layout>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toast para mensagens de sucesso e erro
    const flashSuccess = @json(session('success'));
    const flashError = @json(session('error'));
    const validationErrors = @json($errors->all());
    
    if (flashSuccess) {
        showToast(flashSuccess, 'success');
    }
    if (flashError) {
        showToast(flashError, 'error');
    }
    if (validationErrors && validationErrors.length > 0) {
        validationErrors.forEach(error => {
            showToast(error, 'error');
        });
    }
    
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 translate-x-full`;
        
        if (type === 'success') {
            toast.className += ' bg-green-500 text-white';
            toast.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    ${message}
                </div>
            `;
        } else {
            toast.className += ' bg-red-500 text-white';
            toast.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    ${message}
                </div>
            `;
        }
        
        document.body.appendChild(toast);
        
        // Animar entrada
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);
        
        // Remover após 5 segundos
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 5000);
    }
});
</script>

