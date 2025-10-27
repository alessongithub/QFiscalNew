<x-admin-layout>
	<x-slot name="header">
		<div class="flex items-center justify-between">
			<h2 class="font-semibold text-xl text-gray-800">Parceiro: {{ $partner->name }}</h2>
			<form action="{{ route('admin.partners.invite', $partner) }}" method="POST" onsubmit="return confirm('Enviar convite para definir senha?')">
				@csrf
				<button class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Enviar convite</button>
			</form>
			<form action="{{ route('admin.partners.credentials', $partner) }}" method="POST" onsubmit="return confirm('Enviar credenciais com senha temporária?')">
				@csrf
				<button class="ml-2 px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">Enviar credenciais</button>
			</form>
		</div>
	</x-slot>

	@if(session('success'))
		<div class="mb-4 p-3 bg-green-50 text-green-800 rounded">{{ session('success') }}</div>
	@endif

	<div class="bg-white p-4 rounded shadow max-w-3xl">
		<div class="grid grid-cols-2 gap-4">
			<div>
				<div class="text-xs text-gray-500">Slug</div>
				<div class="font-semibold">{{ $partner->slug }}</div>
			</div>
			<div>
				<div class="text-xs text-gray-500">Domínio</div>
				<div class="font-semibold">{{ $partner->domain ?: '—' }}</div>
			</div>
			<div>
				<div class="text-xs text-gray-500">CNPJ</div>
				<div class="font-semibold">{{ $partner->cnpj }}</div>
			</div>
			<div>
				<div class="text-xs text-gray-500">Contato</div>
				<div class="font-semibold">{{ $partner->contact_name }} — {{ $partner->contact_email }}</div>
			</div>
			<div>
				<div class="text-xs text-gray-500">Comissão</div>
				<div class="font-semibold">{{ number_format($partner->commission_percent*100,2,',','.') }}%</div>
			</div>
			<div>
				<div class="text-xs text-gray-500">Ativo</div>
				<div class="font-semibold">{{ $partner->active ? 'Sim' : 'Não' }}</div>
			</div>
		</div>
		<div class="mt-6">
			<a href="{{ route('admin.partners.edit', $partner) }}" class="px-4 py-2 border rounded">Editar</a>
			<a href="{{ route('admin.partners.index') }}" class="px-4 py-2 border rounded ml-2">Voltar</a>
		</div>
	</div>
</x-admin-layout>


