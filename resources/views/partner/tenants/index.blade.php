<x-partner-layout>
	<div class="bg-white rounded shadow">
		<div class="p-4 border-b">
			<div class="flex items-center gap-4 mb-4">
				<div class="font-semibold text-gray-800">Clientes</div>
			</div>
			
			<!-- Filtros -->
			<form method="GET" action="{{ isset($isAdmin) && $isAdmin ? route('admin.partner.tenants') : route('partner.tenants.index') }}" class="space-y-4">
				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
					<!-- Busca -->
					<div>
						<label class="block text-xs font-medium text-gray-700 mb-1">Buscar Cliente</label>
						<input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Nome, fantasia, email, CNPJ..." class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
					</div>
					
					<!-- Filtro por plano -->
					<div>
						<label class="block text-xs font-medium text-gray-700 mb-1">Plano</label>
						<select name="plan_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
							<option value="">Todos os planos</option>
							<option value="none" {{ isset($planId) && $planId === 'none' ? 'selected' : '' }}>Sem plano</option>
							@foreach($plans ?? [] as $plan)
								<option value="{{ $plan->id }}" {{ isset($planId) && $planId == $plan->id ? 'selected' : '' }}>
									{{ $plan->name }}
								</option>
							@endforeach
						</select>
					</div>
					
					<!-- Filtro assinaturas vencidas -->
					<div>
						<label class="block text-xs font-medium text-gray-700 mb-1">Status Assinatura</label>
						<label class="flex items-center mt-2">
							<input type="checkbox" name="expired" value="1" {{ isset($expired) && $expired ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
							<span class="ml-2 text-sm text-gray-700">Apenas vencidas</span>
						</label>
					</div>
					
					<!-- Registros por página -->
					<div>
						<label class="block text-xs font-medium text-gray-700 mb-1">Por página</label>
						<select name="per_page" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" onchange="this.form.submit()">
							<option value="10" {{ isset($perPage) && $perPage == 10 ? 'selected' : '' }}>10</option>
							<option value="15" {{ isset($perPage) && $perPage == 15 ? 'selected' : '' }}>15</option>
							<option value="25" {{ isset($perPage) && $perPage == 25 ? 'selected' : '' }}>25</option>
							<option value="50" {{ isset($perPage) && $perPage == 50 ? 'selected' : '' }}>50</option>
							<option value="100" {{ isset($perPage) && $perPage == 100 ? 'selected' : '' }}>100</option>
						</select>
					</div>
				</div>
				
				<div class="flex gap-2">
					<button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium">
						Filtrar
					</button>
					<a href="{{ isset($isAdmin) && $isAdmin ? route('admin.partner.tenants') : route('partner.tenants.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium">
						Limpar
					</a>
				</div>
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
						@if(isset($isAdmin) && $isAdmin)
							<th class="text-left px-4 py-2">Parceiro</th>
						@endif
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
							<td class="px-4 py-2">
								<div class="font-medium text-gray-800">{{ optional($t->plan)->name ?? '—' }}</div>
								@if(optional($t->plan)->price)
									<div class="text-xs text-gray-500">R$ {{ number_format($t->plan->price, 2, ',', '.') }}/mês</div>
								@endif
							</td>
							@if(isset($isAdmin) && $isAdmin)
								<td class="px-4 py-2 text-gray-700">{{ optional($t->partner)->name ?? '—' }}</td>
							@endif
							<td class="px-4 py-2 text-gray-700">{{ optional($t->plan_expires_at)->format('d/m/Y') ?? '—' }}</td>
							<td class="px-4 py-2">
								<span class="inline-flex items-center px-2 py-0.5 text-xs rounded {{ $t->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
									{{ $t->active ? 'Ativo' : 'Inativo' }}
								</span>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="{{ isset($isAdmin) && $isAdmin ? '7' : '6' }}" class="px-4 py-6 text-center text-gray-500">Nenhum tenant encontrado.</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
		<div class="p-4">{{ $tenants->links() }}</div>
	</div>
</x-partner-layout>



