<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18M8 8h8M5 13h14M7 18h10" />
                </svg>
                Visualizar Configuração Tributária #{{ $rate->id }}
            </h2>
            <a href="{{ route('tax_rates.index') }}" class="text-gray-600 hover:text-gray-800 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto">
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                <h3 class="text-white text-lg font-semibold">Detalhes da Configuração</h3>
                <p class="text-green-100 text-sm">Informações completas para conferência</p>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-semibold text-gray-600 uppercase mb-2">Identificação</h4>
                    <div class="space-y-2 text-sm">
                        <div><span class="text-gray-500">Nome:</span> <span class="text-gray-900">{{ $rate->name ?: '—' }}</span></div>
                        <div><span class="text-gray-500">Tipo:</span> <span class="text-gray-900">{{ $rate->tipo_nota === 'produto' ? 'Produto' : 'Serviço' }}</span></div>
                        <div><span class="text-gray-500">NCM:</span> <span class="text-gray-900">{{ $rate->ncm ?: '—' }}</span></div>
                        <div><span class="text-gray-500">CFOP:</span> <span class="text-gray-900">{{ $rate->cfop ?: '—' }}</span></div>
                        <div><span class="text-gray-500">Cód. Serviço:</span> <span class="text-gray-900">{{ $rate->codigo_servico ?: '—' }}</span></div>
                        <div><span class="text-gray-500">Status:</span> <span class="text-gray-900">{{ $rate->ativo ? 'Ativo' : 'Inativo' }}</span></div>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-600 uppercase mb-2">Alíquotas</h4>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div><span class="text-gray-500">ICMS:</span> <span class="font-mono">{{ $rate->icms_aliquota !== null ? number_format($rate->icms_aliquota*100,2,',','.') . '%' : '—' }}</span></div>
                        <div><span class="text-gray-500">Red. BC ICMS:</span> <span class="font-mono">{{ $rate->icms_reducao_bc !== null ? number_format($rate->icms_reducao_bc*100,2,',','.') . '%' : '—' }}</span></div>
                        <div><span class="text-gray-500">PIS:</span> <span class="font-mono">{{ $rate->pis_aliquota !== null ? number_format($rate->pis_aliquota*100,2,',','.') . '%' : '—' }}</span></div>
                        <div><span class="text-gray-500">COFINS:</span> <span class="font-mono">{{ $rate->cofins_aliquota !== null ? number_format($rate->cofins_aliquota*100,2,',','.') . '%' : '—' }}</span></div>
                        <div><span class="text-gray-500">ISS:</span> <span class="font-mono">{{ $rate->iss_aliquota !== null ? number_format($rate->iss_aliquota*100,2,',','.') . '%' : '—' }}</span></div>
                        <div><span class="text-gray-500">CSLL:</span> <span class="font-mono">{{ $rate->csll_aliquota !== null ? number_format($rate->csll_aliquota*100,2,',','.') . '%' : '—' }}</span></div>
                        <div><span class="text-gray-500">INSS:</span> <span class="font-mono">{{ $rate->inss_aliquota !== null ? number_format($rate->inss_aliquota*100,2,',','.') . '%' : '—' }}</span></div>
                        <div><span class="text-gray-500">IRRF:</span> <span class="font-mono">{{ $rate->irrf_aliquota !== null ? number_format($rate->irrf_aliquota*100,2,',','.') . '%' : '—' }}</span></div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <h4 class="text-sm font-semibold text-gray-600 uppercase mb-2">ICMS-ST</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2 text-sm">
                        <div><span class="text-gray-500">Modalidade:</span> <span class="text-gray-900">{{ $rate->icmsst_modalidade ?? '—' }}</span></div>
                        <div><span class="text-gray-500">MVA:</span> <span class="font-mono">{{ $rate->icmsst_mva !== null ? number_format($rate->icmsst_mva*100,2,',','.') . '%' : '—' }}</span></div>
                        <div><span class="text-gray-500">Alíquota:</span> <span class="font-mono">{{ $rate->icmsst_aliquota !== null ? number_format($rate->icmsst_aliquota*100,2,',','.') . '%' : '—' }}</span></div>
                        <div><span class="text-gray-500">Red. BC:</span> <span class="font-mono">{{ $rate->icmsst_reducao_bc !== null ? number_format($rate->icmsst_reducao_bc*100,2,',','.') . '%' : '—' }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        if (@json(session('success'))) {
            const n = document.createElement('div');
            n.className = 'fixed top-4 right-4 px-4 py-2 rounded shadow-lg z-50 bg-green-600 text-white';
            n.textContent = @json(session('success'));
            document.body.appendChild(n);
            setTimeout(()=> n.remove(), 3000);
        }
        @if($errors->any())
        const errs = @json($errors->all());
        errs.forEach(function(msg){
            const n = document.createElement('div');
            n.className = 'fixed top-4 right-4 px-4 py-2 mb-2 rounded shadow-lg z-50 bg-red-600 text-white';
            n.textContent = msg;
            document.body.appendChild(n);
            setTimeout(()=> n.remove(), 4000);
        });
        @endif
    });
    </script>
    @endpush
</x-app-layout>


