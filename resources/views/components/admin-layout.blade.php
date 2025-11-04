<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Administração</title>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <a href="{{ route('admin.dashboard') }}">
                                <x-logo-image />
                            </a>
                        </div>

                        <!-- Navigation Links (grouped) -->
                        <div class="hidden sm:flex sm:items-center sm:ml-10 gap-4">
                            <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                                Dashboard
                            </x-nav-link>

                            <!-- Cadastros -->
                            <x-dropdown align="left" width="60">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-700 hover:text-gray-900">
                                        Cadastros
                                        <svg class="ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"/></svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    <x-dropdown-link :href="route('admin.tenants')" :active="request()->routeIs('admin.tenants')">Tenants</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.news.index')" :active="request()->routeIs('admin.news*')">Novidades</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.ncm_rules.index')" :active="request()->routeIs('admin.ncm_rules*')">Regras NCM→GTIN</x-dropdown-link>
                                </x-slot>
                            </x-dropdown>

                            <!-- Financeiro -->
                            <x-dropdown align="left" width="60">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-700 hover:text-gray-900">
                                        Financeiro
                                        <svg class="ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"/></svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    <x-dropdown-link :href="route('admin.payments')" :active="request()->routeIs('admin.payments')">Pagamentos</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.receivables')" :active="request()->routeIs('admin.receivables')">Boletos</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.balances')" :active="request()->routeIs('admin.balances')">
                                        Saldos
                                        @php $__tb_req = \App\Models\TenantBalance::where('status','requested')->count(); @endphp
                                        @if($__tb_req > 0)
                                            <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium leading-4 rounded-full bg-red-100 text-red-800">{{ $__tb_req }}</span>
                                        @endif
                                    </x-dropdown-link>
                                </x-slot>
                            </x-dropdown>

                            <!-- Configurações -->
                            <x-dropdown align="left" width="60">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-700 hover:text-gray-900">
                                        Configurações
                                        <svg class="ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"/></svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    <x-dropdown-link :href="route('admin.smtp-settings')" :active="request()->routeIs('admin.smtp-settings')">Config. SMTP</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.gateway.edit')" :active="request()->routeIs('admin.gateway.*')">Gateway</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.plans')" :active="request()->routeIs('admin.plans*')">Planos</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.profile')" :active="request()->routeIs('admin.profile')">Perfil</x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </div>

                    <!-- Settings Dropdown -->
                    <div class="hidden sm:flex sm:items-center sm:ml-6">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                    <div>{{ Auth::user()->name }}</div>
                                    <div class="ml-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('admin.profile')">
                                    {{ __('Perfil do Admin') }}
                                </x-dropdown-link>

                                <!-- Authentication -->
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault();
                                                        this.closest('form').submit();">
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main>
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    @if (session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
</body>
</html>
