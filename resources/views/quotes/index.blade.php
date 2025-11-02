<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Orçamentos
            </h2>
            <a href="{{ route('quotes.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Novo Orçamento
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-3 bg-green-50 text-green-700 border border-green-200 rounded-lg flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <!-- Card principal -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <!-- Header da tabela -->
                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Lista de Orçamentos</h3>
                            <p class="mt-1 text-sm text-gray-600">Gerencie todos os orçamentos de vendas</p>
                        </div>
                        <div class="text-sm text-gray-500">
                            Total: {{ $quotes->total() }} orçamentos
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="px-6 py-3 bg-white border-b border-gray-200">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                        <div class="md:col-span-5">
                            <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Buscar
                            </label>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Número, título ou cliente" class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                Status
                            </label>
                            <select name="status" class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todos os status</option>
                                @foreach(['awaiting'=>'Aguardando','approved'=>'Aprovado','canceled'=>'Cancelado','expirado'=>'Expirado'] as $k=>$v)
                                    <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data até</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="md:col-span-12 flex justify-end gap-2 mt-4">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150 ease-in-out flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Filtrar
                            </button>
                            <a href="{{ route('quotes.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-150 ease-in-out flex items-center">
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
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                        </svg>
                                        Número
                                    </div>
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        Cliente
                                    </div>
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        Título
                                    </div>
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                        </svg>
                                        Total
                                    </div>
                                </th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center justify-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Status
                                    </div>
                                </th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
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
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($quotes as $q)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-mono text-sm font-medium text-gray-900 bg-gray-100 px-2 py-1 rounded">
                                            #{{ $q->number }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ optional($q->client)->name ?: 'Cliente não informado' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $q->title }}">
                                            {{ $q->title }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            R$ {{ number_format($q->total_amount, 2, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @php $map = ['awaiting'=>'Aguardando','approved'=>'Aprovado','not_approved'=>'Rejeitado','canceled'=>'Cancelado','expirado'=>'Expirado']; @endphp
                                        @switch($q->status)
                                            @case('awaiting')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    Aguardando
                                                </span>
                                                @break
                                            @case('approved')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    Aprovado
                                                </span>
                                                @break
                                            @case('not_approved')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    Rejeitado
                                                </span>
                                                @break
                                            @case('canceled')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                    Cancelado
                                                </span>
                                                @break
                                            @case('expirado')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    Expirado
                                                </span>
                                                @break
                                            @default
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    {{ $map[$q->status] ?? $q->status }}
                                                </span>
                                        @endswitch
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center space-x-1">
                                            @php $statusNorm = strtolower(trim((string) $q->status)); $locked = in_array($statusNorm, ['approved','customer_notified','canceled']); $isExpired = $statusNorm === 'expirado'; @endphp
                                            @php
                                                $__cli = optional($q->client);
                                                $__phone = preg_replace('/\D/', '', (string) ($__cli->phone ?? ''));
                                            @endphp
                                            
                                            @if(!empty($__phone) && !$isExpired)
                                                <a href="{{ route('quotes.whatsapp', $q) }}" target="_blank" rel="noopener" title="WhatsApp" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 transition duration-150 ease-in-out">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 32 32" aria-hidden="true"><path d="M19.11 17.67c-.26-.13-1.53-.75-1.77-.83-.24-.09-.42-.13-.6.13-.18.26-.69.83-.85 1.01-.16.18-.31.2-.57.07-.26-.13-1.09-.4-2.08-1.28-.77-.69-1.29-1.54-1.44-1.8-.15-.26-.02-.4.11-.53.11-.11.26-.29.4-.44.13-.15.18-.26.27-.44.09-.18.04-.33-.02-.46-.07-.13-.6-1.44-.82-1.97-.22-.53-.44-.46-.6-.46-.16 0-.33-.02-.51-.02-.18 0-.46.07-.7.33-.24.26-.92.9-.92 2.19 0 1.29.95 2.54 1.08 2.71.13.18 1.87 3.05 4.53 4.28.63.27 1.12.43 1.5.55.63.2 1.21.17 1.66.1.51-.08 1.53-.62 1.74-1.22.22-.6.22-1.12.15-1.22-.07-.11-.24-.18-.49-.31z"/><path d="M16 3C8.82 3 3 8.82 3 16c0 2.29.61 4.44 1.67 6.3L3 29l6.86-1.8C11.75 28.4 13.81 29 16 29c7.18 0 13-5.82 13-13S23.18 3 16 3zm0 23.75c-2.17 0-4.18-.66-5.84-1.78l-.42-.27-4.06 1.07 1.09-3.96-.28-.41A10.66 10.66 0 015.25 16c0-5.93 4.82-10.75 10.75-10.75S26.75 10.07 26.75 16 21.93 26.75 16 26.75z"/></svg>
                                                </a>
                                            @endif
                                            
                                            @if(auth()->user()->hasPermission('quotes.view'))
                                                @php $isApproved = ($q->status === 'approved'); @endphp
                                                @if($isApproved)
                                                    <span title="Orçamento aprovado e convertido em pedido. Use o email do pedido." class="inline-flex items-center justify-center w-8 h-8 rounded-md text-sm font-medium text-indigo-400 bg-indigo-50 opacity-50 cursor-not-allowed">
                                                @else
                                                    <a href="{{ route('quotes.email_form', $q) }}" title="E-mail" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition duration-150 ease-in-out">
                                                @endif
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-18 8h18a2 2 0 002-2V8a2 2 0 00-2-2H3a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                                    </svg>
                                                @if($isApproved)
                                                    </span>
                                                @else
                                                    </a>
                                                @endif
                                            @endif
                                            
                                            @if(auth()->user()->hasPermission('quotes.view') && !$isExpired)
                                                <a href="{{ route('quotes.print', $q) }}" title="Imprimir" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                                    </svg>
                                                </a>
                                            @endif
                                            
                                            @if(auth()->user()->hasPermission('quotes.edit') && (!$locked || $isExpired))
                                                <a href="{{ route('quotes.edit', $q) }}" title="Editar" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition duration-150 ease-in-out">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4l10.243-10.243a2.5 2.5 0 10-3.536-3.536L4 16v4z"/></svg>
                                                </a>
                                            @endif
                                            
                                            @if(auth()->user()->hasPermission('quotes.convert') && !$locked && !$isExpired)
                                                <form action="{{ route('quotes.convert', $q) }}" method="POST" class="inline" onsubmit="return confirm('Converter orçamento em pedido?');">
                                                    @csrf
                                                    <button type="submit" title="Converter em Pedido" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-sm font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition duration-150 ease-in-out">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m0 0l-3-3m3 3l-3 3m5-9V5a2 2 0 00-2-2H6a2 2 0 00-2 2v14a2 2 0 002 2h11a2 2 0 002-2v-2" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            @if(auth()->user()->hasPermission('quotes.view') && ($q->status === 'approved' || $q->status === 'canceled'))
                                                <a href="{{ route('quotes.show', $q) }}" title="Visualizar Orçamento {{ $q->status === 'approved' ? 'Aprovado' : 'Cancelado' }}" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </a>
                                            @endif
                                            
                                            @if(auth()->user()->hasPermission('quotes.delete') && (!$locked || $isExpired))
                                                <button onclick="confirmCancel({{ $q->id }}, '{{ $q->number }}')" title="Cancelar Orçamento" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 transition duration-150 ease-in-out">
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
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <p class="text-lg font-medium text-gray-900 mb-2">Nenhum orçamento encontrado</p>
                                            <p class="text-sm text-gray-500 mb-4">Comece criando seu primeiro orçamento de venda</p>
                                            <a href="{{ route('quotes.create') }}" 
                                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150 ease-in-out">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                Criar primeiro orçamento
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                @if($quotes->hasPages())
                <div class="bg-gray-50 px-4 py-2 border-t border-gray-200">
                    {{ $quotes->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação -->
    <div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Cancelar Orçamento</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Tem certeza que deseja cancelar o orçamento <strong id="quoteNumber"></strong>?
                        </p>
                        <p class="text-xs text-red-600 mb-4">
                            ⚠️ Esta ação não pode ser desfeita!
                        </p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Motivo do Cancelamento <span class="text-red-500">*</span>
                        </label>
                        <textarea id="cancelReason" name="cancel_reason_textarea" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500" 
                                  placeholder="Descreva o motivo do cancelamento (mínimo 10 caracteres)" required></textarea>
                        <p class="text-xs text-gray-500 mt-1">Este motivo será registrado no log de auditoria.</p>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="closeModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancelar
                        </button>
                        <form id="cancelForm" method="POST" class="flex-1">
                            @csrf @method('DELETE')
                            <input type="hidden" name="cancel_reason" id="cancelReasonHidden">
                            <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                Confirmar Cancelamento
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmCancel(quoteId, quoteNumber) {
            console.log('confirmCancel chamado', { quoteId, quoteNumber });
            document.getElementById('quoteNumber').textContent = '#' + quoteNumber;
            document.getElementById('cancelForm').action = '/quotes/' + quoteId;
            document.getElementById('cancelReason').value = '';
            document.getElementById('cancelModal').classList.remove('hidden');
            console.log('Modal deve estar visível agora');
        }

        function closeModal() {
            document.getElementById('cancelModal').classList.add('hidden');
            document.getElementById('cancelReason').value = '';
        }

        // Fechar modal ao clicar fora
        document.getElementById('cancelModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Validação do formulário
        document.getElementById('cancelForm').addEventListener('submit', function(e) {
            console.log('Formulário de cancelamento submetido');
            const reason = document.getElementById('cancelReason').value.trim();
            console.log('Motivo do cancelamento:', reason);
            if (reason.length < 10) {
                console.log('Validação falhou: motivo muito curto');
                e.preventDefault();
                alert('O motivo do cancelamento deve ter pelo menos 10 caracteres.');
                document.getElementById('cancelReason').focus();
            } else {
                console.log('Validação passou, copiando motivo para campo hidden');
                document.getElementById('cancelReasonHidden').value = reason;
                console.log('Formulário sendo enviado com motivo:', reason);
            }
        });
    </script>
</x-app-layout>
