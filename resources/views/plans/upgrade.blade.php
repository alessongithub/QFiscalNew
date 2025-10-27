<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Upgrade de Plano') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Escolha seu novo plano:</h3>

                    @if(session('error'))
                        <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded">
                            {!! session('error') !!}
                        </div>
                    @endif

                    @php
                        $tenant = auth()->user()->tenant;
                        $expiresAt = $tenant?->plan_expires_at;
                        $isExpired = $expiresAt ? \Carbon\Carbon::parse($expiresAt)->isPast() : false;
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach($plans as $plan)
                            @php 
                                $isCurrent = false;
                                $planId = (int)($plan->id ?? 0);
                                $planSlug = $plan->slug ?? '';
                                $currentId = (int)($currentPlanId ?? 0);
                                $currentSlug = $currentPlanSlug ?? '';

                                // Debug
                                \Log::info("Plan comparison", [
                                    'plan_id' => $planId,
                                    'plan_slug' => $planSlug,
                                    'current_id' => $currentId,
                                    'current_slug' => $currentSlug
                                ]);

                                if ($currentId > 0 && $planId > 0) {
                                    $isCurrent = ($currentId === $planId);
                                }
                                if (!$isCurrent && $currentSlug && $planSlug) {
                                    $isCurrent = (strtolower($currentSlug) === strtolower($planSlug));
                                }
                            @endphp

                            <div class="border rounded-lg p-6 {{ $isCurrent ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                <h4 class="text-xl font-bold mb-2">{{ $plan->name }}</h4>
                                <p class="text-3xl font-bold text-green-600 mb-4">
                                    R$ {{ number_format($plan->price, 2, ',', '.') }}
                                </p>

                                @php
                                    $features = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []);
                                    $displayFeatures = $features['display_features'] ?? [];
                                @endphp

                                <ul class="space-y-2 mb-6">
                                    @foreach($displayFeatures as $feature)
                                        <li class="flex items-center">
                                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                </ul>

                                @if($isCurrent)
                                    @if($isExpired)
                                        <a href="{{ route('checkout.index', ['plan_id' => $plan->id]) }}" class="w-full inline-block text-center bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded">
                                            Renovar Plano
                                        </a>
                                        <div class="text-xs text-red-700 mt-2">Assinatura vencida. Clique para renovar.</div>
                                    @else
                                        <button disabled class="w-full bg-gray-300 text-gray-500 py-2 px-4 rounded">
                                            Plano Atual
                                        </button>
                                    @endif
                                @else
                                    <form method="POST" action="{{ route('plans.upgrade.process') }}">
                                        @csrf
                                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
                                            Escolher Plano
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


