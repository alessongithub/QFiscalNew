<x-partner-layout>
	<x-slot name="header">
		<div class="flex items-center justify-between">
			<h2 class="font-semibold text-xl text-gray-800">Painel do Parceiro</h2>
			<form method="POST" action="{{ route('partner.logout') }}">
				@csrf
				<button class="px-3 py-1.5 border rounded">Sair</button>
			</form>
		</div>
	</x-slot>

	@if(session('success'))
		<div class="mb-4 p-3 bg-green-50 text-green-800 rounded">{{ session('success') }}</div>
	@endif

	@if(isset($partner) && $partner && $partner->logo_path)
		<div class="mb-6 flex justify-center">
			<img src="{{ asset('storage/' . $partner->logo_path) }}" alt="{{ $partner->name }}" class="h-20 w-auto">
		</div>
	@endif

	@if(isset($todayTenants) && $todayTenants->count() > 0)
		<div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-500 rounded shadow">
			<div class="flex items-center justify-between mb-3">
				<div class="flex items-center gap-2">
					<svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
					</svg>
					<h3 class="text-lg font-semibold text-blue-800">Novos Clientes Cadastrados Hoje ({{ $todayTenants->count() }})</h3>
				</div>
			</div>
			<div class="space-y-2">
				@foreach($todayTenants as $tenant)
					<div class="bg-white p-3 rounded border border-blue-200">
						<div class="flex items-center justify-between">
							<div>
								<div class="font-medium text-gray-800">{{ $tenant->fantasy_name ?? $tenant->name }}</div>
								<div class="text-sm text-gray-600">{{ $tenant->cnpj }}</div>
								<div class="text-xs text-gray-500 mt-1">Cadastrado às {{ $tenant->created_at->format('H:i') }}</div>
							</div>
							<div class="text-right">
								<div class="text-sm font-medium text-blue-600">{{ optional($tenant->plan)->name ?? 'Gratuito' }}</div>
								<span class="inline-flex items-center px-2 py-0.5 text-xs rounded {{ $tenant->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
									{{ $tenant->active ? 'Ativo' : 'Inativo' }}
								</span>
							</div>
						</div>
					</div>
				@endforeach
			</div>
		</div>
	@endif

	<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
		<div class="bg-white p-4 rounded shadow">
			<div class="text-sm text-gray-600">Clientes (tenants)</div>
			<div class="text-3xl font-bold text-gray-800">{{ $tenantsCount }}</div>
		</div>
		<div class="bg-white p-4 rounded shadow">
			<div class="text-sm text-gray-600">Faturas</div>
			<div class="text-3xl font-bold text-gray-800">{{ $invoicesCount }}</div>
		</div>
		<div class="bg-white p-4 rounded shadow">
			<div class="text-sm text-gray-600">Pagamentos aprovados</div>
			<div class="text-3xl font-bold text-gray-800">R$ {{ number_format($paymentsApproved,2,',','.') }}</div>
		</div>
		<div class="bg-white p-4 rounded shadow">
			<div class="text-sm text-gray-600">Application fee total</div>
			<div class="text-3xl font-bold text-gray-800">R$ {{ number_format($applicationFees,2,',','.') }}</div>
		</div>
	</div>

	<div class="mt-6 text-sm text-gray-600">Powered by QFiscal</div>

	<!-- Financeiro: Próximos vencimentos e últimos pagamentos -->
	<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
		<div class="bg-white rounded shadow">
			<div class="p-4 border-b font-semibold text-gray-800">Próximos vencimentos</div>
			<div class="p-4 overflow-x-auto">
				<table class="min-w-full text-sm">
					<thead>
						<tr class="bg-gray-50 text-gray-600">
							<th class="text-left px-3 py-2">Tenant</th>
							<th class="text-left px-3 py-2">Vencimento</th>
							<th class="text-right px-3 py-2">Valor mensal</th>
							<th class="text-left px-3 py-2">Plano</th>
						</tr>
					</thead>
					<tbody>
						@forelse(($upcomingExpirations ?? []) as $t)
							<tr class="border-b">
								<td class="px-3 py-2 text-gray-800">{{ $t->fantasy_name ?? $t->name }}</td>
								<td class="px-3 py-2 text-gray-700">{{ optional($t->plan_expires_at)->format('d/m/Y') }}</td>
								<td class="px-3 py-2 text-right text-gray-800">R$ {{ number_format(optional($t->plan)->price ?? 0,2,',','.') }}</td>
								<td class="px-3 py-2 text-gray-700">{{ optional($t->plan)->name ?? '—' }}</td>
							</tr>
						@empty
							<tr><td colspan="4" class="px-3 py-4 text-center text-gray-500">Sem vencimentos pendentes</td></tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</div>
		<div class="bg-white rounded shadow">
			<div class="p-4 border-b font-semibold text-gray-800">Contas vencidas</div>
			<div class="p-4 overflow-x-auto">
				<table class="min-w-full text-sm">
					<thead>
						<tr class="bg-gray-50 text-gray-600">
							<th class="text-left px-3 py-2">Tenant</th>
							<th class="text-left px-3 py-2">Venceu em</th>
							<th class="text-right px-3 py-2">Valor mensal</th>
							<th class="text-left px-3 py-2">Plano</th>
						</tr>
					</thead>
					<tbody>
						@forelse(($overdueExpirations ?? []) as $t)
							<tr class="border-b">
								<td class="px-3 py-2 text-gray-800">{{ $t->fantasy_name ?? $t->name }}</td>
								<td class="px-3 py-2 text-gray-700">{{ optional($t->plan_expires_at)->format('d/m/Y') }}</td>
								<td class="px-3 py-2 text-right text-gray-800">R$ {{ number_format(optional($t->plan)->price ?? 0,2,',','.') }}</td>
								<td class="px-3 py-2 text-gray-700">{{ optional($t->plan)->name ?? '—' }}</td>
							</tr>
						@empty
							<tr><td colspan="4" class="px-3 py-4 text-center text-gray-500">Sem contas vencidas</td></tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</div>
		<div class="bg-white rounded shadow">
			<div class="p-4 border-b font-semibold text-gray-800">Últimos pagamentos aprovados</div>
			<div class="p-4 overflow-x-auto">
				<table class="min-w-full text-sm">
					<thead>
						<tr class="bg-gray-50 text-gray-600">
							<th class="text-left px-3 py-2">Tenant</th>
							<th class="text-left px-3 py-2">Pago em</th>
							<th class="text-right px-3 py-2">Valor</th>
							<th class="text-right px-3 py-2">Fee</th>
						</tr>
					</thead>
					<tbody>
						@forelse(($recentPayments ?? []) as $pay)
							<tr class="border-b">
								<td class="px-3 py-2 text-gray-800">{{ optional(optional($pay->invoice)->tenant)->fantasy_name ?? optional(optional($pay->invoice)->tenant)->name }}</td>
								<td class="px-3 py-2 text-gray-700">{{ optional($pay->paid_at)->format('d/m/Y H:i') }}</td>
								<td class="px-3 py-2 text-right text-gray-800">R$ {{ number_format($pay->amount,2,',','.') }}</td>
								<td class="px-3 py-2 text-right text-gray-700">R$ {{ number_format($pay->application_fee_amount ?? 0,2,',','.') }}</td>
							</tr>
						@empty
							<tr><td colspan="4" class="px-3 py-4 text-center text-gray-500">Sem pagamentos aprovados</td></tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</div>
	</div>
    
    <!-- Lista de Tenants do parceiro -->
    <div class="mt-8 bg-white rounded shadow">
        <div class="p-4 border-b flex items-center justify-between">
            <div class="font-semibold text-gray-800">Clientes</div>
            <form method="GET" action="{{ route('partner.dashboard') }}" class="flex items-center gap-2">
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
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="text-left px-4 py-2">Criado em</th>
                    </tr>
                </thead>
                <tbody>
                    @isset($tenants)
                    @forelse($tenants as $t)
                        <tr class="border-b">
                            <td class="px-4 py-2 text-gray-800">{{ $t->name }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $t->fantasy_name }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $t->cnpj }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ optional($t->plan)->name ?? '—' }}</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-2 py-0.5 text-xs rounded {{ $t->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $t->active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-700">{{ optional($t->created_at)->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">Nenhum tenant encontrado.</td>
                        </tr>
                    @endforelse
                    @endisset
                </tbody>
            </table>
        </div>

    </div>
</x-partner-layout>


