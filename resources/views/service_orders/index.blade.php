<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ordens de Serviço</h2>
            <a href="{{ route('service_orders.create') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Nova OS</a>
        </div>
    </x-slot>


    <div class="bg-white p-4 rounded shadow">
        <form method="GET" class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-12">
                <label class="block text-xs text-gray-600">Buscar</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Número, título ou cliente" class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600">Status</label>
                <select name="status" class="w-full border rounded p-2">
                    <option value="">Todos</option>
                    <option value="open" @selected(request('status')==='open')>Em análise</option>
                    <option value="in_progress" @selected(request('status')==='in_progress')>Orçada</option>
                    <option value="in_service" @selected(request('status')==='in_service')>Em andamento</option>
                    <option value="warranty" @selected(request('status')==='warranty')>Garantia</option>
                    <option value="service_finished" @selected(request('status')==='service_finished')>Serviço Finalizado</option>
                    <option value="no_repair" @selected(request('status')==='no_repair')>Sem reparo</option>
                    <option value="finished" @selected(request('status')==='finished')>Finalizada</option>
                    <option value="canceled" @selected(request('status')==='canceled')>Cancelada</option>
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs text-gray-600">Cliente</label>
                <select name="client_id" class="w-full border rounded p-2">
                    <option value="">Todos</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" @selected(request('client_id')==$c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs text-gray-600">Equipamento</label>
                <input type="text" name="equipment" value="{{ request('equipment') }}" placeholder="Marca, modelo, série..." class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600">Em garantia</label>
                <select name="warranty" class="w-full border rounded p-2">
                    <option value="">Todas</option>
                    <option value="in" @selected(request('warranty')==='in')>Somente em garantia</option>
                    <option value="out" @selected(request('warranty')==='out')>Fora da garantia</option>
                </select>
            </div>
            <div class="md:col-span-2 flex items-end justify-end gap-2">
                <button class="px-3 py-2 bg-gray-800 text-white rounded">Filtrar</button>
                <a href="{{ route('service_orders.index') }}" class="px-3 py-2 border rounded text-gray-700 bg-white">Limpar</a>
            </div>

            <div class="md:col-span-3">
                <label class="block text-xs text-gray-600">Data de</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs text-gray-600">Data até</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600">Mostrar</label>
                <select name="per_page" class="w-full border rounded p-2">
                    @foreach([10,12,25,50,100,200] as $opt)
                        <option value="{{ $opt }}" @selected((int)request('per_page', 12)===$opt)>{{ $opt }} por página</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs text-gray-600">Ordem do número</label>
                <select name="number_order" class="w-full border rounded p-2">
                    <option value="">Mais recente primeiro</option>
                    <option value="asc" @selected(request('number_order')==='asc')>Crescente</option>
                    <option value="desc" @selected(request('number_order')==='desc')>Decrescente</option>
                </select>
            </div>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4">
            <div class="p-3 border rounded bg-blue-50">
                <div class="text-xs text-gray-600">Finalizadas (hoje)</div>
                <div class="text-lg font-semibold">{{ $finishedTodayCount ?? 0 }}</div>
                <div class="text-xs text-gray-600">Total R$ {{ number_format($finishedTodayAmount ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="p-3 border rounded bg-yellow-50">
                <div class="text-xs text-gray-600">Abertas</div>
                <div class="text-lg font-semibold">{{ $openCount ?? 0 }}</div>
            </div>
            <div class="p-3 border rounded bg-purple-50">
                <div class="text-xs text-gray-600">Em andamento</div>
                <div class="text-lg font-semibold">{{ $inProgressCount ?? 0 }}</div>
            </div>
            <div class="p-3 border rounded bg-green-50">
                <div class="text-xs text-gray-600">Finalizadas (total)</div>
                <div class="text-lg font-semibold">{{ $finishedCount ?? 0 }}</div>
            </div>
            <div class="p-3 border rounded bg-green-100">
                <div class="text-xs text-gray-600">Recebidos hoje (OS)</div>
                <div class="text-lg font-semibold">R$ {{ number_format($receivedTodayAmount ?? 0, 2, ',', '.') }}</div>
            </div>
        </div>
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left border-b">
                <th class="py-2">Número</th>
                <th>Cliente</th>
                <th>Título</th>
                <th>Total</th>
                <th>Fotos</th>
                <th>Status</th>
                <th class="text-right">Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($serviceOrders as $o)
                <tr class="border-b">
                    <td class="py-2">{{ $o->number }}</td>
                    <td>{{ optional($o->client)->name }}</td>
                    <td>
                        <div>{{ $o->title }}</div>
                        @php $inWarranty = $o->warranty_until && \Carbon\Carbon::parse($o->warranty_until)->isFuture(); @endphp
                        @if($inWarranty)
                            <span class="inline-block mt-1 px-2 py-0.5 text-xs rounded bg-green-600 text-white">Em garantia até {{ \Carbon\Carbon::parse($o->warranty_until)->format('d/m/Y') }}</span>
                        @endif
                    </td>
                    <td>
                        <div>R$ {{ number_format($o->total_amount, 2, ',', '.') }}</div>
                        @if(($o->discount_total ?? 0) != 0 || ($o->addition_total ?? 0) != 0)
                            <div class="text-xs text-gray-600">
                                @if(($o->discount_total ?? 0) != 0) Desconto: R$ {{ number_format($o->discount_total, 2, ',', '.') }} @endif
                                @if(($o->addition_total ?? 0) != 0) &nbsp; Acrésc.: R$ {{ number_format($o->addition_total, 2, ',', '.') }} @endif
                            </div>
                        @endif
                    </td>
                    <td>{{ $o->attachments_count ?? 0 }}</td>
                    <td>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2 py-1 rounded text-white text-xs {{
                                $o->status === 'finished' ? 'bg-green-600' : (
                                $o->status === 'open' ? 'bg-yellow-600' : (
                                $o->status === 'in_progress' ? 'bg-blue-600' : (
                                $o->status === 'in_service' ? 'bg-purple-600' : (
                                $o->status==='service_finished' ? 'bg-indigo-600' : (
                                $o->status==='no_repair' ? 'bg-gray-700' : 'bg-gray-600'))))) }}">
                                {{ ['open'=>'Em análise','in_progress'=>'Orçada','in_service'=>'Em andamento','service_finished'=>'Serviço Finalizado','warranty'=>'Garantia','no_repair'=>'Sem reparo','finished'=>'Finalizada','canceled'=>'Cancelada'][$o->status] ?? $o->status }}
                            </span>
                            @if(in_array($o->status, ['in_progress','service_finished']))
                                @if($o->approval_status==='awaiting')
                                    <span class="px-2 py-1 rounded text-xs bg-amber-100 text-amber-700">Avisar Cliente</span>
                                @elseif($o->approval_status==='customer_notified')
                                    <span class="px-2 py-1 rounded text-xs bg-emerald-100 text-emerald-700">Cliente Avisado</span>
                                @elseif($o->approval_status==='approved')
                                    <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-700">Aprovada</span>
                                @endif
                            @endif
                        </div>
                    </td>
                    <td class="text-right">
                        <div class="inline-flex items-center gap-2">
                            @php $isFinished = $o->status==='finished'; @endphp
                            @php $isCanceled = $o->status==='canceled'; @endphp
                            
                            @if(auth()->user()->hasPermission('service_orders.view'))
                            <a href="{{ route('service_orders.show', $o) }}" title="Visualizar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-gray-50 hover:bg-gray-100 text-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            @endif
                            
                            @if($isCanceled)
                                <!-- Para OS cancelada -->
                                <a href="{{ route('service_orders.cancellation_receipt', $o) }}" target="_blank" title="Imprimir Cancelamento" class="inline-flex items-center justify-center w-8 h-8 rounded bg-red-50 hover:bg-red-100 text-red-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                </a>
                                @if(auth()->user()->hasPermission('service_orders.email'))
                                    <a href="{{ route('service_orders.email_form', $o) }}" title="E-mail - OS cancelada" class="inline-flex items-center justify-center w-8 h-8 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M1.5 4.5A1.5 1.5 0 013 3h18a1.5 1.5 0 011.5 1.5v15a1.5 1.5 0 01-1.5 1.5H3A1.5 1.5 0 011.5 19.5v-15zM3 6.44V19.5h18V6.44l-8.553 5.702a2 2 0 01-2.894 0L3 6.44zM20.25 4.5H3.75L12 10.5l8.25-6z"/></svg>
                                    </a>
                                @endif
                            @else
                                @if(auth()->user()->hasPermission('service_orders.view'))
                                <a href="{{ route('service_orders.print', $o) }}" target="_blank" title="Imprimir OS" class="inline-flex items-center justify-center w-8 h-8 rounded bg-gray-50 hover:bg-gray-100 text-gray-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                </a>
                                @endif
                                @if(!$isFinished && auth()->user()->hasPermission('service_orders.edit'))
                                    <a href="{{ route('service_orders.edit', $o) }}" title="Editar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-blue-50 hover:bg-blue-100 text-blue-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4l10.243-10.243a2.5 2.5 0 10-3.536-3.536L4 16v4z"/></svg>
                                    </a>
                                @endif
                                @if(!$isFinished && auth()->user()->hasPermission('service_orders.finalize') && in_array($o->status, ['in_progress','warranty']))
                                    <a href="{{ route('service_orders.finalize_form', $o) }}" title="Finalizar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-green-50 hover:bg-green-100 text-green-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M2.25 12a9.75 9.75 0 1119.5 0 9.75 9.75 0 01-19.5 0zm14.03-2.28a.75.75 0 00-1.06-1.06l-4.72 4.72-1.97-1.97a.75.75 0 10-1.06 1.06l2.5 2.5c.3.3.77.3 1.06 0l5.25-5.25z" clip-rule="evenodd" /></svg>
                                    </a>
                                @endif
                                @if($o->is_warranty && auth()->user()->hasPermission('service_orders.email'))
                                    <a href="{{ route('service_orders.email_form', $o) }}" title="Enviar e-mail - Garantia" class="inline-flex items-center justify-center w-8 h-8 rounded bg-orange-50 hover:bg-orange-100 text-orange-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </a>
                                @endif
                                @if(auth()->user()->hasPermission('service_orders.edit') && in_array($o->status, ['in_progress','service_finished']))
                                    <a href="{{ route('service_orders.email_form', $o) }}" title="Enviar e-mail" class="inline-flex items-center justify-center w-8 h-8 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M1.5 4.5A1.5 1.5 0 013 3h18a1.5 1.5 0 011.5 1.5v15a1.5 1.5 0 01-1.5 1.5H3A1.5 1.5 0 011.5 19.5v-15zM3 6.44V19.5h18V6.44l-8.553 5.702a2 2 0 01-2.894 0L3 6.44zM20.25 4.5H3.75L12 10.5l8.25-6z"/></svg>
                                    </a>
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

        <div class="mt-4">{{ $serviceOrders->links() }}</div>
    </div>

    <!-- Sistema de Toast Dinâmico -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    <script>
        // Função de toast dinâmico
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
            
            // Remover após 4 segundos
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Verificar mensagens de sessão quando a página carrega
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                showToast('{{ session('success') }}', 'success');
            @endif
            
            @if(session('error'))
                showToast('{{ session('error') }}', 'error');
            @endif
            
            @if(session('info'))
                showToast('{{ session('info') }}', 'info');
            @endif
        });
    </script>
</x-app-layout>


