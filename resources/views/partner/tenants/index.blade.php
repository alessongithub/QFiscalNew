<x-partner-layout>
	<div class="bg-white rounded shadow">
		<div class="p-4 border-b flex items-center justify-between">
			<div class="font-semibold text-gray-800">Clientes</div>
			<form method="GET" action="{{ route('partner.tenants.index') }}" class="flex items-center gap-2">
				<input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Buscar por nome, fantasia, CNPJ, email, cidade..." class="border rounded p-2 w-80">
				<button class="px-3 py-2 bg-gray-800 text-white rounded">Buscar</button>
			</form>
		</div>
		<div class="overflow-x-auto">
			<table class="min-w-full text-sm">
				<thead>
					<tr class="bg-gray-50 text-gray-600">
						<th class="text-left px-4 py-2">Razão Social</th>
						<th class="text-left px-4 py-2">Fantasia</th>
						<th class="text-left px-4 py-2">CNPJ</th>
						<th class="text-left px-4 py-2">Plano</th>
						<th class="text-left px-4 py-2">Vencimento</th>
						<th class="text-left px-4 py-2">Status</th>
					</tr>
				</thead>
				<tbody>
					@forelse($tenants as $t)
						<tr class="border-b">
							<td class="px-4 py-2 text-gray-800">{{ $t->name }}</td>
							<td class="px-4 py-2 text-gray-700">{{ $t->fantasy_name }}</td>
							<td class="px-4 py-2 text-gray-700">{{ $t->cnpj }}</td>
							<td class="px-4 py-2 text-gray-700">{{ optional($t->plan)->name ?? '—' }}</td>
							<td class="px-4 py-2 text-gray-700">{{ optional($t->plan_expires_at)->format('d/m/Y') }}</td>
							<td class="px-4 py-2">
								<span class="inline-flex items-center px-2 py-0.5 text-xs rounded {{ $t->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
									{{ $t->active ? 'Ativo' : 'Inativo' }}
								</span>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="6" class="px-4 py-6 text-center text-gray-500">Nenhum tenant encontrado.</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
		<div class="p-4">{{ $tenants->links() }}</div>
	</div>
</x-partner-layout>



