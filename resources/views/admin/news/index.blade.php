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
        <table class="min-w-full text-sm">
            <thead><tr class="text-left text-xs text-gray-600 uppercase"><th>Título</th><th>Publicada em</th><th>Status</th><th class="text-right">Ações</th></tr></thead>
            <tbody>
            @foreach($items as $n)
                <tr class="border-b">
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
        <div class="mt-3">{{ $items->links() }}</div>
    </div>
</x-admin-layout>


