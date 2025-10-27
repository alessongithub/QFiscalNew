<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Categorias</h2>
            @if(auth()->user()->hasPermission('categories.create'))
            <a href="{{ route('categories.create') }}" class="px-4 py-2 bg-green-600 text-white rounded">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Nova Categoria
            </a>
            @endif
        </div>
    </x-slot>

    <div class="bg-white p-4 rounded shadow">
        <form method="GET" class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-4">
                <label class="block text-xs text-gray-600">Buscar</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome ou categoria pai" class="w-full border rounded p-2">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600">Status</label>
                <select name="status" class="w-full border rounded p-2">
                    <option value="">Todos</option>
                    <option value="active" @selected(request('status')==='active')>Ativa</option>
                    <option value="inactive" @selected(request('status')==='inactive')>Inativa</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600">Ordenar por</label>
                <select name="sort" class="w-full border rounded p-2">
                    <option value="name" @selected(request('sort','name')==='name')>Nome</option>
                    <option value="parent" @selected(request('sort')==='parent')>Categoria pai</option>
                    <option value="created_at" @selected(request('sort')==='created_at')>Cadastro</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600">Direção</label>
                <select name="direction" class="w-full border rounded p-2">
                    <option value="asc" @selected(request('direction','asc')==='asc')>Crescente</option>
                    <option value="desc" @selected(request('direction')==='desc')>Decrescente</option>
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
            <div class="md:col-span-12 flex items-end justify-end gap-2">
                <button class="px-3 py-2 bg-gray-800 text-white rounded">Filtrar</button>
                <a href="{{ route('categories.index') }}" class="px-3 py-2 border rounded text-gray-700 bg-white">Limpar</a>
            </div>
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-600">
                        <th class="py-2 px-2">Nome</th>
                        <th class="py-2 px-2">Categoria Pai</th>
                        <th class="py-2 px-2">Status</th>
                        <th class="py-2 px-2 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $c)
                    <tr class="border-b">
                        <td class="py-2 px-2">{{ $c->name }}</td>
                        <td class="py-2 px-2">{{ optional($c->parent)->name ?? '—' }}</td>
                        <td class="py-2 px-2"><span class="px-2 py-0.5 rounded text-white text-xs {{ $c->active ? 'bg-green-600' : 'bg-gray-500' }}">{{ $c->active ? 'Ativa' : 'Inativa' }}</span></td>
                        <td class="py-2 px-2 text-right">
                            <div class="inline-flex gap-2">
                                @if(auth()->user()->hasPermission('categories.edit'))
                                <a href="{{ route('categories.edit', $c) }}" title="Editar" class="inline-flex items-center justify-center w-8 h-8 rounded bg-blue-50 hover:bg-blue-100 text-blue-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4l10.243-10.243a2.5 2.5 0 10-3.536-3.536L4 16v4z"/></svg>
                                </a>
                                @endif
                                @if(auth()->user()->hasPermission('categories.delete'))
                                <form method="POST" action="{{ route('categories.destroy', $c) }}" onsubmit="return confirm('Excluir categoria?')">
                                    @csrf @method('DELETE')
                                    <button title="Excluir" class="inline-flex items-center justify-center w-8 h-8 rounded bg-red-50 hover:bg-red-100 text-red-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2m-9 0h10"/></svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="py-6 text-center text-gray-500">Nenhuma categoria</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $categories->links() }}</div>
    </div>
</x-app-layout>


