<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sangrias do Caixa</h2>
            <a href="{{ route('cash_withdrawals.create') }}" class="px-4 py-2 bg-red-600 text-white rounded">Nova Sangria</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                @if(session('success'))
                    <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded">{{ session('success') }}</div>
                @endif

                <form method="GET" class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <!-- Primeira linha: Busca -->
                    <div class="md:col-span-12">
                        <label class="block text-xs text-gray-600">Buscar</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Motivo da sangria" class="border rounded p-2 w-full">
                    </div>
                    
                    <!-- Segunda linha: Filtros de data e ordenação -->
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-600">Data Inicial</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="border rounded p-2 w-full">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-600">Data Final</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="border rounded p-2 w-full">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-600">Ordenar por</label>
                        <select name="sort" class="border rounded p-2 w-full">
                            <option value="date" @selected(request('sort','date')==='date')>Data</option>
                            <option value="amount" @selected(request('sort')==='amount')>Valor</option>
                            <option value="reason" @selected(request('sort')==='reason')>Motivo</option>
                            <option value="created_at" @selected(request('sort')==='created_at')>Criado em</option>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-600">Direção</label>
                        <select name="direction" class="border rounded p-2 w-full">
                            <option value="desc" @selected(request('direction','desc')==='desc')>Decrescente</option>
                            <option value="asc" @selected(request('direction')==='asc')>Crescente</option>
                        </select>
                    </div>
                    
                    <!-- Terceira linha: Paginação e botões -->
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-600">Mostrar</label>
                        <select name="per_page" class="border rounded p-2 w-full">
                            @foreach([10,20,50,100,200] as $opt)
                                <option value="{{ $opt }}" @selected((int)request('per_page',20)===$opt)>{{ $opt }} por página</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-9 flex items-end justify-end gap-2">
                        <button class="px-3 py-2 bg-gray-800 text-white rounded">Filtrar</button>
                        <a href="{{ route('cash_withdrawals.index') }}" class="px-3 py-2 border rounded text-gray-700 bg-white">Limpar</a>
                    </div>
                </form>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Motivo</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Criado por</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($withdrawals as $w)
                            <tr>
                                <td class="px-3 py-2">{{ $w->date->format('d/m/Y') }}</td>
                                <td class="px-3 py-2">{{ $w->reason }}</td>
                                <td class="px-3 py-2 text-right">R$ {{ number_format((float)$w->amount, 2, ',', '.') }}</td>
                                <td class="px-3 py-2">{{ $w->user?->name ?? 'N/A' }}</td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('cash_withdrawals.edit', $w) }}" class="inline-flex items-center justify-center w-8 h-8 rounded bg-blue-50 hover:bg-blue-100 text-blue-700" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form action="{{ route('cash_withdrawals.destroy', $w) }}" method="POST" class="inline" onsubmit="return confirm('Excluir sangria?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded bg-red-50 hover:bg-red-100 text-red-700" title="Excluir">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-8 text-center text-gray-500">
                                    Nenhuma sangria encontrada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-6">
                    {{ $withdrawals->links() }}
                </div>
            </div>
        </div>
    </div>
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
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>${message}</span>
                </div>
            `;
        } else {
            toast.className += ' bg-red-500 text-white';
            toast.innerHTML = `
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span>${message}</span>
                </div>
            `;
        }
        
        document.body.appendChild(toast);
        
        // Animar entrada
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);
        
        // Remover após 4 segundos
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 4000);
    }
});
</script>

