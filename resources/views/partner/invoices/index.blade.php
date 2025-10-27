<x-partner-layout>
	<div class="bg-white rounded shadow">
		<div class="p-4 border-b flex items-center justify-between">
			<div class="font-semibold text-gray-800">Contas</div>
			<form method="GET" action="{{ route('partner.invoices.index') }}" class="flex items-center gap-2">
				<select name="status" class="border rounded p-2">
					<option value="">Status</option>
					@foreach(['open'=>'Em aberto','paid'=>'Pago','canceled'=>'Cancelado','pending'=>'Pendente'] as $k=>$v)
						<option value="{{ $k }}" {{ ($status??'')===$k ? 'selected' : '' }}>{{ $v }}</option>
					@endforeach
				</select>
				<select name="due" class="border rounded p-2">
					<option value="">Vencimento</option>
					<option value="upcoming" {{ ($due??'')==='upcoming'?'selected':'' }}>A vencer</option>
					<option value="overdue" {{ ($due??'')==='overdue'?'selected':'' }}>Vencidas</option>
					<option value="all" {{ ($due??'')==='all'?'selected':'' }}>Todas</option>
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
						<th class="text-left px-4 py-2">Vencimento</th>
						<th class="text-right px-4 py-2">Valor</th>
						<th class="text-left px-4 py-2">Status</th>
						<th class="text-left px-4 py-2">Descrição</th>
					</tr>
				</thead>
				<tbody>
					@forelse($invoices as $inv)
						<tr class="border-b">
							<td class="px-4 py-2 text-gray-800">{{ optional($inv->tenant)->fantasy_name ?? optional($inv->tenant)->name }}</td>
							<td class="px-4 py-2 text-gray-700">{{ optional($inv->due_date)->format('d/m/Y') }}</td>
							<td class="px-4 py-2 text-right text-gray-800">R$ {{ number_format($inv->amount,2,',','.') }}</td>
							<td class="px-4 py-2 text-gray-700">{{ $inv->status }}</td>
							<td class="px-4 py-2 text-gray-700">{{ $inv->description }}</td>
						</tr>
					@empty
						<tr>
							<td colspan="5" class="px-4 py-6 text-center text-gray-500">Nenhuma conta encontrada.</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
		<div class="p-4">{{ $invoices->links() }}</div>
	</div>
</x-partner-layout>



