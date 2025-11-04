@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6">
    <h1 class="text-2xl font-bold mb-6">Saldo do Tenant</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded shadow p-6">
            <div class="text-sm text-gray-600">Disponível para Transferência</div>
            <div class="text-2xl font-bold text-green-600">R$ {{ number_format($totalAvailable, 2, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded shadow p-6">
            <div class="text-sm text-gray-600">Aguardando Liquidação</div>
            <div class="text-2xl font-bold text-yellow-600">R$ {{ number_format($totalPending, 2, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded shadow p-6">
            <div class="text-sm text-gray-600">Total Transferido</div>
            <div class="text-2xl font-bold text-blue-600">R$ {{ number_format($totalTransferred, 2, ',', '.') }}</div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 rounded bg-green-50 text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-3 rounded bg-red-50 text-red-800">{{ $errors->first() }}</div>
    @endif

    <div class="bg-white rounded shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bruto</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Taxa MP</th>
                    
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Líquido</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($balances as $balance)
                    <tr>
                        <td class="px-4 py-2">{{ optional($balance->payment_received_at)->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">{{ $balance->receivable->description ?? 'N/A' }}</td>
                        <td class="px-4 py-2">R$ {{ number_format($balance->gross_amount, 2, ',', '.') }}</td>
                        <td class="px-4 py-2">R$ {{ number_format($balance->mp_fee_amount, 2, ',', '.') }}</td>
                        
                        <td class="px-4 py-2 font-semibold">R$ {{ number_format($balance->net_amount, 2, ',', '.') }}</td>
                        <td class="px-4 py-2">
                            @switch($balance->status)
                                @case('available')
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded">Disponível</span>
                                    @break
                                @case('pending')
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded">Aguardando</span>
                                    @break
                                @case('requested')
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">Solicitado</span>
                                    @break
                                @case('transferring')
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">Transferindo</span>
                                    @break
                                @case('transferred')
                                    <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded">Transferido</span>
                                    @break
                                @default
                                    <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded">{{ $balance->status }}</span>
                            @endswitch
                        </td>
                        <td class="px-4 py-2">
                            @if($balance->status === 'available')
                                <form action="{{ route('tenant.balance.request-transfer') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="balance_id" value="{{ $balance->id }}">
                                    <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Solicitar Transferência</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-center text-gray-500" colspan="7">Nenhum saldo encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $balances->links() }}</div>
</div>
@endsection


