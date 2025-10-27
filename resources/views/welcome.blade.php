<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>QFiscal ERP - Sistema de Gestão Empresarial</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
        @endif
        <style>
            .gradient-bg {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .hero-gradient {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            }
            .feature-card {
                transition: all 0.3s ease;
            }
            .feature-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            }
        </style>
    </head>
    <body class="bg-gray-50">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <h1 class="text-2xl font-bold text-gray-900">QFiscal ERP</h1>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                                Entrar
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                    Cadastrar
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero-gradient text-white py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <div class="mb-6">
                        <img src="{{ asset('logo-transparent.png') }}" alt="QFiscal ERP" class="h-24 md:h-32 mx-auto">
                    </div>
                    <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
                        Sistema completo de gestão empresarial para pequenas e médias empresas
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('register') }}" class="bg-white text-blue-600 px-8 py-3 rounded-lg text-lg font-semibold hover:bg-gray-100 transition-colors">
                            Começar Agora
                        </a>
                        <a href="#features" class="border-2 border-white text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors">
                            Conhecer Recursos
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-gray-900 mb-4">
                        Recursos Principais
                    </h2>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                        Tudo que sua empresa precisa para crescer e se organizar
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Ordens de Serviço -->
                    <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Ordens de Serviço</h3>
                        <p class="text-gray-600 mb-4">
                            Controle completo de ordens de serviço com workflow avançado, garantia, técnicos e equipamentos.
                        </p>
                        <ul class="text-sm text-gray-500 space-y-1">
                            <li>• Workflow de status completo</li>
                            <li>• Sistema de garantia</li>
                            <li>• Atribuição de técnicos</li>
                            <li>• Impressão profissional</li>
                        </ul>
                    </div>

                    <!-- Gestão Financeira -->
                    <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Gestão Financeira</h3>
                        <p class="text-gray-600 mb-4">
                            Controle total das finanças com contas a receber, pagar, recibos e caixa diário.
                        </p>
                        <ul class="text-sm text-gray-500 space-y-1">
                            <li>• Contas a receber/pagar</li>
                            <li>• Recibos e sangrias</li>
                            <li>• Caixa diário</li>
                            <li>• Relatórios detalhados</li>
                        </ul>
                    </div>

                    <!-- Pedidos e Orçamentos -->
                    <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Pedidos e Orçamentos</h3>
                        <p class="text-gray-600 mb-4">
                            Sistema completo de vendas com orçamentos, pedidos, frete e controle de estoque.
                        </p>
                        <ul class="text-sm text-gray-500 space-y-1">
                            <li>• Orçamentos profissionais</li>
                            <li>• Gestão de frete</li>
                            <li>• Controle de estoque</li>
                            <li>• Emissão de NF-e</li>
                        </ul>
                    </div>

                    <!-- Multi-tenant -->
                    <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Multi-tenant</h3>
                        <p class="text-gray-600 mb-4">
                            Arquitetura multi-tenant com isolamento completo de dados por empresa.
                        </p>
                        <ul class="text-sm text-gray-500 space-y-1">
                            <li>• Isolamento de dados</li>
                            <li>• Controle de acesso</li>
                            <li>• Personalização por tenant</li>
                            <li>• Escalabilidade</li>
                        </ul>
                    </div>

                    <!-- RBAC -->
                    <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Controle de Acesso</h3>
                        <p class="text-gray-600 mb-4">
                            Sistema RBAC avançado com roles e permissions granulares.
                        </p>
                        <ul class="text-sm text-gray-500 space-y-1">
                            <li>• Roles: Admin, Gestor, Operador</li>
                            <li>• Permissions granulares</li>
                            <li>• Controle por funcionalidade</li>
                            <li>• Segurança total</li>
                        </ul>
                    </div>

                    <!-- Impressão -->
                    <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Impressão Profissional</h3>
                        <p class="text-gray-600 mb-4">
                            Documentos impressos com logo da empresa e layout profissional.
                        </p>
                        <ul class="text-sm text-gray-500 space-y-1">
                            <li>• Logo personalizada</li>
                            <li>• Layout profissional</li>
                            <li>• CSS para impressão</li>
                            <li>• Múltiplos formatos</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Benefits Section -->
        <section class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-gray-900 mb-4">
                        Por que escolher o QFiscal ERP?
                    </h2>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                        Desenvolvido para atender às necessidades reais das empresas brasileiras
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Rápido</h3>
                        <p class="text-gray-600">Interface intuitiva e processos otimizados</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Confiável</h3>
                        <p class="text-gray-600">Sistema robusto e seguro para sua empresa</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Flexível</h3>
                        <p class="text-gray-600">Adaptável às necessidades do seu negócio</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Econômico</h3>
                        <p class="text-gray-600">Custo-benefício ideal para PMEs</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 bg-blue-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-4xl font-bold text-white mb-4">
                    Pronto para transformar sua empresa?
                </h2>
                <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
                    Comece agora mesmo e veja como o QFiscal ERP pode revolucionar a gestão do seu negócio
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('register') }}" class="bg-white text-blue-600 px-8 py-3 rounded-lg text-lg font-semibold hover:bg-gray-100 transition-colors">
                        Criar Conta Grátis
                    </a>
                    <a href="{{ route('login') }}" class="border-2 border-white text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors">
                        Fazer Login
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-xl font-bold mb-4">QFiscal ERP</h3>
                        <p class="text-gray-400">
                            Sistema completo de gestão empresarial para pequenas e médias empresas.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold mb-4">Produto</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="#" class="hover:text-white">Ordens de Serviço</a></li>
                            <li><a href="#" class="hover:text-white">Gestão Financeira</a></li>
                            <li><a href="#" class="hover:text-white">Pedidos e Orçamentos</a></li>
                            <li><a href="#" class="hover:text-white">Controle de Estoque</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold mb-4">Empresa</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="#" class="hover:text-white">Sobre Nós</a></li>
                            <li><a href="#" class="hover:text-white">Contato</a></li>
                            <li><a href="#" class="hover:text-white">Suporte</a></li>
                            <li><a href="#" class="hover:text-white">Blog</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold mb-4">Legal</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="#" class="hover:text-white">Termos de Uso</a></li>
                            <li><a href="#" class="hover:text-white">Política de Privacidade</a></li>
                            <li><a href="#" class="hover:text-white">Cookies</a></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                    <p>&copy; 2024 QFiscal ERP. Todos os direitos reservados.</p>
                </div>
            </div>
        </footer>
    </body>
</html>
