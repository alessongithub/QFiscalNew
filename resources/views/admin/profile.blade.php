<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Perfil do Administrador</h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            @if(session('success'))
                <div class="mb-4 p-3 rounded bg-green-50 text-green-800">{{ session('success') }}</div>
            @endif

            <form action="{{ route('admin.profile.update') }}" method="POST" class="space-y-6 max-w-2xl">
                @csrf
                @method('PUT')

                <div class="border rounded-lg p-5 space-y-4">
                    <h3 class="text-sm font-medium text-gray-800">Informações de Contato</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs mb-1">E-mail do Admin</label>
                            <input type="email" name="admin_email" value="{{ old('admin_email', $adminEmail) }}" class="w-full border rounded px-3 py-2">
                            @error('admin_email')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs mb-1">E-mail para Solicitações de Transferência</label>
                            <input type="email" name="request_email" value="{{ old('request_email', $requestEmail) }}" class="w-full border rounded px-3 py-2">
                            <p class="text-[11px] text-gray-500 mt-1">Receberá os avisos quando um tenant solicitar transferência.</p>
                            @error('request_email')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>


