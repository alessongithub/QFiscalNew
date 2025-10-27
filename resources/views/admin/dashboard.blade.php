<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Administrativo') }}
        </h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card Total de Tenants -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-500 bg-opacity-10">
                            <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-600 text-sm">Total de Tenants</h2>
                            <p class="text-2xl font-semibold text-gray-700">{{ $totalTenants }}</p>
                        </div>
                    </div>
                </div>

                <!-- Card Tenants Ativos -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-500 bg-opacity-10">
                            <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-600 text-sm">Tenants Ativos</h2>
                            <p class="text-2xl font-semibold text-gray-700">{{ $activeTenants }}</p>
                        </div>
                    </div>
                </div>

                <!-- Card Total de Usuários -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-500 bg-opacity-10">
                            <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-600 text-sm">Total de Usuários</h2>
                            <p class="text-2xl font-semibold text-gray-700">{{ $totalUsers }}</p>
                        </div>
                    </div>
                </div>

                <!-- Card Status SMTP -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full {{ $smtpConfig ? 'bg-green-500 bg-opacity-10' : 'bg-red-500 bg-opacity-10' }}">
                            <svg class="h-8 w-8 {{ $smtpConfig ? 'text-green-500' : 'text-red-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-600 text-sm">SMTP</h2>
                            <p class="text-2xl font-semibold {{ $smtpConfig ? 'text-green-600' : 'text-red-600' }}">
                                {{ $smtpConfig ? 'Configurado' : 'Não Configurado' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card Teste de E-mail -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-indigo-500 bg-opacity-10">
                            <svg class="h-8 w-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-600 text-sm">Teste de E-mail</h2>
                            <p class="text-sm text-gray-500">Envie um e-mail de teste usando PHPMailer</p>
                            <div class="mt-2">
                                <a href="{{ route('admin.email-test.index') }}" class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Abrir</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Configuração Emissor Delphi -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-500 bg-opacity-10">
                            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 1.657-1.343 3-3 3S6 12.657 6 11s1.343-3 3-3 3 1.343 3 3z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11V7m0 4l3 3m-3-3l-3 3"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-600 text-sm">Emissor Delphi</h2>
                            <p class="text-sm text-gray-500">Configure URL, token e ambiente</p>
                            <div class="mt-2">
                                <a href="{{ route('admin.delphi-config') }}" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Configurar</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Diagnóstico Emissor (Dev/Admin) -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-emerald-500 bg-opacity-10">
                            <svg class="h-8 w-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 8h10M5 6h14v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-600 text-sm">Diagnóstico do Emissor</h2>
                            <p class="text-sm text-gray-500">Verifica se o emissor responde ao /api/status</p>
                            <div class="mt-2">
                                <form action="{{ route('admin.emitter-healthcheck') }}" method="POST">
                                    @csrf
                                    <button class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700">Verificar agora</button>
                                </form>
                                @if(session('emitter_status'))
                                    <div class="mt-2 text-xs text-gray-600">
                                        <div><strong>URL:</strong> {{ session('emitter_status.url') }}</div>
                                        <div><strong>HTTP:</strong> {{ session('emitter_status.http') }} — <strong>OK:</strong> {{ session('emitter_status.ok') ? 'sim' : 'não' }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Parceiros -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-500 bg-opacity-10">
                            <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-600 text-sm">Parceiros</h2>
                            <p class="text-sm text-gray-500">Gerenciar contabilidades parceiras</p>
                            <div class="mt-2">
                                <a href="{{ route('admin.partners.index') }}" class="inline-flex items-center px-3 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">Abrir</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
