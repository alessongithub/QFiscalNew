<x-app-layout>
    <!-- Header com saudação -->
    @php
        $brandPrimary = isset($partner) && !empty($partner->primary_color) ? $partner->primary_color : '#059669';
        $brandSecondary = isset($partner) && !empty($partner->secondary_color) ? $partner->secondary_color : '#047857';
        $partnerLogo = isset($partner) && !empty($partner->logo_path) ? \Storage::disk('public')->url($partner->logo_path) : null;
        $defaultLogo = asset('logo/logo_transp.png');
    @endphp
    <div class="text-white rounded-lg shadow-lg p-6 mb-8" style="background: linear-gradient(90deg, {{ $brandPrimary }}, {{ $brandSecondary }});">
        <div class="flex items-center mb-2">
            <h1 class="text-3xl font-bold mr-4">Dashboard</h1>
            <img src="{{ $partnerLogo ?? $defaultLogo }}" class="h-8 w-auto" alt="Logo">
        </div>
        <p class="text-green-100">Gestão completa do seu negócio em um só lugar</p>
    </div>

    {{-- Alertas Financeiros: A Receber e A Pagar --}}
    @php
        $vHojeRec = $dashboardData['todayReceivablesAmount'] ?? 0;
        $cHojeRec = $dashboardData['todayReceivablesCount'] ?? 0;
        $vVencRec = $dashboardData['overdueReceivablesAmount'] ?? 0;
        $cVencRec = $dashboardData['overdueReceivablesCount'] ?? 0;

        $vHojePay = $dashboardData['todayPayablesAmount'] ?? 0;
        $cHojePay = $dashboardData['todayPayablesCount'] ?? 0;
        $vVencPay = $dashboardData['overduePayablesAmount'] ?? 0;
        $cVencPay = $dashboardData['overduePayablesCount'] ?? 0;

        $tenant = auth()->user()->tenant;
        $expiresAt = $tenant->plan_expires_at ?? null;
        $daysToExpire = $expiresAt ? (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($expiresAt)->startOfDay(), false) : null;
    @endphp

    @if($expiresAt && $daysToExpire !== null && $daysToExpire <= 5 && $daysToExpire >= -3)
        <div class="mb-4 p-3 border-l-4 {{ $daysToExpire < 0 ? 'border-red-500 bg-red-50 text-red-800' : 'border-yellow-500 bg-yellow-50 text-yellow-800' }} rounded-r">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-semibold">
                        @if($daysToExpire < 0)
                            Assinatura vencida
                        @elseif($daysToExpire === 0)
                            Assinatura vence hoje
                        @else
                            Assinatura próxima do vencimento
                        @endif
                    </div>
                    <div class="text-sm">Data de expiração: {{ \Carbon\Carbon::parse($expiresAt)->format('d/m/Y') }}</div>
                </div>
                @if(Route::has('checkout.index'))
                    <a href="{{ route('checkout.index', ['plan_id' => $tenant->plan_id]) }}" class="px-3 py-1.5 bg-blue-600 text-white rounded">Pagar Agora</a>
                @endif
            </div>
        </div>
    @endif
    @php
        $blockAfterDays = (int) (\App\Models\GatewayConfig::current()->block_login_after_days ?? 3);
        $daysAfterExpire = ($expiresAt && $daysToExpire !== null && $daysToExpire < 0) ? abs($daysToExpire) : 0;
        $planSlug = optional($tenant?->plan)->slug;
    @endphp

    @if($expiresAt && $daysAfterExpire >= $blockAfterDays && $daysAfterExpire < 15)
        <div class="mb-4 p-3 border-l-4 border-orange-500 bg-orange-50 text-orange-800 rounded-r">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-semibold">Acesso limitado por inadimplência</div>
                    <div class="text-sm">Sua assinatura está vencida há {{ $daysAfterExpire }} dia(s). Emissão de NF-e, boletos e PDV foram desativados até a regularização.</div>
                </div>
                <div class="flex items-center gap-2">
                    @if(Route::has('checkout.index'))
                        <a href="{{ route('checkout.index', ['plan_id' => $tenant->plan_id]) }}" class="px-3 py-1.5 bg-blue-600 text-white rounded">Pagar Agora</a>
                    @endif
                    <a href="{{ route('plans.upgrade') }}" class="px-3 py-1.5 bg-gray-800 text-white rounded">Gerenciar Plano</a>
                </div>
            </div>
        </div>
    @endif

    @if($planSlug === 'free')
        <div class="mb-4 p-3 border-l-4 border-gray-500 bg-gray-50 text-gray-800 rounded-r">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-semibold">Você está no Plano Gratuito</div>
                    <ul class="text-sm list-disc ml-5 mt-1">
                        <li>Cadastrar clientes, criar OS, orçamentos e pedidos</li>
                        <li>Sem emissão de NF-e, boletos, PDV e emissor Delphi</li>
                        <li>Apenas 1 usuário (administrador); sem edição de impostos</li>
                    </ul>
                </div>
                <a href="{{ route('plans.upgrade') }}" class="px-3 py-1.5 bg-green-600 text-white rounded">Fazer Upgrade</a>
            </div>
        </div>
    @endif
    @php
        $approved = $dashboardData['recentApprovedOs'] ?? collect();
        $rejected = $dashboardData['recentRejectedOs'] ?? collect();
    @endphp
    @if(($vHojeRec>0) || ($vVencRec>0) || ($vHojePay>0) || ($vVencPay>0))
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @if($vHojeRec>0)
        <a href="{{ route('receivables.index', ['date_from'=>now()->toDateString(),'date_to'=>now()->toDateString(),'status'=>['open','partial']]) }}" class="block bg-yellow-50 border-l-4 border-yellow-500 rounded-r p-4 hover:bg-yellow-100 transition">
            <div class="text-sm text-yellow-800 font-semibold">A Receber Hoje</div>
            <div class="text-gray-800 text-lg font-bold">R$ {{ number_format($vHojeRec, 2, ',', '.') }}</div>
            <div class="text-xs text-yellow-700">{{ $cHojeRec }} títulos</div>
        </a>
        @endif
        @if($vVencRec>0)
        <a href="{{ route('receivables.index', ['overdue'=>1]) }}" class="block bg-red-50 border-l-4 border-red-500 rounded-r p-4 hover:bg-red-100 transition">
            <div class="text-sm text-red-800 font-semibold">A Receber Vencido</div>
            <div class="text-gray-800 text-lg font-bold">R$ {{ number_format($vVencRec, 2, ',', '.') }}</div>
            <div class="text-xs text-red-700">{{ $cVencRec }} títulos</div>
        </a>
        @endif
        @if($vHojePay>0)
        <a href="{{ route('payables.index', ['date_from'=>now()->toDateString(),'date_to'=>now()->toDateString(),'status'=>['open','partial']]) }}" class="block bg-yellow-50 border-l-4 border-yellow-500 rounded-r p-4 hover:bg-yellow-100 transition">
            <div class="text-sm text-yellow-800 font-semibold">A Pagar Hoje</div>
            <div class="text-gray-800 text-lg font-bold">R$ {{ number_format($vHojePay, 2, ',', '.') }}</div>
            <div class="text-xs text-yellow-700">{{ $cHojePay }} títulos</div>
        </a>
        @endif
        @if($vVencPay>0)
        <a href="{{ route('payables.index', ['overdue'=>1]) }}" class="block bg-red-50 border-l-4 border-red-500 rounded-r p-4 hover:bg-red-100 transition">
            <div class="text-sm text-red-800 font-semibold">A Pagar Vencido</div>
            <div class="text-gray-800 text-lg font-bold">R$ {{ number_format($vVencPay, 2, ',', '.') }}</div>
            <div class="text-xs text-red-700">{{ $cVencPay }} títulos</div>
        </a>
        @endif
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-4">
        <!-- Card Clientes -->
        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow p-6 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-700">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h2 class="text-gray-600 text-sm font-medium">Total de Clientes</h2>
                    <p class="text-3xl font-bold text-gray-800">{{ $dashboardData['totalClients'] }}</p>
                    <span class="text-green-600 text-sm font-medium">Ativos</span>
                </div>
            </div>
        </div>

        <!-- Card NF-e emitidas no mês -->
        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-700">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h2 class="text-gray-600 text-sm font-medium">NFe emitidas</h2>
                    <p class="text-3xl font-bold text-gray-800">{{ $dashboardData['nfeCountMonth'] ?? 0 }}</p>
                    <span class="text-blue-600 text-sm font-medium">Este mês</span>
                </div>
            </div>
        </div>

        <!-- Card Receitas -->
        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow p-6 border-l-4 border-emerald-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-emerald-100 text-emerald-700">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h2 class="text-gray-600 text-sm font-medium">Receitas do Mês</h2>
                    <p class="text-3xl font-bold text-gray-800">R$ {{ number_format($dashboardData['monthlyRevenue'], 2, ',', '.') }}</p>
                    <span class="text-emerald-600 text-sm font-medium">Este mês</span>
                </div>
            </div>
        </div>

        <!-- Card Tributos -->
        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow p-6 border-l-4 border-red-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-700">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h2 class="text-gray-600 text-sm font-medium">Tributos do Mês</h2>
                    <p class="text-3xl font-bold text-gray-800">R$ {{ number_format($dashboardData['monthlyTaxes'], 2, ',', '.') }}</p>
                    <span class="text-red-600 text-sm font-medium">A pagar</span>
                </div>
            </div>
        </div>
    </div>

    @if(($approved && count($approved)) || ($rejected && count($rejected)))
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            @if($approved && count($approved))
            <div class="bg-green-50 border-l-4 border-green-600 rounded-r p-4" style="resize: both; overflow: auto; min-width: 280px; max-width: 800px; min-height: 120px;">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-green-800 font-semibold">OS aprovadas hoje</div>
                    <a href="{{ route('service_orders.index', ['status'=>'in_progress']) }}" class="text-green-700 text-sm underline">ver OS</a>
                </div>
                <ul class="space-y-1 text-sm text-green-900">
                    @foreach($approved as $o)
                        <li class="flex items-center justify-between">
                            <span>#{{ $o->number }} - {{ $o->title }}</span>
                            <span class="text-green-700">{{ optional($o->approved_at)->format('H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if($rejected && count($rejected))
            <div class="bg-red-50 border-l-4 border-red-600 rounded-r p-4" style="resize: both; overflow: auto; min-width: 280px; max-width: 800px; min-height: 120px;">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-red-800 font-semibold">OS rejeitadas hoje</div>
                    <a href="{{ route('service_orders.index', ['status'=>'in_progress']) }}" class="text-red-700 text-sm underline">ver OS</a>
                </div>
                <ul class="space-y-1 text-sm text-red-900">
                    @foreach($rejected as $o)
                        <li class="flex items-center justify-between">
                            <span>#{{ $o->number }} - {{ $o->title }}</span>
                            <span class="text-red-700">{{ optional($o->rejected_at)->format('H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    @endif

    <!-- Novidades -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-3 bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Novidades</h3>
            @php
                $newsItems = \App\Models\News::where('active',true)
                    ->orderByDesc('published_at')
                    ->take(5)->get();
                if ($newsItems->isEmpty()) {
                    $newsItems = collect([
                        (object)['title'=>'Novo módulo de Calendário','body'=>'Agenda integrada com A Receber/A Pagar e eventos.','image_url'=>null,'link_url'=>null,'published_at'=>now()],
                        (object)['title'=>'Relatórios com impressão','body'=>'Relatórios por período com versão para imprimir.','image_url'=>null,'link_url'=>null,'published_at'=>now()->subDay()],
                        (object)['title'=>'Vendas: Orçamentos e Pedidos','body'=>'Cadastre orçamentos e converta em pedidos.','image_url'=>null,'link_url'=>null,'published_at'=>now()->subDays(2)],
                    ]);
                }
            @endphp
            <div x-data="{ i: 0, total: {{ $newsItems->count() }}, next(){ this.i = (this.i+1)%this.total }, prev(){ this.i = (this.i-1+this.total)%this.total }, init(){ this.$nextTick(()=>{ if(this.total>1){ setInterval(()=>this.next(), 5000) } }) } }" class="relative">
                <div class="overflow-hidden">
                    @foreach($newsItems as $idx => $n)
                        <div x-show="i === {{ $idx }}" x-transition class="border rounded p-3 min-h-[150px]">
                            <div class="flex gap-4">
                                @if(!empty($n->image_url))
                                    <img src="{{ $n->image_url }}" class="w-32 h-24 object-cover rounded" />
                                @endif
                                <div class="flex-1">
                                    <div class="text-sm text-gray-500">{{ optional($n->published_at)->format('d/m/Y') }}</div>
                                    <div class="font-semibold mb-1">{{ $n->title }}</div>
                                    @if(!empty($n->body))
                                        <div class="text-sm text-gray-700 line-clamp-3">{{ $n->body }}</div>
                                    @endif
                                    @if(!empty($n->link_url))
                                        <a href="{{ $n->link_url }}" target="_blank" class="text-green-700 text-sm hover:underline">Saiba mais</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="flex items-center justify-between mt-2" x-show="total>1">
                    <button @click="prev()" class="px-3 py-1 bg-gray-100 rounded hover:bg-gray-200">Anterior</button>
                    <div class="space-x-1">
                        @foreach($newsItems as $idx => $n)
                            <span class="inline-block w-2 h-2 rounded-full" :class="i==={{ $idx }} ? 'bg-green-600' : 'bg-gray-300'"></span>
                        @endforeach
                    </div>
                    <button @click="next()" class="px-3 py-1 bg-gray-100 rounded hover:bg-gray-200">Próximo</button>
                </div>
            </div>
        </div>
    </div>

    

    <!-- Seção de Gráficos e Tabelas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Gráfico de Faturamento -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Faturamento Mensal</h3>
            <div class="h-64 flex items-center justify-center border-2 border-dashed border-gray-300 rounded-lg">
                <div class="text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <p class="text-gray-500">Gráfico será implementado</p>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Ações Rápidas</h3>
            <div class="space-y-3">
                <a href="{{ route('clients.create') }}" class="flex items-center p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span class="text-gray-700 font-medium">Novo Cliente</span>
                </a>
                <a href="{{ route('orders.create') }}" class="flex items-center p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="text-gray-700 font-medium">Nova Nota Fiscal</span>
                </a>
                <a href="#" class="flex items-center p-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span class="text-gray-700 font-medium">Novo Produto</span>
                </a>
                <a href="#" class="flex items-center p-3 bg-orange-50 hover:bg-orange-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="text-gray-700 font-medium">Ver Relatórios</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Assinatura (Resumo) -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Sua Assinatura</h3>
        @php
            $tenant = auth()->user()->tenant;
            $nextDue = $tenant?->plan_expires_at ? \Carbon\Carbon::parse($tenant->plan_expires_at) : null;
            $days = $nextDue ? (int) now()->startOfDay()->diffInDays($nextDue, false) : null;
        @endphp
        <div class="flex flex-wrap items-center gap-4">
            <div>Plano atual: <span class="font-semibold">{{ optional($tenant?->plan)->name ?? '—' }}</span></div>
            <div>Próximo vencimento: <span class="font-semibold">{{ $nextDue ? $nextDue->format('d/m/Y') : '—' }}</span></div>
            @if(!is_null($days))
                <div class="text-sm {{ $days <= 5 ? 'text-red-600' : 'text-gray-600' }}">{{ $days >= 0 ? 'Faltam ' . (int)$days . ' dias' : 'Vencido há ' . (int)abs($days) . ' dias' }}</div>
            @endif
            @php $isExpired = $nextDue ? $nextDue->isPast() : false; @endphp
            <div class="ml-auto space-x-2">
                @if($isExpired)
                    <a href="{{ route('checkout.index', ['plan_id' => $tenant->plan_id]) }}" class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded">Renovar Agora</a>
                @endif
            </div>
        </div>
    </div>

    <!-- Últimas Notas Fiscais e Pendências -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Últimas NF-e -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Últimas NF-e</h3>
                <span class="text-sm text-gray-500">Este mês</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 text-sm font-medium text-gray-600">Pedido/NFe</th>
                            <th class="text-left py-2 text-sm font-medium text-gray-600">Cliente</th>
                            <th class="text-left py-2 text-sm font-medium text-gray-600">Emissão</th>
                            <th class="text-center py-2 text-sm font-medium text-gray-600">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $latest = $dashboardData['latestNfe'] ?? collect(); @endphp
                        @forelse($latest as $n)
                            <tr class="border-b border-gray-100">
                                <td class="py-2">
                                    <div class="text-sm text-gray-800">{{ $n->numero_pedido }}</div>
                                    @if($n->numero_nfe)
                                        <div class="text-xs text-gray-500">NFe: {{ $n->numero_nfe }}</div>
                                    @endif
                                </td>
                                <td class="py-2 text-sm text-gray-800">{{ optional($n->client)->name ?? '—' }}</td>
                                <td class="py-2 text-sm text-gray-800">{{ optional($n->emitted_at ?? $n->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="py-2 text-center">
                                    @php $badge = $n->status_badge ?? 'bg-gray-100 text-gray-700'; @endphp
                                    <span class="px-2 py-1 rounded text-xs {{ $badge }}">{{ $n->status_name ?? ucfirst($n->status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="py-4 text-center text-gray-500" colspan="4">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="text-gray-500">Nenhuma NF-e encontrada</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pendências Fiscais (NFe pendentes/erro) -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Pendências Fiscais</h3>
                <span class="bg-red-100 text-red-800 text-xs font-medium px-2 py-1 rounded-full">{{ $dashboardData['fiscalPendenciesCount'] ?? 0 }} pendentes</span>
            </div>
            <div class="space-y-3">
                @php $pend = $dashboardData['fiscalPendencies'] ?? collect(); @endphp
                @forelse($pend as $p)
                    <div class="flex items-center p-3 {{ $p->status==='error' ? 'bg-red-50 border-l-4 border-red-400' : 'bg-yellow-50 border-l-4 border-yellow-400' }} rounded-r-lg">
                        <svg class="w-5 h-5 {{ $p->status==='error' ? 'text-red-600' : 'text-yellow-600' }} mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L4.316 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800">Pedido {{ $p->numero_pedido }} — {{ optional($p->client)->name ?? 'Cliente' }}</p>
                            <p class="text-xs text-gray-600">{{ $p->status_name ?? ucfirst($p->status) }} • Atualizado {{ optional($p->updated_at)->diffForHumans() }}</p>
                            @if($p->error_message)
                                <p class="text-xs text-red-700 mt-1">{{ \Illuminate\Support\Str::limit($p->error_message, 140) }}</p>
                            @endif
                        </div>
                        <a href="{{ route('nfe.show', $p->id) }}" class="text-green-700 text-sm hover:underline">ver</a>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">Sem pendências fiscais no momento.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>