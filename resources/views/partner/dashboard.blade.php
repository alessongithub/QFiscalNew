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
            <div class="font-semibold text-gray-800">Tenants</div>
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


