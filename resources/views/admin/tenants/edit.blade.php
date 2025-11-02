<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Editar Tenant</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 text-green-800 rounded">{{ session('success') }}</div>
    @endif

    <div class="bg-white p-6 rounded shadow max-w-2xl">
        <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}" class="space-y-4">
            @csrf
            @method('PUT')
            
            <div>
                <label class="block text-xs text-gray-600 mb-1">Nome / Razão Social</label>
                <input type="text" value="{{ $tenant->name }}" class="w-full border rounded p-2 bg-gray-100" disabled>
            </div>

            <div>
                <label class="block text-xs text-gray-600 mb-1">CNPJ</label>
                <input type="text" value="{{ $tenant->cnpj }}" class="w-full border rounded p-2 bg-gray-100" disabled>
            </div>

            <div>
                <label class="block text-xs text-gray-600 mb-1">E-mail</label>
                <input type="text" value="{{ $tenant->email }}" class="w-full border rounded p-2 bg-gray-100" disabled>
            </div>

            <div>
                <label class="block text-xs text-gray-600 mb-1">Parceiro</label>
                <select name="partner_id" class="w-full border rounded p-2">
                    <option value="">— Sem parceiro —</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}" {{ $tenant->partner_id == $partner->id ? 'selected' : '' }}>
                            {{ $partner->name }}
                        </option>
                    @endforeach
                </select>
                @error('partner_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-2 pt-4">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Salvar</button>
                <a href="{{ route('admin.tenants') }}" class="px-4 py-2 border rounded">Cancelar</a>
            </div>
        </form>
    </div>
</x-admin-layout>

