<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Faturas e Assinaturas</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Assinaturas</h3>
                    @if(($subscriptionPayments->total() ?? 0) === 0)
                        <div class="text-sm text-gray-600">Nenhum pagamento de assinatura encontrado.</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left border-b">
                                        <th class="py-2 pr-4">Data</th>
                                        <th class="py-2 pr-4">Plano</th>
                                        <th class="py-2 pr-4">Valor</th>
                                        <th class="py-2 pr-4">Status</th>
                                        <th class="py-2 pr-4">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscriptionPayments as $sp)
                                        @php
                                            $meta = is_array($sp->metadata) ? $sp->metadata : (json_decode($sp->metadata ?? '{}', true) ?: []);
                                            // Tentativas de extrair boleto
                                            $boletoUrl = $meta['boleto']['url'] ?? $meta['boleto']['pdf'] ?? $meta['payment']['boleto']['url'] ?? $meta['payment']['boleto']['pdf'] ?? $meta['boleto_url'] ?? $meta['pdf'] ?? null;
                                            $linha = $meta['boleto']['digitable_line'] ?? $meta['payment']['boleto']['digitable_line'] ?? $meta['linha_digitavel'] ?? $meta['digitable_line'] ?? $meta['barcode'] ?? null;
                                        @endphp
                                        <tr class="border-b">
                                            <td class="py-2 pr-4">{{ optional($sp->paid_at ?? $sp->created_at)->format('d/m/Y H:i') }}</td>
                                            <td class="py-2 pr-4">{{ $sp->plan->name ?? 'Plano' }}</td>
                                            <td class="py-2 pr-4">R$ {{ number_format($sp->amount, 2, ',', '.') }}</td>
                                            <td class="py-2 pr-4 capitalize">{{ $sp->status }}</td>
                                            <td class="py-2 pr-4 flex gap-2">
                                                @if($boletoUrl)
                                                    <a href="{{ $boletoUrl }}" target="_blank" rel="noopener" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded">Baixar boleto</a>
                                                @endif
                                                @if($linha)
                                                    <button type="button" data-line="{{ $linha }}" class="copy-line px-3 py-1 bg-gray-600 hover:bg-gray-700 text-white rounded">Copiar linha digitável</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $subscriptionPayments->links() }}</div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Pagamentos</h3>
                    @if(($payments->total() ?? 0) === 0)
                        <div class="text-sm text-gray-600">Nenhum pagamento encontrado.</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left border-b">
                                        <th class="py-2 pr-4">Data</th>
                                        <th class="py-2 pr-4">Fatura</th>
                                        <th class="py-2 pr-4">Método</th>
                                        <th class="py-2 pr-4">Valor</th>
                                        <th class="py-2 pr-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $p)
                                        <tr class="border-b">
                                            <td class="py-2 pr-4">{{ optional($p->paid_at)->format('d/m/Y H:i') }}</td>
                                            <td class="py-2 pr-4">#{{ $p->invoice_id }}</td>
                                            <td class="py-2 pr-4">{{ $p->method }}</td>
                                            <td class="py-2 pr-4">R$ {{ number_format($p->amount, 2, ',', '.') }}</td>
                                            <td class="py-2 pr-4 capitalize">{{ $p->status }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $payments->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.copy-line');
        if (!btn) return;
        const line = btn.getAttribute('data-line') || '';
        if (!line) return;
        navigator.clipboard.writeText(line).then(() => {
            btn.textContent = 'Copiado!';
            setTimeout(() => btn.textContent = 'Copiar linha digitável', 1500);
        });
    });
    </script>
</x-app-layout>

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




