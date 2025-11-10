<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Planos e Assinaturas') }}
        </h2>
    </x-slot>

    <div class="py-8 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        {!! session('error') !!}
                    </div>
                </div>
            @endif

            <div class="text-center mb-10">
                <h1 class="text-4xl font-bold text-gray-900 mb-3">Escolha o Plano Ideal</h1>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">Selecione o plano que melhor se adapta às necessidades da sua empresa</p>
            </div>

            @php
                $tenant = auth()->user()->tenant;
                $expiresAt = $tenant?->plan_expires_at;
                $isExpired = $expiresAt ? \Carbon\Carbon::parse($expiresAt)->isPast() : false;
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                @foreach($plans as $plan)
                    @php 
                        $isCurrent = false;
                        $planId = (int)($plan->id ?? 0);
                        $planSlug = $plan->slug ?? '';
                        $currentId = (int)($currentPlanId ?? 0);
                        $currentSlug = $currentPlanSlug ?? '';

                        if ($currentId > 0 && $planId > 0) {
                            $isCurrent = ($currentId === $planId);
                        }
                        if (!$isCurrent && $currentSlug && $planSlug) {
                            $isCurrent = (strtolower($currentSlug) === strtolower($planSlug));
                        }
                    @endphp

                    <div class="relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden {{ $isCurrent ? 'ring-4 ring-green-500 ring-opacity-50 scale-105' : '' }} flex flex-col">
                        @if($isCurrent)
                            <div class="absolute top-0 right-0 bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-1 text-xs font-bold rounded-bl-lg">
                                PLANO ATUAL
                            </div>
                        @endif

                        <div class="p-8 flex flex-col h-full">
                            <div class="mb-6">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
                                <div class="flex items-baseline">
                                    <span class="text-3xl font-extrabold text-gray-900">R$</span>
                                    <span class="text-3xl font-extrabold text-gray-900 ml-1">{{ number_format($plan->price, 2, ',', '.') }}</span>
                                    <span class="text-gray-500 ml-2">/mês</span>
                                </div>
                                @if((string)($plan->price) !== '0.00' && (float)$plan->price > 0.0)
                                    <p class="text-sm text-gray-500 mt-1">Cobrança mensal recorrente</p>
                                @endif
                            </div>

                            @php
                                $features = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []);
                                $displayFeatures = $features['display_features'] ?? [];
                            @endphp

                            <div class="border-t border-gray-200 pt-6 mb-6">
                                <ul class="space-y-4">
                                    @foreach($displayFeatures as $feature)
                                        <li class="flex items-start">
                                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="text-gray-700">{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            @php
                                $slugLower = strtolower($planSlug);
                                $celcoinLinks = [
                                    'basico' => 'https://celcash.celcoin.com.br/qfiscal/basico',
                                    'plano-enterprise' => 'https://celcash.celcoin.com.br/qfiscal/plano-enterprise',
                                    'plano-platinum' => 'https://celcash.celcoin.com.br/qfiscal/plano-platinum',
                                    'plano-profissional' => 'https://celcash.celcoin.com.br/qfiscal/plano-profissional',
                                ];
                                // Normalizar slug por aproximação
                                $normalizedSlug = null;
                                if (isset($celcoinLinks[$slugLower])) {
                                    $normalizedSlug = $slugLower;
                                } else {
                                    if (str_contains($slugLower, 'basic') || str_contains($slugLower, 'básico') || str_contains($slugLower, 'basico')) {
                                        $normalizedSlug = 'basico';
                                    } elseif (str_contains($slugLower, 'enterprise')) {
                                        $normalizedSlug = 'plano-enterprise';
                                    } elseif (str_contains($slugLower, 'platinum')) {
                                        $normalizedSlug = 'plano-platinum';
                                    } elseif (str_contains($slugLower, 'prof') || str_contains($slugLower, 'professional') || str_contains($slugLower, 'profissional')) {
                                        $normalizedSlug = 'plano-profissional';
                                    }
                                }
                                $celcoinUrl = $normalizedSlug ? ($celcoinLinks[$normalizedSlug] ?? null) : null;
                                $isFree = (string)($plan->price) === '0.00' || (float)$plan->price <= 0.0;
                            @endphp

                            <div class="mt-auto">
                                @if($isCurrent)
                                    @if($isExpired)
                                        @if($isFree)
                                            <form method="POST" action="{{ route('plans.upgrade.process') }}">
                                                @csrf
                                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                                <button type="submit" class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                                    Renovar Plano
                                                </button>
                                            </form>
                                        @elseif($celcoinUrl)
                                            <a href="{{ $celcoinUrl }}" target="_blank" rel="noopener" class="block w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 text-center">
                                                Renovar Plano
                                            </a>
                                        @else
                                            <form method="POST" action="{{ route('plans.upgrade.process') }}">
                                                @csrf
                                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                                <button type="submit" class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                                    Renovar Plano
                                                </button>
                                            </form>
                                        @endif
                                        <div class="mt-3 text-center">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                </svg>
                                                Assinatura vencida
                                            </span>
                                        </div>
                                    @else
                                        <button disabled class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold py-3 px-6 rounded-xl shadow-md cursor-not-allowed opacity-75">
                                            <span class="flex items-center justify-center">
                                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                                Plano Atual
                                            </span>
                                        </button>
                                    @endif
                                @else
                                    @if($isFree)
                                        <form method="POST" action="{{ route('plans.upgrade.process') }}">
                                            @csrf
                                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                            <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                                Escolher Plano
                                            </button>
                                        </form>
                                    @elseif($celcoinUrl)
                                        <a href="{{ $celcoinUrl }}" target="_blank" rel="noopener" class="block w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 text-center">
                                            Escolher Plano
                                        </a>
                                        <div class="mt-3 text-center">
                                            <p class="text-xs text-gray-500 flex items-center justify-center">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                Pagamento seguro
                                            </p>
                                        </div>
                                    @else
                                        <form method="POST" action="{{ route('plans.upgrade.process') }}">
                                            @csrf
                                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                            <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                                Escolher Plano
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-12">
                <p class="text-gray-600 mb-4">Dúvidas sobre os planos?</p>
                <a href="{{ route('support.create') }}" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium underline">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                    Entre em contato com nosso suporte
                </a>
            </div>
        </div>
    </div>
</x-app-layout>


