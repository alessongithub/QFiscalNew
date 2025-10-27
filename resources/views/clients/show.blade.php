<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dados do Cliente</h2>
            <div class="flex items-center gap-2">
                @can('clients.edit')
                <a href="{{ route('clients.edit', $client) }}" class="inline-flex items-center justify-center px-3 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white">Editar</a>
                @endcan
                <a href="{{ route('clients.index') }}" class="inline-flex items-center justify-center px-3 py-2 rounded border">Voltar</a>
            </div>
        </div>
    </x-slot>

    <div class="bg-white p-6 rounded shadow max-w-5xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border rounded p-4 bg-gray-50">
                <h3 class="font-semibold mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Informações Gerais
                </h3>
                <div class="space-y-2 text-sm">
                    <div><span class="text-gray-500">Nome:</span> <span class="font-medium text-gray-800">{{ $client->name }}</span></div>
                    <div><span class="text-gray-500">Email:</span> <span class="font-medium text-gray-800">{{ $client->email ?: '—' }}</span></div>
                    <div><span class="text-gray-500">Telefone:</span> <span class="font-medium text-gray-800">{{ $client->formatted_phone ?: '—' }}</span></div>
                    <div><span class="text-gray-500">Status:</span> <span class="px-2 py-0.5 rounded text-white text-xs {{ $client->status==='active' ? 'bg-green-600' : 'bg-gray-500' }}">{{ $client->status==='active'?'Ativo':'Inativo' }}</span></div>
                    <div><span class="text-gray-500">Tipo:</span> <span class="font-medium text-gray-800">{{ $client->type_name }}</span></div>
                </div>
            </div>

            <div class="border rounded p-4 bg-gray-50">
                <h3 class="font-semibold mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 8h10M5 6h14v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6z"/></svg>
                    Documento
                </h3>
                <div class="space-y-2 text-sm">
                    <div><span class="text-gray-500">CPF/CNPJ:</span> <span class="font-medium text-gray-800">{{ $client->formatted_cpf_cnpj }}</span></div>
                    <div><span class="text-gray-500">RG/IE:</span> <span class="font-medium text-gray-800">{{ $client->ie_rg ?: '—' }}</span></div>
                </div>
            </div>

            <div class="border rounded p-4 bg-gray-50 md:col-span-2">
                <h3 class="font-semibold mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 12l4.243-4.243m-11.314 0L10.586 12 6.343 16.243"/></svg>
                    Endereço
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-6 gap-3 text-sm">
                    <div class="md:col-span-3"><span class="text-gray-500">Endereço:</span> <span class="font-medium text-gray-800">{{ $client->address ?: '—' }}</span></div>
                    <div class="md:col-span-1"><span class="text-gray-500">Número:</span> <span class="font-medium text-gray-800">{{ $client->number ?: '—' }}</span></div>
                    <div class="md:col-span-2"><span class="text-gray-500">Complemento:</span> <span class="font-medium text-gray-800">{{ $client->complement ?: '—' }}</span></div>
                    <div class="md:col-span-2"><span class="text-gray-500">Bairro:</span> <span class="font-medium text-gray-800">{{ $client->neighborhood ?: '—' }}</span></div>
                    <div class="md:col-span-2"><span class="text-gray-500">Cidade/UF:</span> <span class="font-medium text-gray-800">{{ $client->city ? $client->city.' / '.$client->state : '—' }}</span></div>
                    <div class="md:col-span-2"><span class="text-gray-500">CEP:</span> <span class="font-medium text-gray-800">{{ $client->zip_code ?: '—' }}</span></div>
                </div>
            </div>

            @if(!empty($client->observations))
            <div class="border rounded p-4 bg-gray-50 md:col-span-2">
                <h3 class="font-semibold mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Observações
                </h3>
                <div class="text-sm text-gray-800 whitespace-pre-line">{{ $client->observations }}</div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>


