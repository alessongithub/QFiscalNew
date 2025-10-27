@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
  <!-- Header -->
  <div class="mb-8">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Notas Fiscais (NF-e)</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Gerencie suas notas fiscais eletrônicas</p>
      </div>
      <div class="flex space-x-3">
        <button onclick="window.location.reload()" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">
          <i class="fas fa-sync-alt mr-2"></i>Atualizar
        </button>
      </div>
    </div>
  </div>

  <!-- Filters Card -->
  <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg mb-6">
    <div class="p-6">
      <form method="GET" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar</label>
            <input type="text" name="s" value="{{ request('s') }}" 
                   placeholder="Buscar por chave, número ou pedido..." 
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-100">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-100">
              <option value="">Todos os status</option>
              @foreach(['transmitida'=>'Transmitida','cancelada'=>'Cancelada','com_cc'=>'Com CC-e','error'=>'Com Erro','pending'=>'Pendente'] as $k=>$v)
                <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
              @endforeach
            </select>
          </div>
          <div class="flex items-end">
            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
              <i class="fas fa-search mr-2"></i>Filtrar
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Inutilização Card -->
  <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg mb-6">
    <div class="p-6">
      @if(session('success'))
        <div class="hidden" id="__toast_success" data-message="{{ session('success') }}"></div>
      @endif
      @if(session('error'))
        <div class="hidden" id="__toast_error" data-message="{{ session('error') }}"></div>
      @endif
      @if ($errors->any())
        @foreach ($errors->all() as $err)
          <div class="hidden __toast_error_multi" data-message="{{ $err }}"></div>
        @endforeach
      @endif
      <details class="group">
        <summary class="cursor-pointer flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
          <div class="flex items-center">
            <i class="fas fa-ban mr-3 text-gray-600 dark:text-gray-400"></i>
            <span class="font-medium text-gray-900 dark:text-gray-100">Inutilizar Numeração</span>
          </div>
          <i class="fas fa-chevron-down text-gray-400 group-open:rotate-180 transition-transform"></i>
        </summary>
        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
          @if ($errors->any())
            <div class="mb-3 p-3 rounded border border-red-200 bg-red-50 text-red-700 text-sm">
              <ul class="list-disc ml-5">
                @foreach ($errors->all() as $err)
                  <li>{{ $err }}</li>
                @endforeach
              </ul>
            </div>
          @endif
          <form action="{{ route('nfe.inutilizar') }}" method="POST" class="space-y-4">
            @csrf
            <div class="mb-2">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Documento</label>
              <div class="inline-flex items-center bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden">
                <label class="px-4 py-2 cursor-pointer flex items-center">
                  <input type="radio" name="__tipo_doc" value="55" class="mr-2" checked>
                  NF-e (modelo 55)
                </label>
                <label class="px-4 py-2 cursor-pointer flex items-center border-l border-gray-300 dark:border-gray-600">
                  <input type="radio" name="__tipo_doc" value="65" class="mr-2">
                  NFC-e (modelo 65)
                </label>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Escolha o tipo para ajustarmos automaticamente o modelo e sugerirmos a série.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CNPJ</label>
                <input id="inut_cnpj" name="emit_cnpj" placeholder="00.000.000/0000-00" 
                       value="{{ old('emit_cnpj') }}"
                       class="w-full px-3 py-2 border {{ $errors->has('emit_cnpj') ? 'border-red-400' : 'border-gray-300 dark:border-gray-600' }} rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-100">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ano</label>
                <input id="inut_ano" name="ano" placeholder="25" 
                       value="{{ old('ano') }}"
                       class="w-full px-3 py-2 border {{ $errors->has('ano') ? 'border-red-400' : 'border-gray-300 dark:border-gray-600' }} rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-100">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Modelo</label>
                <input id="inut_modelo" name="modelo" value="{{ old('modelo', '55') }}" placeholder="55 ou 65" 
                       class="w-full px-3 py-2 border {{ $errors->has('modelo') ? 'border-red-400' : 'border-gray-300 dark:border-gray-600' }} rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-100">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Série</label>
                <input id="inut_serie" name="serie" placeholder="1" 
                       value="{{ old('serie') }}"
                       class="w-full px-3 py-2 border {{ $errors->has('serie') ? 'border-red-400' : 'border-gray-300 dark:border-gray-600' }} rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-100">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número Inicial</label>
                <input id="inut_numero_inicial" name="numero_inicial" placeholder="1" 
                       value="{{ old('numero_inicial') }}"
                       class="w-full px-3 py-2 border {{ $errors->has('numero_inicial') ? 'border-red-400' : 'border-gray-300 dark:border-gray-600' }} rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-100">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número Final</label>
                <input id="inut_numero_final" name="numero_final" placeholder="Opcional" 
                       value="{{ old('numero_final') }}"
                       class="w-full px-3 py-2 border {{ $errors->has('numero_final') ? 'border-red-400' : 'border-gray-300 dark:border-gray-600' }} rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-100">
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Justificativa</label>
              <input id="inut_justificativa" name="justificativa" placeholder="Justificativa para inutilização (mínimo 15 caracteres)" 
                     value="{{ old('justificativa') }}"
                     class="w-full px-3 py-2 border {{ $errors->has('justificativa') ? 'border-red-400' : 'border-gray-300 dark:border-gray-600' }} rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-100">
            </div>
            <div class="flex justify-between items-center">
              <button type="button" id="btn-inut-prefill" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-magic mr-2"></i>Preencher com meus dados
              </button>
              <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors" 
                      onclick="return confirm('Confirma a inutilização desta numeração?')">
                <i class="fas fa-ban mr-2"></i>Inutilizar Numeração
              </button>
            </div>
          </form>
        </div>
      </details>
    </div>
  </div>

  @php
    $recentInut = $inutPageItems ?? [];
  @endphp
  @if(count($recentInut) > 0)
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg mb-6">
      <div class="p-6">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            <i class="fas fa-ban text-red-500 mr-2"></i>Eventos de Inutilização
          </h3>
          <div class="text-sm text-gray-600 dark:text-gray-300">{{ $inutTotal ?? 0 }} eventos</div>
        </div>
        <div class="space-y-2">
          @foreach($recentInut as $ev)
            <div class="flex items-center justify-between text-sm border border-gray-200 dark:border-gray-700 rounded p-2">
              <div class="flex items-center space-x-3">
                <span class="text-gray-700 dark:text-gray-200">{{ $ev['at'] ?? '' }}</span>
                <span class="text-gray-600 dark:text-gray-300">Série {{ $ev['serie'] ?? '' }}</span>
                <span class="text-gray-600 dark:text-gray-300">Faixa {{ $ev['numero_inicial'] ?? '' }}{{ (!empty($ev['numero_final']) && $ev['numero_final'] != $ev['numero_inicial']) ? '–'.$ev['numero_final'] : '' }}</span>
                <span class="{{ in_array((string)($ev['cStat'] ?? ''), ['102','135','173']) ? 'text-green-700' : 'text-gray-700' }}">cStat: {{ $ev['cStat'] ?? '—' }}</span>
              </div>
              <div>
                @if(!empty($ev['xml_path']) && file_exists($ev['xml_path']))
                  <a href="{{ route('nfe.inut_xml', $nfes->first() ?? new \App\Models\NfeNote()) }}?path={{ urlencode($ev['xml_path']) }}" target="_blank" class="inline-flex items-center px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded">
                    <i class="fas fa-download mr-1"></i>XML
                  </a>
                @else
                  <form method="POST" action="{{ route('nfe.inut.reprocess') }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded" title="Tentar localizar XML/cStat recentes">
                      <i class="fas fa-sync-alt mr-1"></i>Reprocessar
                    </button>
                  </form>
                @endif
              </div>
            </div>
          @endforeach
        </div>
        @if(($inutTotalPages ?? 1) > 1)
          <div class="mt-4 flex items-center justify-end space-x-2">
            @php $cp = $inutPage ?? 1; $tp = $inutTotalPages ?? 1; @endphp
            @if($cp > 1)
              <a href="{{ request()->fullUrlWithQuery(['inut_page' => $cp - 1]) }}" class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs">Anterior</a>
            @endif
            <span class="text-xs text-gray-600 dark:text-gray-300">Página {{ $cp }} de {{ $tp }}</span>
            @if($cp < $tp)
              <a href="{{ request()->fullUrlWithQuery(['inut_page' => $cp + 1]) }}" class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs">Próxima</a>
            @endif
          </div>
        @endif
      </div>
    </div>
  @endif

  <!-- NFe Table Card -->
  <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
    <div class="p-6">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pedido</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Número NFe</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Chave de Acesso</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($nfes as $n)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                {{ optional($n->emitted_at ?: $n->data_emissao ?: $n->created_at)->format('d/m/Y H:i') }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                {{ $n->order?->number ?? $n->numero_pedido }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                {{ $n->numero_nfe_resolved ?? '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                <div class="max-w-xs font-mono js-key-toggle" data-full="{{ $n->chave_acesso_resolved ?? '' }}" title="{{ $n->chave_acesso_resolved ?? '' }}">
                  <span class="js-key-short">
                    {{ ($n->chave_acesso_resolved ?? null) ? Str::limit($n->chave_acesso_resolved, 25, '...') : '-' }}
                  </span>
                  <span class="js-key-full hidden break-all">{{ $n->chave_acesso_resolved ?? '' }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex flex-col">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    @if($n->status==='emitted' || $n->status==='transmitida') bg-green-100 text-green-800
                    @elseif($n->status==='pending') bg-yellow-100 text-yellow-800
                    @elseif($n->status==='error') bg-red-100 text-red-800
                    @elseif($n->status==='cancelled' || $n->status==='cancelada') bg-gray-100 text-gray-800
                    @else bg-blue-100 text-blue-800 @endif">
                    @if($n->status==='emitted' || $n->status==='transmitida')
                      <i class="fas fa-check-circle mr-1"></i>Transmitida
                    @elseif($n->status==='pending')
                      <i class="fas fa-clock mr-1"></i>Pendente
                    @elseif($n->status==='error')
                      <i class="fas fa-exclamation-triangle mr-1"></i>Erro
                    @elseif($n->status==='cancelled' || $n->status==='cancelada')
                      <i class="fas fa-ban mr-1"></i>Cancelada
                    @else
                      {{ $n->status_name ?? strtoupper($n->status ?? '') }}
                    @endif
                  </span>
                  @if($n->status === 'error' && !empty($n->error_message))
                    <details class="mt-1">
                      <summary class="text-xs text-red-600 cursor-pointer hover:text-red-800">Ver erro</summary>
                      <div class="mt-1 p-2 bg-red-50 dark:bg-red-900/20 rounded text-xs text-red-700 dark:text-red-300 max-w-xs">
                        {{ Str::limit($n->error_message, 150) }}
                      </div>
                    </details>
                  @endif
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <div class="flex items-center space-x-2">
                  <a href="{{ route('nfe.show', $n) }}" 
                     class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-xs font-medium transition-colors">
                    <i class="fas fa-cogs mr-1.5"></i>Gerenciar NF-e
                  </a>
                  {{-- Cancelamento só no gerenciamento/detalhe --}}
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                <div class="flex flex-col items-center">
                  <i class="fas fa-file-invoice text-4xl mb-4 text-gray-300"></i>
                  <p class="text-lg font-medium">Nenhuma nota fiscal encontrada</p>
                  <p class="text-sm">Tente ajustar os filtros ou criar uma nova NFe</p>
                </div>
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">{{ $nfes->links() }}</div>
    </div>
  </div>
</div>
<!-- Cancelamento via modal apenas no gerenciamento/detalhe -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  var toggles = document.querySelectorAll('.js-key-toggle');
  toggles.forEach(function(el){
    var shortEl = el.querySelector('.js-key-short');
    var fullEl = el.querySelector('.js-key-full');
    el.style.cursor = 'pointer';
    el.addEventListener('click', function(){
      if (!fullEl || !shortEl) return;
      var isHidden = fullEl.classList.contains('hidden');
      if (isHidden) {
        fullEl.classList.remove('hidden');
        shortEl.classList.add('hidden');
      } else {
        fullEl.classList.add('hidden');
        shortEl.classList.remove('hidden');
      }
    });
    // Duplo clique copia para a área de transferência
    el.addEventListener('dblclick', function(){
      var text = (fullEl && !fullEl.classList.contains('hidden')) ? fullEl.textContent.trim() : (el.getAttribute('data-full') || '');
      if (!text) return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function(){
          el.setAttribute('data-title-old', el.getAttribute('title') || '');
          el.setAttribute('title', 'Copiado!');
          setTimeout(function(){ el.setAttribute('title', el.getAttribute('data-title-old') || ''); }, 1200);
        }).catch(function(){});
      }
    });
  });
  // Prefill inutilização
  (function(){
    try{
      var btn = document.getElementById('btn-inut-prefill');
      if (btn){
        btn.addEventListener('click', function(){
          try{
            var suggest = @json($inutPrefill ?? []);
            if (suggest && typeof suggest === 'object'){
              document.getElementById('inut_cnpj').value = (suggest.emit_cnpj||'');
              document.getElementById('inut_ano').value = (suggest.ano||'');
              document.getElementById('inut_modelo').value = (suggest.modelo||'55');
              document.getElementById('inut_serie').value = (suggest.serie||'1');
              document.getElementById('inut_numero_inicial').value = (suggest.numero_inicial||'');
              document.getElementById('inut_numero_final').value = (suggest.numero_final||'');
            }
          }catch(e){}
        });
      }
      // Toggle 55/65 → ajusta modelo e sugere série
      var radios = document.querySelectorAll('input[name="__tipo_doc"]');
      var modelo = document.getElementById('inut_modelo');
      var serie = document.getElementById('inut_serie');
      function suggestSerie(val){
        try{
          // Séries padrão: NF-e => Setting nfe.series (ou 1), NFC-e => Setting nfce.series (ou 1)
          var sNfe = @json((string) \App\Models\Setting::get('nfe.series','1'));
          var sNfce = @json((string) \App\Models\Setting::get('nfce.series','1'));
          return (val==='65') ? (sNfce||'1') : (sNfe||'1');
        }catch(e){ return '1'; }
      }
      if (radios && modelo){
        radios.forEach(function(r){
          r.addEventListener('change', function(){
            try {
              var val = this.value;
              modelo.value = val;
              if (serie && !serie.value){ serie.value = suggestSerie(val); }
            } catch(e){}
          });
        });
      }
    }catch(e){}
  })();

// Toast simples
(function(){
  function showToast(msg, type){
    try{
      var wrap = document.createElement('div');
      wrap.style.position = 'fixed';
      wrap.style.right = '20px';
      wrap.style.bottom = '20px';
      wrap.style.zIndex = '9999';
      var el = document.createElement('div');
      el.style.padding = '10px 14px';
      el.style.borderRadius = '8px';
      el.style.color = type==='error' ? '#991B1B' : '#065F46';
      el.style.background = type==='error' ? '#FEE2E2' : '#D1FAE5';
      el.style.border = type==='error' ? '1px solid #FCA5A5' : '1px solid #A7F3D0';
      el.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
      el.style.fontSize = '14px';
      el.style.maxWidth = '420px';
      el.style.wordBreak = 'break-word';
      el.innerText = msg;
      wrap.appendChild(el);
      document.body.appendChild(wrap);
      setTimeout(function(){
        try{ document.body.removeChild(wrap); }catch(e){}
      }, 4500);
    }catch(e){}
  }
  var ok = document.getElementById('__toast_success');
  if (ok && ok.dataset.message){ showToast(ok.dataset.message, 'success'); }
  var er = document.getElementById('__toast_error');
  if (er && er.dataset.message){ showToast(er.dataset.message, 'error'); }
  var errs = document.querySelectorAll('.__toast_error_multi');
  if (errs && errs.length){
    errs.forEach(function(node){
      var msg = node.getAttribute('data-message') || '';
      if (msg){ showToast(msg, 'error'); }
    });
  }
})();
});
</script>
@endsection

