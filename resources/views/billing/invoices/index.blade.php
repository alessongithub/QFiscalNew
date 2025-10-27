<x-app-layout>
    <div class="bg-white shadow rounded p-4">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold text-gray-800">Faturas pagas</h1>
            <a href="{{ route('plans.upgrade') }}" class="text-green-700 hover:underline text-sm">Gerenciar plano</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600">
                        <th class="text-left px-3 py-2">Pago em</th>
                        <th class="text-left px-3 py-2">Descrição</th>
                        <th class="text-right px-3 py-2">Valor</th>
                        <th class="text-left px-3 py-2">Método</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $pay)
                        <tr class="border-b">
                            <td class="px-3 py-2 text-gray-800">{{ optional($pay->paid_at)->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ optional($pay->invoice)->description ?? 'Assinatura' }}</td>
                            <td class="px-3 py-2 text-right text-gray-800">R$ {{ number_format($pay->amount ?? optional($pay->invoice)->amount ?? 0, 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $pay->method ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-4 text-center text-gray-500">Nenhum pagamento aprovado encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $payments->links() }}
        </div>
    </div>
</x-app-layout>




