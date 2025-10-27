<x-guest-layout>
    <div class="max-w-xl mx-auto mt-10 bg-white p-6 rounded shadow">
        <h1 class="text-xl font-semibold mb-3">OS #{{ $serviceOrder->number }}</h1>
        <p class="mb-2">{{ $message }}</p>
        <p class="text-sm text-gray-600">Título: {{ $serviceOrder->title }}</p>
    </div>
</x-guest-layout>


