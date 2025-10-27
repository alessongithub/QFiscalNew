<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Importar Nota de Entrada (XML NFe)</h2>
            <a href="{{ route('inbound.index') }}" class="text-gray-700">Voltar</a>
        </div>
    </x-slot>

    <div class="bg-white p-6 rounded shadow max-w-3xl">
        @if(session('error'))<div class="mb-3 p-3 bg-red-100 text-red-700 rounded">{{ session('error') }}</div>@endif
        <form action="{{ route('inbound.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm text-gray-700 mb-1">Arquivo XML</label>
                <input type="file" name="xml" accept=".xml" class="w-full border rounded px-3 py-2" required>
            </div>
            <div class="text-right">
                <button class="px-4 py-2 bg-green-600 text-white rounded">Enviar</button>
            </div>
        </form>
    </div>
</x-app-layout>


