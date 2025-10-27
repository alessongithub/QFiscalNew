<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Checkout do Plano</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto">
      @if($errors->any())
        <div class="mb-3 p-3 rounded bg-red-50 text-red-800">
          {{ $errors->first() }}
        </div>
      @endif

      <div class="border rounded p-4 space-y-4 bg-white">
        <p class="text-sm text-gray-700">Plano selecionado: <strong>{{ $plan?->name ?? 'Seu plano atual' }}</strong></p>
        <p class="text-2xl font-bold">R$ {{ number_format($plan?->price ?? 0, 2, ',', '.') }}</p>

        <form method="POST" action="{{ route('checkout.create') }}" class="space-y-3">
          @csrf
          <input type="hidden" name="plan_id" value="{{ $plan?->id }}">
          <p class="text-sm text-gray-700">Pagamento via Mercado Pago (cartão 1x ou Pix).</p>
          <button class="px-4 py-2 bg-blue-600 text-white rounded">Pagar com Mercado Pago</button>
        </form>

        <p class="text-xs text-gray-500">Ao clicar, você será direcionado para o ambiente de pagamento seguro do Mercado Pago.</p>
      </div>
    </div>
</x-app-layout>


