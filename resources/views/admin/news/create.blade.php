<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nova Notícia</h2>
    </x-slot>
    <div class="bg-white p-4 rounded shadow max-w-3xl">
        <form method="POST" action="{{ route('admin.news.store') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm text-gray-600">Título</label>
                <input name="title" class="w-full border rounded px-3 py-2" required />
            </div>
            <div>
                <label class="block text-sm text-gray-600">Texto</label>
                <textarea name="body" rows="5" class="w-full border rounded px-3 py-2"></textarea>
            </div>
            <div class="grid md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm text-gray-600">Imagem (URL)</label>
                    <input name="image_url" class="w-full border rounded px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Link (URL)</label>
                    <input name="link_url" class="w-full border rounded px-3 py-2" />
                </div>
            </div>
            <div>
                <label class="block text-sm text-gray-600">Ou enviar imagem</label>
                <input type="file" name="image_file" accept="image/*" class="w-full border rounded px-3 py-2" />
            </div>
            <div class="grid md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm text-gray-600">Publicada em</label>
                    <input type="datetime-local" name="published_at" class="w-full border rounded px-3 py-2" />
                </div>
                <label class="inline-flex items-center mt-6"><input type="checkbox" name="active" value="1" class="mr-2" checked /> Ativa</label>
            </div>
            <div class="flex justify-end">
                <a href="{{ route('admin.news.index') }}" class="px-3 py-2 bg-gray-200 rounded mr-2">Cancelar</a>
                <button class="px-3 py-2 bg-green-600 text-white rounded">Salvar</button>
            </div>
        </form>
    </div>
</x-admin-layout>


