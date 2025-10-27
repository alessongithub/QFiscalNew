<x-admin-layout>
	<x-slot name="header">
		<div class="flex items-center justify-between">
			<h2 class="font-semibold text-xl text-gray-800">Parceiros</h2>
			<a href="{{ route('admin.partners.create') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Novo</a>
		</div>
	</x-slot>

	@if(session('success'))
		<div class="mb-4 p-3 bg-green-50 text-green-800 rounded">{{ session('success') }}</div>
	@endif

	<div class="bg-white p-4 rounded shadow">
			<table class="min-w-full text-sm">
			<thead>
				<tr class="text-left border-b">
					<th class="py-2">Nome</th>
					<th>Slug</th>
					<th>Domínio</th>
					<th>Comissão</th>
						<th>Contato</th>
					<th>Ativo</th>
					<th class="text-right">Ações</th>
				</tr>
			</thead>
			<tbody>
				@forelse($partners as $p)
					<tr class="border-b">
						<td class="py-2">{{ $p->name }}</td>
						<td>{{ $p->slug }}</td>
						<td>{{ $p->domain ?: '—' }}</td>
						<td>{{ number_format($p->commission_percent * 100, 2, ',', '.') }}%</td>
							<td>
								<div>{{ $p->contact_name }}</div>
								<div class="text-gray-500 text-xs">{{ $p->contact_email }}</div>
							</td>
						<td>{{ $p->active ? 'Sim' : 'Não' }}</td>
						<td class="text-right">
								<a href="{{ route('admin.partners.show', $p) }}" class="px-3 py-1 bg-gray-100 text-gray-800 rounded">Ver</a>
								<a href="{{ route('admin.partners.edit', $p) }}" class="px-3 py-1 bg-blue-50 text-blue-700 rounded">Editar</a>
							<form action="{{ route('admin.partners.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('Remover parceiro?')">
								@csrf @method('DELETE')
								<button class="px-3 py-1 bg-red-50 text-red-700 rounded">Excluir</button>
							</form>
						</td>
					</tr>
				@empty
					<tr><td colspan="6" class="py-6 text-center text-gray-500">Nenhum parceiro</td></tr>
				@endforelse
			</tbody>
		</table>
		<div class="mt-4">{{ $partners->links() }}</div>
	</div>
</x-admin-layout>


