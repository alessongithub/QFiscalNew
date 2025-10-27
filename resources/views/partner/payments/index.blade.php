<x-partner-layout>
	<div class="bg-white rounded shadow">
		<div class="p-4 border-b flex items-center justify-between">
			<div class="font-semibold text-gray-800">Pagamentos</div>
			<form method="GET" action="{{ route('partner.payments.index') }}" class="flex items-center gap-2">
				<select name="status" class="border rounded p-2">
					<option value="">Status</option>
					@foreach(['approved'=>'Aprovado','pending'=>'Pendente','refunded'=>'Estornado','rejected'=>'Rejeitado'] as $k=>$v)
						<option value="{{ $k }}" {{ ($status??'')===$k ? 'selected' : '' }}>{{ $v }}</option>
					@endforeach
				</select>
				<input type="date" name="from" value="{{ $dateFrom ?? '' }}" class="border rounded p-2">
				<input type="date" name="to" value="{{ $dateTo ?? '' }}" class="border rounded p-2">
				<button class="px-3 py-2 bg-gray-800 text-white rounded">Filtrar</button>
			</form>
		</div>
		<div class="overflow-x-auto">
			<table class="min-w-full text-sm">
				<thead>
					<tr class="bg-gray-50 text-gray-600">
						<th class="text-left px-4 py-2">Tenant</th>
						<th class="text-left px-4 py-2">Pago em</th>
						<th class="text-right px-4 py-2">Valor</th>
						<th class="text-right px-4 py-2">Fee</th>
						<th class="text-left px-4 py-2">Status</th>
					</tr>
				</thead>
				<tbody>
					@forelse($payments as $p)
						<tr class="border-b">
							<td class="px-4 py-2 text-gray-800">{{ optional(optional($p->invoice)->tenant)->fantasy_name ?? optional(optional($p->invoice)->tenant)->name }}</td>
							<td class="px-4 py-2 text-gray-700">{{ optional($p->paid_at)->format('d/m/Y H:i') }}</td>
							<td class="px-4 py-2 text-right text-gray-800">R$ {{ number_format($p->amount,2,',','.') }}</td>
							<td class="px-4 py-2 text-right text-gray-700">R$ {{ number_format($p->application_fee_amount ?? 0,2,',','.') }}</td>
							<td class="px-4 py-2 text-gray-700">{{ $p->status }}</td>
						</tr>
					@empty
						<tr>
							<td colspan="5" class="px-4 py-6 text-center text-gray-500">Nenhum pagamento encontrado.</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
		<div class="p-4">{{ $payments->links() }}</div>
	</div>
</x-partner-layout>










