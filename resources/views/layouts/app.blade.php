<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $partnerCtx = isset($partner) ? $partner : (auth()->check() ? (optional(Auth::user()->tenant)->partner) : null);
        @endphp
        <title>{{ $partnerCtx ? ($partnerCtx->name . ' - ' . config('app.name','Laravel')) : config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        
        <!-- FontAwesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @if($partnerCtx)
        <style>
            :root{
                --brand-primary: {{ $partnerCtx->primary_color ?? '#059669' }};
                --brand-secondary: {{ $partnerCtx->secondary_color ?? '#047857' }};
            }
            .bg-brand { background-color: var(--brand-primary) !important; }
            .text-brand { color: var(--brand-primary) !important; }
            .border-brand { border-color: var(--brand-primary) !important; }
            .hover\:bg-brand:hover { background-color: var(--brand-secondary) !important; }
            /* Override principais classes verdes para usar a cor do parceiro */
            body.theme-brand .bg-green-600 { background-color: var(--brand-primary) !important; }
            body.theme-brand .bg-green-700 { background-color: var(--brand-primary) !important; }
            body.theme-brand .hover\:bg-green-600:hover { background-color: var(--brand-secondary) !important; }
            body.theme-brand .text-green-600 { color: var(--brand-primary) !important; }
            body.theme-brand .text-green-700 { color: var(--brand-primary) !important; }
            body.theme-brand .border-green-400 { border-color: var(--brand-primary) !important; }
            body.theme-brand .border-green-500 { border-color: var(--brand-primary) !important; }
            body.theme-brand .border-green-600 { border-color: var(--brand-primary) !important; }
            body.theme-brand .from-green-600 { --tw-gradient-from: var(--brand-primary) !important; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(16, 185, 129, 0)); }
            body.theme-brand .to-green-700 { --tw-gradient-to: var(--brand-secondary) !important; }
        </style>
        @endif
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        @php $theme = \App\Models\Setting::get('ui.theme','light'); @endphp
        @if(($theme ?? 'light') === 'dark')
        <style>
            body.dark .bg-white { background-color:#1f2937 !important; }
            body.dark .bg-gray-50 { background-color:#111827 !important; }
            body.dark .text-gray-700 { color:#e5e7eb !important; }
            body.dark .text-gray-600 { color:#d1d5db !important; }
            body.dark .text-gray-500 { color:#9ca3af !important; }
            body.dark .border-gray-200 { border-color:#374151 !important; }
            body.dark .border-gray-300 { border-color:#4b5563 !important; }
            body.dark .bg-white .hover\:bg-gray-100:hover { background-color:#374151 !important; }
            /* Inputs em dark: fundo claro e texto preto para legibilidade */
            body.dark input,
            body.dark select,
            body.dark textarea { background-color:#ffffff !important; color:#111827 !important; border-color:#9ca3af !important; }
            body.dark input::placeholder,
            body.dark textarea::placeholder { color:#6b7280 !important; }
            body.dark label { color:#e5e7eb !important; }
            /* Cards/conteúdo */
            body.dark .shadow { box-shadow: 0 1px 2px rgba(0,0,0,0.6) !important; }
            body.dark .bg-gray-100 { background-color:#0f172a !important; }
            body.dark .text-gray-800 { color:#f3f4f6 !important; }
        </style>
        @endif
        
        <!-- Sidebar Collapse CSS -->
        <style>
            /* CSS para controle da sidebar colapsada */
            .sidebar-collapsed .sidebar-text {
                display: none !important;
            }
            .sidebar-collapsed .sidebar-badge {
                display: none !important;
            }
            .sidebar-collapsed .sidebar-divider-text {
                display: none !important;
            }
            .sidebar-collapsed .sidebar-divider-line {
                display: block !important;
            }
            .sidebar-collapsed .sidebar-icon {
                margin-right: 0 !important;
            }
            .sidebar-collapsed .sidebar-link {
                justify-content: center !important;
                padding: 0.5rem 1rem !important;
            }

            /* Aplicar comportamento também para todos os links do menu, mesmo os que não usam o componente */
            .sidebar-collapsed nav a {
                font-size: 0 !important; /* esconde textos planos (text nodes) */
                justify-content: center !important;
                padding: 0.5rem 1rem !important;
            }
            .sidebar-collapsed nav a svg {
                margin-right: 0 !important;
            }
            /* Se um link tiver badges genéricas, escondê-las */
            .sidebar-collapsed nav a .badge,
            .sidebar-collapsed nav a .tag,
            .sidebar-collapsed nav a .label,
            .sidebar-collapsed nav a span,
            .sidebar-collapsed nav a strong {
                display: none !important;
            }
            
            /* Tooltips só aparecem quando colapsada */
            .sidebar-link .tooltip {
                opacity: 0;
                pointer-events: none;
                visibility: hidden;
            }
            .sidebar-collapsed .sidebar-link:hover .tooltip {
                opacity: 1;
                pointer-events: auto;
                visibility: visible;
            }
            
            /* Estados padrão */
            .sidebar-divider-line {
                display: none;
            }
            
            /* Transições suaves */
            .sidebar-text, .sidebar-badge, .sidebar-divider-text {
                transition: opacity 0.2s ease-in-out;
            }
            
            .sidebar-icon {
                transition: margin 0.3s ease-in-out;
            }
            
            .tooltip {
                transition: opacity 0.2s ease-in-out, visibility 0.2s ease-in-out;
            }
        </style>
    </head>
    <body class="{{ $theme==='dark' ? 'dark bg-gray-900 text-gray-100' : 'bg-gray-100' }} {{ $partnerCtx ? 'theme-brand' : '' }}" 
          x-data="{ sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true' }" 
          @resize.window="if (window.innerWidth < 1024) sidebarCollapsed = true"
          :class="{ 'sidebar-collapsed': sidebarCollapsed }">
        <div class="min-h-screen flex">
            <!-- Sidebar -->
            <div :class="sidebarCollapsed ? 'w-16' : 'w-64'" class="{{ $theme==='dark' ? 'bg-gray-800 text-white' : 'bg-gray-900 text-white' }} flex-shrink-0 transition-all duration-300 ease-in-out relative overflow-hidden">
                <div class="flex items-center justify-center h-16 {{ $partnerCtx ? 'bg-brand' : 'bg-green-700' }} relative">
                    @php
                        $defaultLogo = asset('logo/logo_transp.png');
                        $partnerLogo = null;
                        try {
                            if ($partnerCtx && $partnerCtx->logo_path && \Storage::disk('public')->exists($partnerCtx->logo_path)) {
                                $partnerLogo = asset('storage/' . ltrim($partnerCtx->logo_path, '/'));
                            }
                        } catch (\Throwable $e) { $partnerLogo = null; }
                    @endphp
                    <img src="{{ $partnerLogo ?? $defaultLogo }}" :class="sidebarCollapsed ? 'h-8 w-8 rounded-full object-cover' : 'h-10 w-auto'" class="transition-all duration-300" alt="Logo">
                    
                    <!-- Toggle Button -->
                    <button @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebarCollapsed', sidebarCollapsed)" 
                            class="absolute -right-3 top-1/2 transform -translate-y-1/2 w-6 h-6 bg-white rounded-full shadow-lg border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-all duration-200 z-10"
                            :class="{ 'text-gray-700': !sidebarCollapsed, 'text-gray-500': sidebarCollapsed }">
                        <svg :class="sidebarCollapsed ? 'rotate-180' : ''" class="w-3 h-3 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                </div>
                <nav class="mt-8 space-y-1">
                    @php
                        $tenantId = Auth::user()->tenant_id ?? null;
                        $today = now()->toDateString();
                        $overdueRecCount = 0;
                        $overduePayCount = 0;
                        if ($tenantId) {
                            try {
                                $overdueRecCount = \App\Models\Receivable::where('tenant_id', $tenantId)
                                    ->whereIn('status', ['open','partial'])
                                    ->whereDate('due_date','<', $today)
                                    ->count();
                                $overduePayCount = \App\Models\Payable::where('tenant_id', $tenantId)
                                    ->whereIn('status', ['open','partial'])
                                    ->whereDate('due_date','<', $today)
                                    ->count();
                            } catch (\Throwable $e) { /* ignore */ }
                        }
                    @endphp
                    <x-sidebar-link 
                        href="{{ route('dashboard') }}" 
                        :active="request()->routeIs('dashboard')"
                        label="Dashboard">
                        <x-slot name="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </x-slot>
                    </x-sidebar-link>
                    
                    <x-sidebar-divider label="Cadastros" />
                    
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('clients.view'))
                    <x-sidebar-link 
                        href="{{ route('clients.index') }}" 
                        :active="request()->routeIs('clients.*')"
                        label="Clientes">
                        <x-slot name="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 009.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </x-slot>
                    </x-sidebar-link>
                    @endif
                    
                    @if(method_exists(auth()->user(), 'hasRoleSlug') && auth()->user()->hasRoleSlug('admin'))
                    <a href="{{ route('users.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('users.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 009.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Usuários
                    </a>
                    @endif
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('products.view'))
                    <a href="{{ route('products.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('products.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        Produtos
                    </a>
                    @endif
                    @php $lm_nfe = (bool) config('app.limited_mode', false); $isFree_nfe = optional(auth()->user()->tenant?->plan)->slug === 'free'; @endphp
                    <a href="{{ route('nfe.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('nfe.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }} {{ ($lm_nfe || $isFree_nfe) ? 'opacity-60 pointer-events-none cursor-not-allowed' : '' }}" title="{{ ($lm_nfe || $isFree_nfe) ? 'Indisponível no seu plano' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Notas de Saída
                        @php
                            $lm = (bool) config('app.limited_mode', false);
                            $tenantPlanSlug = optional(auth()->user()->tenant?->plan)->slug;
                            $isFreePlan = $tenantPlanSlug === 'free';
                        @endphp
                        @if($lm || $isFreePlan)
                            <span class="ml-2 text-xs px-2 py-0.5 rounded {{ $lm ? 'bg-yellow-500' : 'bg-gray-500' }}">{{ $lm ? 'Limitado' : 'Free' }}</span>
                        @endif
                    </a>
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('categories.view'))
                    <a href="{{ route('categories.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('categories.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        Categorias
                    </a>
                    @endif
                    <x-sidebar-divider label="Estoque" />
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('stock.view'))
                    <a href="{{ route('stock.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('stock.index') ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        Estoque
                    </a>
                    @endif
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('inbound_invoices.view'))
                    @php $lm_inb = (bool) config('app.limited_mode', false); $isFree_inb = optional(auth()->user()->tenant?->plan)->slug === 'free'; @endphp
                    <a href="{{ route('inbound.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('inbound.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }} {{ ($lm_inb || $isFree_inb) ? 'opacity-60 pointer-events-none cursor-not-allowed' : '' }}" title="{{ ($lm_inb || $isFree_inb) ? 'Indisponível no seu plano' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m-4-4h8M5 7h14a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z" />
                        </svg>
                        Notas de Entrada
                    </a>
                    @endif
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('stock.create'))
                    <a href="{{ route('stock.create') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('stock.create') ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                        </svg>
                        Entrada/Saída de Estoque
                    </a>
                    @endif
                    <x-sidebar-divider label="Financeiro" />
                    <a href="{{ route('cash.show') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('cash.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M5 12h14M7 17h10" />
                        </svg>
                        Caixa do Dia
                    </a>
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('cash.withdraw.view'))
                    <a href="{{ route('cash_withdrawals.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('cash_withdrawals.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Sangria do Caixa
                    </a>
                    @endif
                    @php $lm_menu = (bool) config('app.limited_mode', false); $isFree_menu = optional(auth()->user()->tenant?->plan)->slug === 'free'; @endphp
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('receivables.view'))
                    <x-sidebar-link 
                        href="{{ route('receivables.index') }}" 
                        class="{{ ($lm_menu || $isFree_menu) ? 'opacity-60 pointer-events-none cursor-not-allowed' : '' }}"
                        :active="request()->routeIs('receivables.*') && !request('has_boleto')"
                        label="A Receber"
                        :badge="$overdueRecCount > 0 ? $overdueRecCount : null">
                        <x-slot name="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 1.343-4 3s1.79 3 4 3 4 1.343 4 3m-4-12v2m0 10v2" />
                        </x-slot>
                    </x-sidebar-link>
                    
                    <x-sidebar-link 
                        href="{{ route('receivables.index', ['has_boleto' => 1]) }}" 
                        class="{{ ($lm_menu || $isFree_menu) ? 'opacity-60 pointer-events-none cursor-not-allowed' : '' }}"
                        :active="request()->routeIs('receivables.*') && request('has_boleto')"
                        label="Boletos">
                        <x-slot name="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2-2 4 4m0 0l4-4m-4 4V3" />
                        </x-slot>
                    </x-sidebar-link>
                    @endif
                    
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('payables.view'))
                    <x-sidebar-link 
                        href="{{ route('payables.index') }}" 
                        class="{{ ($lm_menu || $isFree_menu) ? 'opacity-60 pointer-events-none cursor-not-allowed' : '' }}"
                        :active="request()->routeIs('payables.*')"
                        label="A Pagar"
                        :badge="$overduePayCount > 0 ? $overduePayCount : null">
                        <x-slot name="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 1.343-4 3s1.79 3 4 3 4 1.343 4 3m-4-12v2m0 10v2" />
                        </x-slot>
                    </x-sidebar-link>
                    @endif
                    <div class="px-6 pt-4 pb-2 text-xs uppercase tracking-wide text-gray-400">Serviços</div>
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('service_orders.view'))
                    <a href="{{ route('service_orders.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('service_orders.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-7 4h8M7 8h10M5 6h14l-1 12a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6z" />
                        </svg>
                        Ordens de Serviço
                    </a>
                    @endif
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('receipts.view'))
                    <a href="{{ route('receipts.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('receipts.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14h6m-7 4h8M7 10h10M5 6h14v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6z" />
                        </svg>
                        Recibos
                    </a>
                    @endif
                    <x-sidebar-divider label="Vendas" />
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('quotes.view'))
                    <a href="{{ route('quotes.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('quotes.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18M9 8h6m-9 5h12m-9 5h6" />
                        </svg>
                        Orçamentos
                    </a>
                    @endif
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('orders.view'))
                    <a href="{{ route('orders.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('orders.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M5 12h14M7 17h10" />
                        </svg>
                        Pedidos
                    </a>
                    @endif
                    @php
                        $lm_sidebar = (bool) config('app.limited_mode', false);
                        $tenantPlanSlug_sidebar = optional(auth()->user()->tenant?->plan)->slug;
                        $isFreePlan_sidebar = $tenantPlanSlug_sidebar === 'free';
                    @endphp
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('pos.view'))
                    <a href="{{ route('pos.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('pos.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }} {{ ($lm_sidebar || $isFreePlan_sidebar) ? 'opacity-60 pointer-events-none cursor-not-allowed' : '' }}" title="{{ ($lm_sidebar || $isFreePlan_sidebar) ? 'Indisponível no seu plano' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m-4-4h8M5 7h14a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z" />
                        </svg>
                        PDV
                    </a>
                    @endif
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('returns.view'))
                    <a href="{{ route('returns.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('returns.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M4 10a8 8 0 0114.32-4.906M20 14a8 8 0 01-14.32 4.906" />
                        </svg>
                        Devoluções
                    </a>
                    @endif
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('carriers.view'))
                    <a href="{{ route('carriers.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('carriers.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }} {{ ($lm_menu || $isFree_menu) ? 'opacity-60 pointer-events-none cursor-not-allowed' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 012-2h4l4 4v4a2 2 0 01-2 2H11a2 2 0 01-2-2zM9 17H7a2 2 0 01-2-2v-2a2 2 0 012-2h2" />
                        </svg>
                        Transportadoras
                    </a>
                    @endif
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('suppliers.view'))
                    <a href="{{ route('suppliers.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('suppliers.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }} {{ ($lm_menu || $isFree_menu) ? 'opacity-60 pointer-events-none cursor-not-allowed' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 01-8 0 4 4 0 118 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Fornecedores
                    </a>
                    @endif
                    @if(method_exists(auth()->user(), 'hasPermission') && (auth()->user()->hasPermission('tax_config.view') || auth()->user()->hasPermission('tax_rates.view') || auth()->user()->hasRoleSlug('accountant')))
                    <div class="px-6 pt-4 pb-2 text-xs uppercase tracking-wide text-gray-400">Contabilidade</div>
                    @endif
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('tax_rates.view'))
                    <a href="{{ route('tax_rates.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('tax_rates.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Tributações
                    </a>
                    @endif
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('tax_config.edit'))
                    @php $lm_nav = (bool) config('app.limited_mode', false); $isFree_nav = optional(auth()->user()->tenant?->plan)->slug === 'free'; @endphp
                    <a href="{{ route('ncm_rules.index') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('ncm_rules.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }} {{ ($lm_nav || $isFree_nav) ? 'opacity-60 pointer-events-none cursor-not-allowed' : '' }}" title="{{ ($lm_nav || $isFree_nav) ? 'Recurso disponível apenas em planos pagos' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18M8 8h8M5 13h14M7 18h10" />
                        </svg>
                        Regras NCM → GTIN
                    </a>
                    @endif
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('tax_config.view'))
                    <a href="{{ route('settings.fiscal.edit') }}" class="flex items-center px-6 py-2 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('settings.fiscal.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Configurações Fiscais
                    </a>
                    @endif
                    @if(method_exists(auth()->user(), 'hasPermission') && (auth()->user()->hasPermission('reports.view') || auth()->user()->hasPermission('calendar.view') || (auth()->user()->is_admin || auth()->user()->hasRoleSlug('admin'))))
                    <div class="px-6 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider sidebar-divider-text">Relatórios</div>
                    <div class="h-px bg-gray-700 sidebar-divider-line"></div>
                    @endif
                    @if(auth()->user()->is_admin || auth()->user()->hasRoleSlug('admin'))
                    <a href="{{ route('activity.index') }}" class="flex items-center px-6 py-3 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('activity.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Atividades
                    </a>
                    @endif
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('reports.view'))
                    <a href="{{ route('reports.index') }}" class="flex items-center px-6 py-3 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('reports.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }} {{ ($lm_menu || $isFree_menu) ? 'opacity-60 pointer-events-none cursor-not-allowed' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        Relatórios
                    </a>
                    @endif
                    @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('calendar.view'))
                    <a href="{{ route('calendar.index') }}" class="flex items-center px-6 py-3 text-white hover:bg-green-600 transition-colors {{ request()->routeIs('calendar.*') ? 'bg-green-600 border-r-4 border-green-400' : '' }} {{ ($lm_menu || $isFree_menu) ? 'opacity-60 pointer-events-none cursor-not-allowed' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M3 11h18M5 7h14a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z" />
                        </svg>
                        Calendário
                    </a>
                    @endif
                </nav>
            </div>

            <!-- Content -->
            <div class="flex-1 flex flex-col">
                <!-- Top bar -->
                <div class="sticky top-0 z-10 flex items-center justify-between h-16 {{ $theme==='dark' ? 'bg-gray-800 text-gray-100' : 'bg-white' }} shadow-sm px-6">
                    <div class="flex items-center">
                        <span class="text-gray-700">Bem-vindo ao QFiscal</span>
                    </div>
                    <div class="relative" x-data="{ open:false }">
                        <button @click="open=!open" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open=false" x-transition class="absolute right-0 mt-2 w-80 bg-white border rounded shadow-lg z-20">
                            <div class="p-4 border-b">
                                <div class="font-semibold">Meu Perfil</div>
                                <div class="text-sm text-gray-600">{{ Auth::user()->email }}</div>
                                <div class="mt-2">
                                    <a href="{{ route('profile.edit') }}" class="text-green-700 text-sm hover:underline">Editar perfil</a>
                                </div>
                            </div>
                            @php
                                $tenant = Auth::user()->tenant;
                                $plan = optional($tenant)->plan;
                                $nextDue = $tenant?->plan_expires_at ? \Carbon\Carbon::parse($tenant->plan_expires_at) : null;
                                // Últimos 5 pagamentos aprovados (faturas pagas) do tenant
                                $invQuery = $tenant
                                    ? \App\Models\Payment::with('invoice')
                                        ->whereHas('invoice', function($q) use ($tenant){ $q->where('tenant_id', $tenant->id); })
                                        ->where('status','approved')
                                        ->orderByDesc('paid_at')
                                        ->limit(5)
                                        ->get()
                                    : collect();
                            @endphp
                            <div class="p-4 border-b">
                                <div class="font-semibold mb-1">Assinatura</div>
                                <div class="text-sm">Plano: <span class="font-medium">{{ $plan->name ?? '—' }}</span></div>
                                <div class="text-sm">Próx. vencimento: <span class="font-medium">{{ $nextDue ? $nextDue->format('d/m/Y') : '—' }}</span></div>
                                <div class="mt-2">
                                    <a href="{{ route('plans.upgrade') }}" class="text-green-700 text-sm hover:underline">Gerenciar plano</a>
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="font-semibold mb-2">Últimas faturas pagas</div>
                                <div class="max-h-40 overflow-auto space-y-2">
                                    @forelse($invQuery as $pay)
                                        @php $inv = $pay->invoice; @endphp
                                        <div class="flex items-center justify-between text-sm">
                                            <div>
                                                <div class="text-gray-800">Pago em: {{ optional($pay->paid_at)->format('d/m/Y H:i') }}</div>
                                                <div class="text-xs text-gray-500">{{ $inv?->description ?? 'Assinatura' }}</div>
                                            </div>
                                            <div class="font-medium">R$ {{ number_format($pay->amount ?? $inv?->amount ?? 0, 2, ',', '.') }}</div>
                                        </div>
                                    @empty
                                        <div class="text-sm text-gray-500">Sem faturas pagas.</div>
                                    @endforelse
                                </div>
                                <div class="mt-3 text-right">
                                    <a href="{{ route('billing.invoices.index') }}" class="text-green-700 text-sm hover:underline">Ver todas</a>
                                </div>
                            </div>
                            <div class="p-3 border-t bg-gray-50 text-right">
                                <a href="{{ route('reports.index') }}" class="inline-flex items-center px-3 py-2 bg-gray-200 text-gray-800 rounded text-sm mr-2">Ver relatórios</a>
                                <a href="{{ route('reports.print', ['from'=>now()->startOfMonth()->toDateString(),'to'=>now()->endOfMonth()->toDateString()]) }}" target="_blank" class="inline-flex items-center px-3 py-2 bg-gray-200 text-gray-800 rounded text-sm">Imprimir relatórios</a>
                            </div>
                            @if(($planFeatures['has_emissor'] ?? false) && !($limitedMode ?? false))
                            <div class="p-3 border-t bg-green-50 text-center">
                                <a href="#" onclick="downloadEmissor()" class="inline-flex items-center px-3 py-2 bg-green-600 text-white rounded text-sm font-medium">
                                    <i class="fas fa-download mr-2"></i>Baixar Emissor Fiscal
                                </a>
                                <p class="text-xs text-green-700 mt-1">Acesso ao emissor fiscal incluído</p>
                            </div>
                            @endif
                            <div class="p-3 border-t bg-gray-50 flex justify-between items-center">
                                @if(method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('settings.edit'))
                                <a href="{{ route('settings.edit') }}" class="inline-flex items-center px-3 py-2 bg-green-600 text-white rounded text-sm">Configurações</a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}" class="text-right">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-3 py-2 bg-gray-800 text-white rounded text-sm">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Sair
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Header slot (página) -->
                @isset($header)
                    <div class="{{ $theme==='dark' ? 'bg-gray-800 border-b border-gray-700' : 'bg-white border-b border-gray-200' }} px-6 py-4">
                        {{ $header }}
                    </div>
                @endisset

                <!-- Main content -->
                <main class="flex-1 overflow-x-hidden {{ $theme==='dark' ? 'bg-gray-900' : 'bg-gray-100' }} p-6">
                    @if(isset($slot))
                        {{ $slot }}
                    @else
                        @yield('content')
                    @endif
                </main>
            </div>
        </div>
        @livewireScripts
        
        <script>
            function downloadEmissor() {
                // Por enquanto, mostrar uma mensagem informativa
                alert('Emissor Fiscal\n\nPara baixar o emissor, entre em contato conosco:\nEmail: contato@qfiscal.com.br\nWhatsApp: 947146126\n\nO emissor será disponibilizado em breve!');
            }
        </script>
        @stack('scripts')
    </body>
</html>
