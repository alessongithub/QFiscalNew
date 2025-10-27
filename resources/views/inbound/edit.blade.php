<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Conferir Nota de Entrada</h2>
            <a href="{{ route('inbound.index') }}" class="text-gray-700">Voltar</a>
        </div>
    </x-slot>

    @php
        $initialItems = [];
        foreach ($items as $iit) {
            $initialItems[$iit->id] = ['action' => $iit->linked_product_id ? 'ignore' : 'link'];
        }
    @endphp
    <div class="bg-white p-6 rounded shadow" x-data='{"items": @json($initialItems)}'>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <div class="text-xs text-gray-500">Número/Série</div>
                <div class="font-semibold">{{ $inbound->number }} / {{ $inbound->series }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Emissão</div>
                <div class="font-semibold">{{ optional($inbound->issue_date)->format('d/m/Y') }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Total</div>
                <div class="font-semibold">R$ {{ number_format($inbound->total_invoice, 2, ',', '.') }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('inbound.update', $inbound) }}">
            @csrf @method('PUT')
            <div class="mb-4">
                <label class="block text-sm text-gray-700 mb-1">Fornecedor</label>
                <select name="supplier_id" class="w-full border rounded px-3 py-2">
                    <option value="">Selecione</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected($inbound->supplier_id==$s->id)>{{ $s->name }} ({{ $s->cpf_cnpj }})</option>
                    @endforeach
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2 px-2">Código</th>
                            <th class="py-2 px-2">Descrição</th>
                            <th class="py-2 px-2">EAN</th>
                            <th class="py-2 px-2">Qtd</th>
                            <th class="py-2 px-2">UN</th>
                            <th class="py-2 px-2">Vlr Unit</th>
                            <th class="py-2 px-2">Ação</th>
                            <th class="py-2 px-2">Produto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $it)
                        <tr class="border-b">
                            <td class="py-2 px-2">{{ $it->product_code }}</td>
                            <td class="py-2 px-2">{{ $it->product_name }}</td>
                            <td class="py-2 px-2">{{ $it->ean }}</td>
                            <td class="py-2 px-2">{{ number_format($it->quantity, 3, ',', '.') }}</td>
                            <td class="py-2 px-2">{{ $it->unit }}</td>
                            <td class="py-2 px-2">R$ {{ number_format($it->unit_price, 4, ',', '.') }}</td>
                            <td class="py-2 px-2">
                                <select name="items[{{ $it->id }}][action]" class="border rounded px-2 py-1 w-32" x-model="items['{{ $it->id }}'].action" @change="if(($event.target.value==='link' || $event.target.value==='create') && ({{ (int)$it->linked_product_id ?: '0' }} || {{ $it->link_locked ? '1' : '0' }}) ){ $event.target.value='ignore'; alert('Item bloqueado.'); }">
                                    <option value="link" @disabled($it->linked_product_id || $it->link_locked)>Vincular</option>
                                    <option value="create" @disabled($it->linked_product_id || $it->link_locked)>Criar novo</option>
                                    <option value="ignore" @selected($it->linked_product_id || $it->link_locked)>Ignorar</option>
                                </select>
                                @if($it->linked_product_id)
                                    <div class="mt-1 text-xs text-green-700">Vinculado ao produto #{{ $it->linked_product_id }} em {{ optional($it->linked_at)->format('d/m/Y H:i') }}</div>
                                @endif
                            </td>
                            <td class="py-2 px-2">
                                @if(!$it->linked_product_id && !$it->link_locked)
                                <div x-data="productSearch('{{ route('products.search') }}')" class="space-y-1">
                                    <input type="hidden" name="items[{{ $it->id }}][product_id]" :value="selected?.id || '{{ $productCandidates[$it->id] ?? '' }}'">
                                    <input type="text" x-model="term" @input.debounce.300ms="search()" placeholder="Buscar por nome/EAN/SKU" class="border rounded px-2 py-1 w-64">
                                    <div x-show="results.length" class="border rounded bg-white shadow max-h-40 overflow-auto">
                                        <template x-for="r in results" :key="r.id">
                                            <div class="px-2 py-1 hover:bg-gray-100 cursor-pointer" @click="select(r)" x-text="r.name"></div>
                                        </template>
                                    </div>
                                    <div class="text-xs text-gray-500" x-show="selected">Selecionado: <span x-text="selected?.name"></span></div>
                                </div>
                                @else
                                    @if(!$it->link_locked && method_exists(auth()->user(),'hasPermission') && auth()->user()->hasPermission('inbound_invoices.unlink'))
                                    <button type="button" class="text-red-600 text-sm hover:underline" onclick="if(confirm('Desvincular este item? O estoque será estornado.')){ var f=document.getElementById('unlinkForm'); if(f){ f.action='{{ route('inbound.items.unlink', [$inbound, $it]) }}'; f.submit(); } }">Desvincular</button>
                                    @else
                                        <span class="text-xs text-gray-500">Irreversível</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="text-right mt-4">
                <button class="px-4 py-2 bg-green-600 text-white rounded">Processar</button>
            </div>
        </form>
        <form id="unlinkForm" method="POST" action="#" class="hidden">@csrf</form>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        const msg = @json(session('success'));
        const err = @json(session('error'));
        const errs = @json($errors->all() ?? []);
        function toast(text, cls){
            const n = document.createElement('div');
            n.className = `fixed top-4 right-4 px-4 py-2 rounded shadow-lg z-50 ${cls}`;
            n.textContent = text;
            document.body.appendChild(n);
            setTimeout(()=> n.remove(), 3000);
        }
        if (msg) toast(msg, 'bg-green-600 text-white');
        if (err) toast(err, 'bg-red-600 text-white');
        if (errs && errs.length){ errs.forEach(e => toast(e,'bg-red-600 text-white')); }
    });
    function productSearch(url){
        return {
            term: '', results: [], selected: null,
            async search(){
                if(!this.term || this.term.length<2){ this.results=[]; return; }
                const q = new URLSearchParams({ term: this.term });
                const res = await fetch(url+"?"+q.toString());
                this.results = await res.json();
            },
            select(r){ this.selected = r; this.term = r.name; this.results = []; }
        }
    }
    // Auto-confirmar criação e submeter sem categoria
    (function(){
        const form = document.querySelector('form[action*="inbound"]');
        if (!form) return;
        document.querySelectorAll('select[name^="items["][name$="[action]"]').forEach(sel => {
            sel.addEventListener('change', function(){
                if (this.value === 'create') {
                    if (confirm('Criar novo produto para este item agora? Você poderá completar os dados na próxima tela.')) {
                        form.submit();
                    } else {
                        this.value = 'ignore';
                    }
                }
            });
        });
    })();
    </script>
</x-app-layout>


