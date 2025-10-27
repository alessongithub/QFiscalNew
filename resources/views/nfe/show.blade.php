<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                    Detalhes da NFe
            </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Pedido #{{ $nfeNote->numero_pedido }} @if($nfeNote->numero_nfe) • NFe #{{ $nfeNote->numero_nfe }} @endif
                </p>
            </div>
            <a href="{{ route('nfe.index') }}" class="inline-flex items-center bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Voltar à Lista
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="hidden" id="__toast_success" data-message="{{ session('success') }}"></div>
            @endif
            @if(session('error'))
                <div class="hidden" id="__toast_error" data-message="{{ session('error') }}"></div>
            @endif
            <!-- Actions & Status Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Actions Section -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Ações</h3>
                                <p class="text-xs text-gray-500">Baixar XML/PDF, gerar DANFE, CC-e, cancelar (se permitido)</p>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap gap-2">
                            @php
                                $hasXml = !empty($nfeNote->xml_resolved_path ?? null) || !empty($nfeNote->xml_path ?? null) || !empty($nfeNote->arquivo_xml ?? null);
                                $hasPdf = !empty($nfeNote->pdf_path ?? null) || !empty($nfeNote->arquivo_danfe ?? null);
                                $emitt = $nfeNote->emitted_at ?? ($nfeNote->data_emissao ?? $nfeNote->created_at ?? null);
                                $over24h = $emitt ? \Carbon\Carbon::now()->diffInHours($emitt) > 24 : false;
                                $respRaw = $nfeNote->response_received ?? null;
                                $resp = is_array($respRaw) ? $respRaw : (is_string($respRaw) ? (json_decode($respRaw, true) ?: []) : []);
                                $cceEvents = is_array($resp['cce_events'] ?? null) ? (array)$resp['cce_events'] : [];
                                $hasCce = count($cceEvents) > 0 || (string)$nfeNote->status === 'com_cc';
                                $canCancel = ($nfeNote->can_cancel ?? false) && auth()->user()->hasPermission('nfe.cancel') && !$over24h && (string)$nfeNote->status !== 'com_cc' && count($cceEvents) === 0;
                            @endphp
                            
                            @if($hasXml)
                                <a href="{{ route('nfe.xml', $nfeNote) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-md text-xs font-medium">
                                    <i class="fas fa-file-code mr-1.5"></i>XML
                                </a>
                            @endif
                            
                            @if($hasPdf)
                                <a href="{{ route('nfe.pdf', $nfeNote) }}?view=1" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 rounded-md text-xs font-medium">
                                    <i class="fas fa-file-pdf mr-1.5"></i>DANFE
                                </a>
                            @elseif($hasXml)
                                <a href="{{ route('nfe.danfe', $nfeNote) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-md text-xs font-medium">
                                    <i class="fas fa-file-pdf mr-1.5"></i>Gerar DANFE
                                </a>
                            @endif
                            
                            @if(in_array($nfeNote->status, ['emitted','transmitida']) && auth()->user()->hasPermission('nfe.cce'))
                                <button type="button" class="inline-flex items-center px-3 py-1.5 bg-orange-100 hover:bg-orange-200 text-orange-700 rounded-md text-xs font-medium" onclick="openCceModalShow({{ $nfeNote->id }})">
                                    <i class="fas fa-edit mr-1.5"></i>Carta de Correção
                                </button>
                            @endif
                            
                            @if(in_array($nfeNote->status, ['cancelled','cancelada']))
                                <a href="{{ route('nfe.cancel_xml', $nfeNote) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-xs font-medium">
                                    <i class="fas fa-file-code mr-1.5"></i>XML Cancelamento
                                </a>
                            @endif
                            
                            @if($hasCce)
                                @if(count($cceEvents) > 1)
                                    <div class="relative inline-block">
                                        <button type="button" id="cceDropdown" class="inline-flex items-center px-3 py-1.5 bg-orange-100 hover:bg-orange-200 text-orange-700 rounded-md text-xs font-medium border border-orange-200 focus:outline-none focus:ring-2 focus:ring-orange-300" onclick="toggleCceDropdown()">
                                            <i class="fas fa-file-code mr-1.5"></i>Cartas de Correção ({{ count($cceEvents) }})
                                            <i class="fas fa-chevron-down ml-1.5 text-xs"></i>
                                        </button>
                                        <div id="cceDropdownMenu" class="hidden absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 z-50">
                                            <div class="py-2">
                                                @if(count($cceEvents) < 20 && auth()->user()->hasPermission('nfe.cce'))
                                                    <button type="button" class="w-full text-left px-4 py-2 bg-orange-50 hover:bg-orange-100 text-orange-700 text-xs font-medium" onclick="openCceModalShow({{ $nfeNote->id }})">
                                                        <i class="fas fa-plus mr-1.5"></i>Nova Carta de Correção
                                                    </button>
                                                    <div class="my-1 border-t border-gray-200 dark:border-gray-600"></div>
                                                @endif
                                                @foreach($cceEvents as $idx => $event)
                                                    @php
                                                        $seq = $event['seq'] ?? ($idx + 1);
                                                        $cStat = $event['cStat'] ?? '';
                                                        $xMotivo = $event['xMotivo'] ?? '';
                                                        $statusColor = in_array($cStat, ['135', '136']) ? 'text-green-600' : 'text-red-600';
                                                        $statusIcon = in_array($cStat, ['135', '136']) ? 'fa-check-circle' : 'fa-times-circle';
                                                    @endphp
                                                    <div class="px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-600 last:border-b-0">
                                                        <div class="flex items-center justify-between">
                        <div class="flex-1">
                                                                <div class="flex items-center space-x-2">
                                                                    <span class="text-xs font-medium text-gray-900 dark:text-gray-100">Seq. {{ $seq }}</span>
                                                                    <i class="fas {{ $statusIcon }} {{ $statusColor }} text-xs"></i>
                                                                    <span class="text-xs {{ $statusColor }}">{{ $cStat }}</span>
                                                                </div>
                                                                @if($xMotivo)
                                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate" title="{{ $xMotivo }}">{{ \Illuminate\Support\Str::limit($xMotivo, 40) }}</p>
                                                                @endif
                                                            </div>
                                                            <a href="{{ route('nfe.cce_xml', $nfeNote) }}?seq={{ $seq }}" target="_blank" class="ml-2 inline-flex items-center px-2 py-1 bg-orange-50 hover:bg-orange-100 text-orange-600 rounded text-xs" title="Baixar XML da Seq. {{ $seq }}">
                                                                <i class="fas fa-download text-xs"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <a href="{{ route('nfe.cce_xml', $nfeNote) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-orange-100 hover:bg-orange-200 text-orange-700 rounded-md text-xs font-medium">
                                        <i class="fas fa-file-code mr-1.5"></i>XML Carta de Correção
                                    </a>
                                    @if(count($cceEvents) < 20 && auth()->user()->hasPermission('nfe.cce'))
                                        <button type="button" class="inline-flex items-center px-3 py-1.5 bg-orange-50 hover:bg-orange-100 text-orange-700 rounded-md text-xs font-medium border border-orange-200" onclick="openCceModalShow({{ $nfeNote->id }})">
                                            <i class="fas fa-plus mr-1.5"></i>Nova CC-e
                                        </button>
                                    @endif
                                @endif
                            @endif
                            
                            @if(($nfeNote->can_retry ?? false) && auth()->user()->hasPermission('nfe.retry'))
                                <form method="POST" action="{{ route('nfe.retry', $nfeNote) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 rounded-md text-xs font-medium" onclick="return confirm('Tentar emitir novamente?')">
                                        <i class="fas fa-redo mr-1.5"></i>Reemitir
                                    </button>
                                </form>
                            @endif
                            
                            @if($canCancel)
                                <button type="button" class="inline-flex items-center px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 rounded-md text-xs font-medium" onclick="openCancelModalShow({{ $nfeNote->id }})">
                                    <i class="fas fa-ban mr-1.5"></i>Cancelar
                                </button>
                            @endif
                            
                            @if(in_array($nfeNote->status, ['emitted','transmitida','com_cc']) && $nfeNote->client?->email)
                                <form method="POST" action="{{ route('nfe.email', $nfeNote) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 rounded-md text-xs font-medium" onclick="return confirm('Enviar XML/PDF por e-mail ao cliente?')">
                                        <i class="fas fa-envelope mr-1.5"></i>E-mail
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <!-- Status Card -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="flex items-center space-x-3 mb-3">
                            <h4 class="text-base font-medium text-gray-900 dark:text-gray-100">Status da NFe</h4>
                                <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full 
                                    @if($nfeNote->status === 'emitted' || $nfeNote->status === 'transmitida') bg-green-100 text-green-800
                                    @elseif($nfeNote->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($nfeNote->status === 'error') bg-red-100 text-red-800
                                    @elseif($nfeNote->status === 'cancelled' || $nfeNote->status === 'cancelada') bg-gray-100 text-gray-800
                                    @else bg-blue-100 text-blue-800 @endif">
                                    @if($nfeNote->status === 'emitted' || $nfeNote->status === 'transmitida')
                                        <i class="fas fa-check-circle mr-1.5"></i>Transmitida
                                    @elseif($nfeNote->status === 'pending')
                                        <i class="fas fa-clock mr-1.5"></i>Pendente
                                    @elseif($nfeNote->status === 'error')
                                        <i class="fas fa-exclamation-triangle mr-1.5"></i>Erro
                                    @elseif($nfeNote->status === 'cancelled' || $nfeNote->status === 'cancelada')
                                        <i class="fas fa-ban mr-1.5"></i>Cancelada
                                    @else
                                    {{ $nfeNote->status_name ?? strtoupper($nfeNote->status) }}
                                    @endif
                                </span>
                            </div>
                            
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if($nfeNote->emitted_at)
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-calendar-alt mr-2 text-gray-400"></i>
                                    <span>Emitida em: {{ $nfeNote->emitted_at->format('d/m/Y H:i:s') }}</span>
                                </div>
                            @endif
                            
                            @if($nfeNote->protocolo)
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-file-contract mr-2 text-gray-400"></i>
                                    <span>Protocolo: {{ $nfeNote->protocolo }}</span>
                        </div>
                            @endif
                        </div>
                    </div>
                        </div>
                    </div>
                    
                    <!-- Error Details (Collapsible) -->
                    @if($nfeNote->error_message)
                <div class="bg-white dark:bg-gray-800 border border-red-200 rounded-lg overflow-hidden">
                            <details class="group">
                                <summary class="cursor-pointer flex items-center justify-between p-4 bg-red-50 hover:bg-red-100 transition-colors">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                                        <span class="font-medium text-red-800">Detalhes do Erro</span>
                                    </div>
                                    <i class="fas fa-chevron-down text-red-400 group-open:rotate-180 transition-transform"></i>
                                </summary>
                                <div class="p-4 bg-red-50 border-t border-red-200">
                            <pre class="text-sm text-red-700 whitespace-pre-wrap">{{ $nfeNote->error_message }}</pre>
                                </div>
                            </details>
                        </div>
                    @endif

            <!-- NFe Details Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Client Information -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            <i class="fas fa-user mr-2 text-blue-500"></i>Cliente
                        </h3>
                        @if($nfeNote->client)
                            <div class="space-y-3">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $nfeNote->client->nome }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $nfeNote->client->cpf_cnpj }}</p>
                        </div>
                                @if($nfeNote->client->email)
                                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-envelope mr-2"></i>
                                        {{ $nfeNote->client->email }}
                                </div>
                            @endif
                                @if($nfeNote->client->telefone)
                                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-phone mr-2"></i>
                                        {{ $nfeNote->client->telefone }}
                                </div>
                            @endif
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400">Cliente não encontrado</p>
                            @endif
                    </div>
                </div>

                <!-- NFe Information -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            <i class="fas fa-file-invoice mr-2 text-green-500"></i>Informações da NFe
                        </h3>
                        <div class="space-y-3">
                            @if($nfeNote->numero_nfe)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Número:</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $nfeNote->numero_nfe }}</span>
                        </div>
                            @endif
                            @if($nfeNote->serie_nfe)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Série:</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $nfeNote->serie_nfe }}</span>
                                </div>
                            @endif
                            @if($nfeNote->chave_acesso ?? $nfeNote->chave_nfe)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Chave:</span>
                                    <span class="text-xs font-mono text-gray-900 dark:text-gray-100 break-all">{{ $nfeNote->chave_acesso ?? $nfeNote->chave_nfe }}</span>
                                </div>
                            @endif
                            @if($nfeNote->valor_total)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Valor Total:</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">R$ {{ number_format($nfeNote->valor_total, 2, ',', '.') }}</span>
                                </div>
                                    @endif
                            </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            @if($nfeNote->order && $nfeNote->order->items->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            <i class="fas fa-shopping-cart mr-2 text-purple-500"></i>Itens do Pedido
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Produto</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Qtd</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor Unit.</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($nfeNote->order->items as $item)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item->product->nome ?? 'Produto não encontrado' }}</div>
                                                @if($item->product->codigo)
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">Código: {{ $item->product->codigo }}</div>
                                                    @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $item->quantidade }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">R$ {{ number_format($item->valor_total, 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
                        </div>
                        </div>

    <!-- Carta de Correção Modal -->
    <div id="cceModalShow" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Carta de Correção</h3>
                    <button type="button" onclick="closeCceModalShow()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                        </div>
                <form id="cceFormShow" method="POST" action="{{ route('nfe.cce', $nfeNote) }}">
                    @csrf
                    <div class="mb-4">
                        <label for="correcao" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Correção (min. 15 caracteres)
                        </label>
                        <textarea id="correcao" name="correcao" rows="4" required minlength="15" maxlength="1000"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
                                  placeholder="Descreva a correção que precisa ser feita..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">Máximo 1000 caracteres</p>
                        </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeCceModalShow()" 
                                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-md">
                            Enviar Correção
                        </button>
                        </div>
                    </form>
                </div>
            </div>
    </div>

    @php
        $respRaw = $nfeNote->response_received ?? null;
        $respView = is_array($respRaw) ? $respRaw : (is_string($respRaw) ? (json_decode($respRaw, true) ?: []) : []);
        $cceEventsView = is_array($respView['cce_events'] ?? null) ? (array)$respView['cce_events'] : [];
    @endphp

    @if(count($cceEventsView) > 0)
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <details class="group">
                        <summary class="cursor-pointer flex items-center justify-between">
                                <div class="flex items-center">
                                <i class="fas fa-history text-orange-500 mr-2"></i>
                                <span class="text-base font-semibold text-gray-900 dark:text-gray-100">Histórico CC-e</span>
                                <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-orange-100 text-orange-700">{{ count($cceEventsView) }}</span>
                        </div>
                                <i class="fas fa-chevron-down text-gray-400 group-open:rotate-180 transition-transform"></i>
                            </summary>
                        <div class="mt-4 space-y-3">
                            @foreach($cceEventsView as $ev)
                                @php
                                    $seqV = (int)($ev['seq'] ?? 0);
                                    $cStatV = (string)($ev['cStat'] ?? '');
                                    $xMotivoV = (string)($ev['xMotivo'] ?? '');
                                    $corrV = (string)($ev['correcao'] ?? '');
                                    $ok = in_array($cStatV, ['135','136']);
                                @endphp
                                <div class="border border-gray-200 dark:border-gray-700 rounded-md p-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs font-medium">Seq. {{ $seqV }}</span>
                                            <span class="text-xs {{ $ok ? 'text-green-700' : 'text-red-700' }}">cStat: {{ $cStatV ?: '—' }}</span>
                                            @if($xMotivoV)
                                                <span class="text-xs text-gray-500">{{ $xMotivoV }}</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('nfe.cce_xml', $nfeNote) }}?seq={{ $seqV }}" target="_blank" class="inline-flex items-center px-2 py-1 bg-orange-50 hover:bg-orange-100 text-orange-600 rounded text-xs">
                                                <i class="fas fa-download mr-1"></i>XML
                                            </a>
                                        </div>
                                    </div>
                                    @if($corrV)
                                        <div class="mt-2 text-xs text-gray-700 dark:text-gray-300">
                                            <span class="font-medium">Correção:</span>
                                            <span>{{ $corrV }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                            </div>
                        </details>
                    </div>
                </div>
        </div>
    @endif

    @php
        $inutListRaw = \App\Models\Setting::get('nfe.inutilizacoes', '[]');
        $inutList = is_string($inutListRaw) ? (json_decode($inutListRaw, true) ?: []) : (is_array($inutListRaw) ? $inutListRaw : []);
        // Filtra por série/modelo/ano do note quando possível
        $serieNote = (int) ($nfeNote->serie_nfe ?: 0);
        $anoNote = $nfeNote->emitted_at ? (int) $nfeNote->emitted_at->format('y') : null;
        $modeloNote = 55; // apenas NF-e por enquanto
        $inutFiltered = [];
        foreach ($inutList as $ev) {
            $ok = true;
            if ($serieNote > 0 && isset($ev['serie']) && (int)$ev['serie'] !== $serieNote) { $ok = false; }
            if ($anoNote !== null && isset($ev['ano']) && (int)$ev['ano'] !== $anoNote) { $ok = false; }
            if (isset($ev['modelo']) && (int)$ev['modelo'] !== $modeloNote) { $ok = false; }
            if ($ok) { $inutFiltered[] = $ev; }
        }
    @endphp

    @if(count($inutFiltered) > 0)
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <details class="group">
                        <summary class="cursor-pointer flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-ban text-red-500 mr-2"></i>
                                <span class="text-base font-semibold text-gray-900 dark:text-gray-100">Eventos de Inutilização</span>
                                <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">{{ count($inutFiltered) }}</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="mt-4 space-y-3">
                            @foreach($inutFiltered as $inut)
                                @php
                                    $dt = (string)($inut['at'] ?? '');
                                    $cst = (string)($inut['cStat'] ?? '');
                                    $xm = (string)($inut['xMotivo'] ?? '');
                                    $emitCnpj = (string)($inut['emit_cnpj'] ?? '');
                                    $serieEv = (int)($inut['serie'] ?? 0);
                                    $from = (int)($inut['numero_inicial'] ?? 0);
                                    $to = (int)($inut['numero_final'] ?? 0);
                                @endphp
                                <div class="border border-gray-200 dark:border-gray-700 rounded-md p-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-xs font-medium">{{ $dt }}</span>
                                                <span class="text-xs">Série {{ $serieEv }}</span>
                                                <span class="text-xs">Faixa {{ $from }}{{ $to && $to !== $from ? '–'.$to : '' }}</span>
                                                <span class="text-xs {{ in_array($cst, ['102','135','173']) ? 'text-green-700' : 'text-gray-700' }}">cStat: {{ $cst ?: '—' }}</span>
                                            </div>
                                            @if($xm)
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $xm }}</p>
                                            @endif
                                        </div>
                                        <div>
                                            @if(!empty($inut['xml_path']) && file_exists($inut['xml_path']))
                                                <a href="{{ route('nfe.inut_xml', $nfeNote) }}?path={{ urlencode($inut['xml_path']) }}" target="_blank" class="inline-flex items-center px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded text-xs">
                                                    <i class="fas fa-download mr-1"></i>XML
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </details>
                </div>
            </div>
        </div>
    @endif

    <!-- Cancel Modal -->
    <div id="cancelModalShow" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Cancelar NFe</h3>
                    <button type="button" onclick="closeCancelModalShow()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
    </div>
                <form id="cancelFormShow" method="POST" action="{{ route('nfe.cancel', $nfeNote) }}">
      @csrf
                    <div class="mb-4">
                        <label for="justificativa" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Justificativa (min. 15 caracteres)
                        </label>
                        <textarea id="justificativa" name="justificativa" rows="4" required minlength="15" maxlength="255"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
                                  placeholder="Motivo do cancelamento..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">Máximo 255 caracteres</p>
      </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeCancelModalShow()" 
                                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md">
                            Confirmar Cancelamento
                        </button>
      </div>
    </form>
  </div>
</div>
    </div>

<script>
        // CC-e Modal Functions
        function openCceModalShow(id) {
            document.getElementById('cceModalShow').classList.remove('hidden');
        }

        function closeCceModalShow() {
            document.getElementById('cceModalShow').classList.add('hidden');
        }

        // Cancel Modal Functions
        function openCancelModalShow(id) {
            document.getElementById('cancelModalShow').classList.remove('hidden');
        }

        function closeCancelModalShow() {
            document.getElementById('cancelModalShow').classList.add('hidden');
        }

        // CC-e Dropdown Functions
        function toggleCceDropdown() {
            const menu = document.getElementById('cceDropdownMenu');
            menu.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('cceDropdown');
            const menu = document.getElementById('cceDropdownMenu');
            
            if (dropdown && menu && !dropdown.contains(event.target) && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });

        // Handle CC-e form submission
        document.getElementById('cceFormShow').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando...';
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    alert('Carta de Correção enviada com sucesso!');
                    window.location.reload();
                } else {
                    alert('Erro: ' + (data.error || data.message || 'Falha desconhecida'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao enviar carta de correção');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                closeCceModalShow();
            });
        });

        // Handle Cancel form submission
        document.getElementById('cancelFormShow').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Cancelando...';
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    alert('NFe cancelada com sucesso!');
                    window.location.reload();
                } else {
                    alert('Erro: ' + (data.error || data.message || 'Falha desconhecida'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao cancelar NFe');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                closeCancelModalShow();
            });
        });
    </script>

<script>
// Toast simples (detalhe)
(function(){
  function showToast(msg, type){
    try{
      var wrap = document.createElement('div');
      wrap.style.position = 'fixed';
      wrap.style.right = '20px';
      wrap.style.bottom = '20px';
      wrap.style.zIndex = '9999';
      var el = document.createElement('div');
      el.style.padding = '10px 14px';
      el.style.borderRadius = '8px';
      el.style.color = type==='error' ? '#991B1B' : '#065F46';
      el.style.background = type==='error' ? '#FEE2E2' : '#D1FAE5';
      el.style.border = type==='error' ? '1px solid #FCA5A5' : '1px solid #A7F3D0';
      el.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
      el.style.fontSize = '14px';
      el.style.maxWidth = '420px';
      el.style.wordBreak = 'break-word';
      el.innerText = msg;
      wrap.appendChild(el);
      document.body.appendChild(wrap);
      setTimeout(function(){
        try{ document.body.removeChild(wrap); }catch(e){}
      }, 4500);
    }catch(e){}
  }
  var ok = document.getElementById('__toast_success');
  if (ok && ok.dataset.message){ showToast(ok.dataset.message, 'success'); }
  var er = document.getElementById('__toast_error');
  if (er && er.dataset.message){ showToast(er.dataset.message, 'error'); }
})();
</script>
</x-app-layout>