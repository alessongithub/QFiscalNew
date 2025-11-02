<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Recibos</h2>
            @if(auth()->user()->hasPermission('receipts.create'))
            <a href="{{ route('receipts.create') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Novo Recibo
            </a>
            @endif
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif

    <div class="bg-white p-4 rounded shadow">
        <form method="GET" class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <!-- Linha 1: Buscar -->
            <div class="md:col-span-12">
                <label class="block text-xs text-gray-600">Buscar</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Número, descrição ou cliente" class="w-full border rounded p-2">
            </div>
            <!-- Linha 2: Somente Status e Cliente -->
            <div class="md:col-span-3">
                <label class="block text-xs text-gray-600">Status</label>
                <select name="status" class="w-full border rounded p-2">
                    <option value="">Todos</option>
                    <option value="issued" @selected(request('status')==='issued')>Emitido</option>
                    <option value="canceled" @selected(request('status')==='canceled')>Cancelado</option>
                </select>
            </div>
            <div class="md:col-span-9">
                <label class="block text-xs text-gray-600">Cliente</label>
                <select name="client_id" class="w-full border rounded p-2">
                    <option value="">Todos</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" @selected(request('client_id')==$c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <!-- Linha 3: Demais filtros -->
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600">Data de</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600">Data até</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-4">
                <label class="block text-xs text-gray-600">Ordenar por</label>
                <select name="sort" class="w-full border rounded p-2">
                    <option value="issue_date" @selected(request('sort','issue_date')==='issue_date')>Data de emissão</option>
                    <option value="number" @selected(request('sort')==='number')>Número</option>
                    <option value="amount" @selected(request('sort')==='amount')>Valor</option>
                    <option value="created_at" @selected(request('sort')==='created_at')>Cadastro</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600">Direção</label>
                <select name="direction" class="w-full border rounded p-2">
                    <option value="desc" @selected(request('direction','desc')==='desc')>Desc</option>
                    <option value="asc" @selected(request('direction')==='asc')>Asc</option>
                </select>
            </div>
            <div class="md:col-span-2 md:col-start-11">
                <label class="block text-xs text-gray-600">Mostrar</label>
                <select name="per_page" class="w-full border rounded p-2">
                    @foreach([10,12,25,50,100,200] as $opt)
                        <option value="{{ $opt }}" @selected((int)request('per_page',12)===$opt)>{{ $opt }} por página</option>
                    @endforeach
                </select>
            </div>
            <!-- Linha 4: Botões -->
            <div class="md:col-span-12 flex items-end justify-end gap-2">
                <button class="px-3 py-2 bg-gray-800 text-white rounded">Filtrar</button>
                <a href="{{ route('receipts.index') }}" class="px-3 py-2 border rounded text-gray-700 bg-white">Limpar</a>
            </div>
        </form>

        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left border-b">
                <th class="py-2">Número</th>
                <th>Cliente</th>
                <th>Data</th>
                <th>Valor</th>
                <th>Status</th>
                <th class="text-right">Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($receipts as $r)
                <tr class="border-b">
                    <td class="py-2">{{ $r->number }}</td>
                    <td>{{ optional($r->client)->name }}</td>
                    <td>{{ optional($r->issue_date)->format('d/m/Y') }}</td>
                    <td>R$ {{ number_format($r->amount, 2, ',', '.') }}</td>
                    <td><span class="px-2 py-1 rounded text-white text-xs {{ $r->status==='issued' ? 'bg-green-600' : 'bg-gray-600' }}">{{ $r->status==='issued'?'Emitido':'Cancelado' }}</span></td>
                    <td class="text-right">
                        <div class="inline-flex items-center gap-2">
                            @if(auth()->user()->hasPermission('receipts.edit') && $r->status !== 'canceled')
                            <a href="{{ route('receipts.edit', $r) }}" title="Editar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-blue-50 hover:bg-blue-100 text-blue-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4l10.243-10.243a2.5 2.5 0 10-3.536-3.536L4 16v4z"/></svg>
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('receipts.print'))
                            <a href="{{ route('receipts.print', $r) }}" target="_blank" title="Imprimir" class="inline-flex items-center justify-center w-8 h-8 rounded bg-gray-50 hover:bg-gray-100 text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" > <polyline points="6 9 6 2 18 2 18 9" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></polyline> <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path> <rect x="6" y="14" width="12" height="8" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></rect> </svg>                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('receipts.view'))
                            <a href="{{ route('receipts.email_form', $r) }}" title="E-mail" class="inline-flex items-center justify-center w-8 h-8 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M1.5 4.5A1.5 1.5 0 013 3h18a1.5 1.5 0 011.5 1.5v15a1.5 1.5 0 01-1.5 1.5H3A1.5 1.5 0 011.5 19.5v-15zM3 6.44V19.5h18V6.44l-8.553 5.702a2 2 0 01-2.894 0L3 6.44zM20.25 4.5H3.75L12 10.5l8.25-6z"/></svg>
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('receipts.view') && $r->status === 'canceled')
                            <a href="{{ route('receipts.show', $r) }}" title="Visualizar Recibo Cancelado" class="inline-flex items-center justify-center w-8 h-8 rounded bg-gray-50 hover:bg-gray-100 text-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('receipts.delete') && $r->status !== 'canceled')
                            <button onclick="confirmCancel({{ $r->id }}, '{{ $r->number }}')" title="Cancelar Recibo" class="inline-flex items-center justify-center w-8 h-8 rounded bg-red-50 hover:bg-red-100 text-red-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2m-9 0h10"/></svg>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 text-center text-gray-500">Nenhum registro</td></tr>
            @endforelse
            </tbody>
        </table>

        <div class="mt-4">{{ $receipts->links() }}</div>
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
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Cancelar Recibo</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Tem certeza que deseja cancelar o recibo <strong id="receiptNumber"></strong>?
                        </p>
                        <p class="text-xs text-red-600 mb-4">
                            ⚠️ Esta ação não pode ser desfeita!
                        </p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Motivo do Cancelamento <span class="text-red-500">*</span>
                        </label>
                        <textarea id="cancelReason" name="cancel_reason" rows="3" 
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
        function confirmCancel(receiptId, receiptNumber) {
            console.log('confirmCancel chamado', { receiptId, receiptNumber });
            document.getElementById('receiptNumber').textContent = '#' + receiptNumber;
            document.getElementById('cancelForm').action = '/receipts/' + receiptId;
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


