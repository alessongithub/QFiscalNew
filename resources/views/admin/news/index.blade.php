<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novidades</h2>
            <a href="{{ route('admin.news.create') }}" class="px-3 py-2 bg-green-600 text-white rounded">Nova Notícia</a>
        </div>
    </x-slot>
    <div class="bg-white p-4 rounded shadow">
        @if(session('success'))
            <div class="mb-3 p-2 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        
        <form id="bulk-delete-form" method="POST" action="{{ route('admin.news.bulk-delete') }}" onsubmit="return confirm('Tem certeza que deseja excluir as notícias selecionadas?')">
            @csrf
            @method('DELETE')
            <div class="mb-3 flex items-center gap-3">
                <button type="button" onclick="toggleAll()" class="px-3 py-1 bg-gray-200 text-gray-700 rounded text-sm">Selecionar Todas</button>
                <button type="submit" id="delete-selected-btn" disabled class="px-3 py-1 bg-red-600 text-white rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed">Excluir Selecionadas</button>
                <span id="selected-count" class="text-sm text-gray-600"></span>
            </div>
            <table class="min-w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-600 uppercase">
                    <th class="w-10"><input type="checkbox" id="select-all" onchange="toggleAll()"></th>
                    <th>Título</th><th>Publicada em</th><th>Status</th><th class="text-right">Ações</th>
                </tr></thead>
                <tbody>
                @foreach($items as $n)
                    <tr class="border-b">
                        <td class="py-1">
                            <input type="checkbox" name="news_ids[]" value="{{ $n->id }}" class="news-checkbox" onchange="updateDeleteButton()">
                        </td>
                        <td class="py-1">{{ $n->title }}</td>
                        <td class="py-1">{{ optional($n->published_at)->format('d/m/Y H:i') }}</td>
                        <td class="py-1">{{ $n->active ? 'Ativa' : 'Inativa' }}</td>
                        <td class="py-1 text-right">
                            <a href="{{ route('admin.news.edit', $n) }}" class="px-2 py-1 bg-blue-600 text-white rounded text-xs">Editar</a>
                            <form action="{{ route('admin.news.destroy', $n) }}" method="POST" class="inline" onsubmit="return confirm('Excluir?')">
                                @csrf @method('DELETE')
                                <button class="px-2 py-1 bg-red-600 text-white rounded text-xs">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </form>
        <div class="mt-3">{{ $items->links() }}</div>
    </div>
    
    <script>
        function toggleAll() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.news-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateDeleteButton();
        }
        
        function updateDeleteButton() {
            const checkboxes = document.querySelectorAll('.news-checkbox');
            const checked = document.querySelectorAll('.news-checkbox:checked');
            const deleteBtn = document.getElementById('delete-selected-btn');
            const countSpan = document.getElementById('selected-count');
            const selectAll = document.getElementById('select-all');
            
            deleteBtn.disabled = checked.length === 0;
            countSpan.textContent = checked.length > 0 ? `${checked.length} selecionada(s)` : '';
            
            // Atualizar checkbox "Selecionar Todas"
            if (checkboxes.length > 0) {
                selectAll.checked = checked.length === checkboxes.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < checkboxes.length;
            }
        }
        
        // Inicializar estado do botão
        document.addEventListener('DOMContentLoaded', updateDeleteButton);
    </script>
</x-admin-layout>


