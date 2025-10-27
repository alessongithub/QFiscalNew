<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Categoria</h2>
            <a href="{{ route('categories.index') }}" class="text-gray-700">Voltar</a>
        </div>
    </x-slot>

    <div class="bg-white p-6 rounded shadow max-w-3xl">
        <form method="POST" action="{{ route('categories.update', $category) }}" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs text-gray-600 mb-1">Nome<span class="text-red-600">*</span></label>
                <input type="text" name="name" value="{{ old('name', $category->name) }}" class="w-full border rounded px-3 py-2" required>
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">Categoria Pai (opcional)</label>
                <select name="parent_id" class="w-full border rounded px-3 py-2">
                    <option value="">Nenhuma (categoria principal)</option>
                    @foreach($parents as $p)
                        <option value="{{ $p->id }}" @selected(old('parent_id', $category->parent_id)==$p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="active" value="1" class="h-4 w-4" @checked(old('active', $category->active))>
                <span class="text-sm">Ativa</span>
            </div>
            <div class="text-right pt-2">
                <button class="px-4 py-2 bg-green-600 text-white rounded">Salvar</button>
            </div>
        </form>
    </div>
</x-app-layout>


