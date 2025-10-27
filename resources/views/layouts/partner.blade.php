<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Parceiros - {{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-100">
        <div class="min-h-screen flex">
            <div class="w-60 bg-purple-800 text-white flex-shrink-0">
                <div class="h-16 flex items-center justify-center font-semibold">Painel do Parceiro</div>
                <nav class="px-3 space-y-1">
                    <a href="{{ route('partner.dashboard') }}" class="block px-3 py-2 rounded hover:bg-purple-700 {{ request()->routeIs('partner.dashboard') ? 'bg-purple-700' : '' }}">Dashboard</a>
                    <a href="{{ route('partner.tenants.index') }}" class="block px-3 py-2 rounded hover:bg-purple-700 {{ request()->routeIs('partner.tenants.*') ? 'bg-purple-700' : '' }}">Clientes</a>
                    <a href="{{ route('partner.invoices.index') }}" class="block px-3 py-2 rounded hover:bg-purple-700 {{ request()->routeIs('partner.invoices.*') ? 'bg-purple-700' : '' }}">Contas</a>
                    <a href="{{ route('partner.payments.index') }}" class="block px-3 py-2 rounded hover:bg-purple-700 {{ request()->routeIs('partner.payments.*') ? 'bg-purple-700' : '' }}">Pagamentos</a>
                    <a href="{{ route('partner.password') }}" class="block px-3 py-2 rounded hover:bg-purple-700">Alterar senha</a>
                    <form method="POST" action="{{ route('partner.logout') }}" class="px-3 py-2">
                        @csrf
                        <button class="w-full text-left px-3 py-2 rounded bg-purple-900 hover:bg-purple-700">Sair</button>
                    </form>
                </nav>
            </div>

            <div class="flex-1 flex flex-col">
                <div class="h-16 bg-white shadow flex items-center justify-between px-6">
                    <div class="font-semibold">{{ auth('partner')->user()->name ?? 'Parceiro' }}</div>
                    <div class="text-sm text-gray-600">Powered by QFiscal</div>
                </div>
                <main class="p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
    </html>


