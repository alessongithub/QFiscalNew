<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QFiscal - ERP e Emissor Fiscal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --evoque-orange: #ff6b00;
            --evoque-dark: #1a1a1a;
            --qfiscal-green: #059669;
        }
        .gradient-bg {
            background: linear-gradient(135deg, var(--evoque-orange) 0%, var(--evoque-dark) 100%);
        }
        .hero-bg {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(255, 107, 0, 0.15);
        }
        .feature-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .feature-card:hover {
            border-color: var(--evoque-orange);
            transform: translateY(-5px);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--qfiscal-green) 0%, var(--evoque-orange) 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--evoque-orange) 0%, var(--qfiscal-green) 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 0, 0.3);
        }
        .btn-secondary {
            border: 2px solid var(--evoque-orange);
            color: var(--evoque-orange);
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background: var(--evoque-orange);
            color: white;
            transform: translateY(-2px);
        }
        .btn-evoque {
            background: var(--evoque-orange);
            color: white;
            transition: all 0.3s ease;
        }
        .btn-evoque:hover {
            background: var(--evoque-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 0, 0.3);
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .glow {
            box-shadow: 0 0 20px rgba(255, 107, 0, 0.2);
        }
        .evoque-section {
            background: linear-gradient(135deg, var(--evoque-dark) 0%, #2d2d2d 100%);
            color: white;
        }
        .evoque-card {
            background: rgba(255, 107, 0, 0.1);
            border: 2px solid var(--evoque-orange);
            transition: all 0.3s ease;
        }
        .evoque-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 107, 0, 0.2);
        }
    </style>
</head>
<body class="bg-gray-50">
    @php
        $logoPath = file_exists(public_path('logo/logo_transp.png')) ? asset('logo/logo_transp.png') : asset('logo/logo.png');
    @endphp
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <img src="{{ $logoPath }}" alt="QFiscal" class="h-12 w-auto">
                    <span class="ml-3 text-2xl font-bold text-gray-900">QFiscal</span>
                </div>
                                 <div class="hidden md:flex space-x-8">
                     <a href="#planos" class="text-gray-600 hover:text-gray-900">QFiscal</a>
                     <a href="#certificados" class="text-gray-600 hover:text-gray-900">Certificados</a>
                     <a href="#contabilidade" class="text-gray-600 hover:text-gray-900">Contabilidade</a>
                     <a href="#recursos" class="text-gray-600 hover:text-gray-900">Recursos</a>
                     <a href="#contato" class="text-gray-600 hover:text-gray-900">Contato</a>
                     <a href="#parceiros" class="text-gray-600 hover:text-gray-900 font-semibold">Seja parceiro</a>
                     <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900 font-semibold flex items-center space-x-1">
                         <i class="fas fa-sign-in-alt text-sm"></i>
                         <span>Acessar Sistema</span>
                     </a>
                 </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero-bg text-white py-20 relative overflow-hidden">
        <!-- Elementos decorativos -->
        <div class="absolute top-0 left-0 w-full h-full">
            <div class="absolute top-20 left-10 w-20 h-20 bg-white bg-opacity-10 rounded-full"></div>
            <div class="absolute top-40 right-20 w-32 h-32 bg-white bg-opacity-5 rounded-full"></div>
            <div class="absolute bottom-20 left-1/4 w-16 h-16 bg-white bg-opacity-10 rounded-full"></div>
            <div class="absolute bottom-40 right-1/3 w-24 h-24 bg-white bg-opacity-5 rounded-full"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <div class="mb-8">
                <img src="{{ $logoPath }}" alt="QFiscal" class="w-32 h-auto mx-auto mb-6">
            </div>
            <h1 class="text-5xl md:text-6xl font-bold mb-6">
                ERP Completo + Emissor Fiscal
            </h1>
            <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
                Gerencie sua empresa de forma integrada com controle financeiro, estoque, clientes e emissão de documentos fiscais.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#planos" class="btn-primary text-white px-8 py-4 rounded-lg font-semibold text-lg">
                    Ver Planos
                </a>
                <a href="#demo" class="btn-secondary px-8 py-4 rounded-lg font-semibold text-lg">
                    Solicitar Demo
                </a>
            </div>
        </div>
    </section>

    <!-- Estatísticas Section -->
    <section class="py-16 bg-green-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div class="bg-white rounded-lg p-6 shadow-lg">
                    <div class="text-3xl font-bold text-green-600 mb-2">500+</div>
                    <div class="text-gray-600">Empresas Ativas</div>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-lg">
                    <div class="text-3xl font-bold text-green-600 mb-2">50k+</div>
                    <div class="text-gray-600">Notas Emitidas</div>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-lg">
                    <div class="text-3xl font-bold text-green-600 mb-2">99.9%</div>
                    <div class="text-gray-600">Uptime</div>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-lg">
                    <div class="text-3xl font-bold text-green-600 mb-2">24/7</div>
                    <div class="text-gray-600">Suporte</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recursos Section -->
    <section id="recursos" class="py-20 bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <div class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-medium mb-4">
                    <i class="fas fa-star mr-2"></i>
                    Recursos Exclusivos
                </div>
                <h2 class="text-4xl font-bold text-white mb-4">Recursos do QFiscal ERP</h2>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                    Sistema completo de gestão empresarial com controle total de vendas, finanças e operações.
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Ordens de Serviço -->
                <div class="text-center p-6 bg-gray-800 rounded-xl shadow-lg feature-card border border-gray-700 hover:border-blue-500">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 floating">
                        <i class="fas fa-tools text-2xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-white">Ordens de Serviço</h3>
                    <p class="text-gray-300">
                        Workflow completo com garantia, técnicos, equipamentos e impressão profissional. Controle de status e aprovações.
                    </p>
                </div>

                <!-- Gestão Financeira -->
                <div class="text-center p-6 bg-gray-800 rounded-xl shadow-lg feature-card border border-gray-700 hover:border-green-500">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 floating">
                        <i class="fas fa-chart-line text-2xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-white">Gestão Financeira</h3>
                    <p class="text-gray-300">
                        Contas a receber/pagar, recibos, sangrias de caixa e relatórios detalhados. Controle total do fluxo financeiro.
                    </p>
                </div>

                <!-- Pedidos e Orçamentos -->
                <div class="text-center p-6 bg-gray-800 rounded-xl shadow-lg feature-card border border-gray-700 hover:border-purple-500">
                    <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 floating">
                        <i class="fas fa-shopping-cart text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-white">Pedidos e Orçamentos</h3>
                    <p class="text-gray-300">
                        Conversão automática de orçamentos em pedidos, gestão de frete e controle de estoque integrado.
                    </p>
                </div>

                <!-- Multi-tenant -->
                <div class="text-center p-6 bg-gray-800 rounded-xl shadow-lg feature-card border border-gray-700 hover:border-teal-500">
                    <div class="bg-teal-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 floating">
                        <i class="fas fa-users text-2xl text-teal-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-white">Multi-tenant</h3>
                    <p class="text-gray-300">
                        Cada empresa tem seu ambiente isolado e seguro, com dados completamente separados e controle de acesso RBAC.
                    </p>
                </div>

                <!-- Controle de Acesso -->
                <div class="text-center p-6 bg-gray-800 rounded-xl shadow-lg feature-card border border-gray-700 hover:border-orange-500">
                    <div class="bg-orange-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 floating">
                        <i class="fas fa-shield-alt text-2xl text-orange-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-white">Controle de Acesso</h3>
                    <p class="text-gray-300">
                        Sistema RBAC com roles (Admin, Gestor, Operador, Técnico) e permissions granulares para cada funcionalidade.
                    </p>
                </div>

                <!-- Impressão Profissional -->
                <div class="text-center p-6 bg-gray-800 rounded-xl shadow-lg feature-card border border-gray-700 hover:border-red-500">
                    <div class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 floating">
                        <i class="fas fa-print text-2xl text-red-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-white">Impressão Profissional</h3>
                    <p class="text-gray-300">
                        Documentos com logo personalizada, layout profissional e CSS específico para impressão. OS, orçamentos e recibos.
                    </p>
                </div>

                <!-- Transportadoras -->
                <div class="text-center p-6 bg-gray-800 rounded-xl shadow-lg feature-card border border-gray-700 hover:border-indigo-500">
                    <div class="bg-indigo-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 floating">
                        <i class="fas fa-truck text-2xl text-indigo-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-white">Gestão de Frete</h3>
                    <p class="text-gray-300">
                        Cadastro de transportadoras, modalidades de frete (CIF/FOB) e integração com NF-e. Controle completo do frete.
                    </p>
                </div>

                <!-- Estoque -->
                <div class="text-center p-6 bg-gray-800 rounded-xl shadow-lg feature-card border border-gray-700 hover:border-yellow-500">
                    <div class="bg-yellow-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 floating">
                        <i class="fas fa-boxes text-2xl text-yellow-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-white">Controle de Estoque</h3>
                    <p class="text-gray-300">
                        Movimentação automática, baixa em pedidos finalizados e controle de saldos. Produtos e serviços integrados.
                    </p>
                </div>

                <!-- Filtros e Paginação -->
                <div class="text-center p-6 bg-gray-800 rounded-xl shadow-lg feature-card border border-gray-700 hover:border-pink-500">
                    <div class="bg-pink-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 floating">
                        <i class="fas fa-search text-2xl text-pink-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-white">Filtros Avançados</h3>
                    <p class="text-gray-300">
                        Busca, filtros por data/status/cliente, paginação configurável e ordenação em todos os módulos.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Planos Section -->
    <section id="planos" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium mb-4">
                    <i class="fas fa-crown mr-2"></i>
                    Planos Flexíveis
                </div>
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Escolha seu Plano</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Temos a solução ideal para o tamanho da sua empresa.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-{{ count($plans) }} gap-8">
                @foreach($plans as $index => $plan)
                    <div class="bg-white rounded-lg shadow-lg p-8 card-hover {{ $index == 1 ? 'border-2 border-blue-500 relative glow' : ($index == 2 ? 'border-2 border-green-500 relative glow' : '') }}">
                        @if($index == 1)
                            <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                                <span class="bg-blue-500 text-white px-4 py-2 rounded-full text-sm font-semibold">Mais Econômico</span>
                            </div>
                        @elseif($index == 2)
                            <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                                <span class="bg-green-500 text-white px-4 py-2 rounded-full text-sm font-semibold">Mais Popular</span>
                            </div>
                        @endif
                        
                        <div class="text-center mb-8">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
                            <div class="text-4xl font-bold text-green-600 mb-2">
                                R$ {{ number_format($plan->price, 2, ',', '.') }}
                            </div>
                            <p class="text-gray-600">
                                {{ $plan->price == 0 ? 'Para começar' : 'por mês' }}
                            </p>
                        </div>
                        
                        @php
                            $f = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []);
                            $displayFeatures = $f['display_features'] ?? [];
                        @endphp
                        <ul class="space-y-4 mb-8">
                            @forelse($displayFeatures as $text)
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    {{ $text }}
                                </li>
                            @empty
                                @if(isset($f['max_clients']))
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-3"></i>
                                        {{ $f['max_clients'] === -1 ? 'Clientes ilimitados' : 'Até ' . $f['max_clients'] . ' clientes' }}
                                    </li>
                                @endif
                                @if(isset($f['has_erp']) && $f['has_erp'])
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-3"></i>
                                        ERP completo
                                    </li>
                                @endif
                                @if(isset($f['has_emissor']) && $f['has_emissor'])
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-3"></i>
                                        Emissor fiscal
                                    </li>
                                @endif
                                @if(isset($f['has_api_access']) && $f['has_api_access'])
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-3"></i>
                                        API disponível
                                    </li>
                                @endif
                            @endforelse
                        </ul>
                        
                        <button onclick="selecionarPlano('{{ $plan->slug }}')" 
                                class="w-full {{ $index == 1 ? 'bg-blue-600 hover:bg-blue-700' : 'btn-primary' }} text-white py-3 rounded-lg font-semibold transition-all">
                            {{ $plan->price == 0 ? 'Começar Grátis' : 'Escolher ' . $plan->name }}
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Certificado Digital Section -->
    <!-- Parceiros Section -->
    <section id="parceiros" class="py-20 bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <div class="inline-flex items-center px-4 py-2 bg-purple-100 text-purple-800 rounded-full text-sm font-medium mb-4">
                    <i class="fas fa-handshake mr-2"></i>
                    Programa de Parceria para Contabilidades
                </div>
                <h2 class="text-4xl font-bold text-gray-900 mb-4">White-label para Contadores</h2>
                <p class="text-lg text-gray-600">Tenha seu ERP com sua marca, subdomínio próprio e comissão recorrente.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gray-50 p-6 rounded-lg border">
                    <h3 class="font-semibold text-gray-800 mb-2">Sua Marca</h3>
                    <p class="text-gray-600">Logo, cores e tema personalizados. Exibição de "Powered by QFiscal".</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg border">
                    <h3 class="font-semibold text-gray-800 mb-2">Subdomínio</h3>
                    <p class="text-gray-600">Acesso via <strong>sua-contabilidade.qfiscal.com.br</strong> (ou domínio dedicado).</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg border">
                    <h3 class="font-semibold text-gray-800 mb-2">Benefícios recorrentes</h3>
                    <p class="text-gray-600">Receba benefícios a cada assinatura ativa dos seus clientes.</p>
                </div>
            </div>
            <div class="bg-purple-50 p-6 rounded-lg border border-purple-200 mb-6">
                <h3 class="font-semibold text-purple-900 mb-2">Quem pode participar?</h3>
                <p class="text-purple-800">Exclusivo para empresas contábeis com CNPJ ativo e regular. Validamos inscrição no CRC quando aplicável.</p>
            </div>
            <div class="text-center">
                <a href="{{ route('partner.apply') }}" class="inline-flex items-center px-8 py-4 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold text-lg transition-all shadow-lg hover:shadow-xl">
                    <i class="fas fa-user-plus mr-2"></i>
                    Cadastrar minha Contabilidade
                </a>
            </div>
        </div>
    </section>
    <section id="certificados" class="bg-gray-50 py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium mb-4">
                    <i class="fas fa-certificate mr-2"></i>
                    Certificado Digital
                </div>
                <h2 class="text-4xl font-bold mb-4">Precisa de Certificado Digital?</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-8">
                    Emita seu certificado digital A1 ou A3 com a Evoque Assessoria. 
                    Processo 100% online, rápido e seguro.
                </p>
                <a href="https://certificados.evoqueassessoria.com.br" target="_blank" 
                   class="inline-flex items-center px-8 py-4 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold text-lg transition-all shadow-lg hover:shadow-xl">
                    <i class="fas fa-certificate mr-2"></i>
                    Solicitar Certificado Digital
                </a>
            </div>
        </div>
    </section>

    <!-- Contabilidade Digital Section -->
    <section id="contabilidade" class="py-20 bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-medium mb-4">
                    <i class="fas fa-calculator mr-2"></i>
                    Contabilidade Digital
                </div>
                <h2 class="text-4xl font-bold mb-4">Contabilidade + Marketing Digital</h2>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto mb-8">
                    Mais que contabilidade: impulsione seu negócio com nossa solução completa de gestão e marketing.
                    Ganhe uma landing page personalizada e suporte para vendas em marketplaces e redes sociais.
                </p>
                                 <div class="space-y-6 max-w-2xl mx-auto mb-8">
                     <div class="flex items-center text-left bg-gray-800 p-4 rounded-lg border border-gray-700">
                         <i class="fas fa-globe text-2xl text-blue-400 mr-4"></i>
                         <div>
                             <h3 class="font-semibold text-white">Landing Page Personalizada</h3>
                             <p class="text-gray-300">Site profissional para sua empresa se destacar online</p>
                         </div>
                     </div>
                     <div class="flex items-center text-left bg-gray-800 p-4 rounded-lg border border-gray-700">
                         <i class="fas fa-ad text-2xl text-blue-400 mr-4"></i>
                         <div>
                             <h3 class="font-semibold text-white">Marketing Digital</h3>
                             <p class="text-gray-300">Suporte com Google Ads, Meta Ads e Instagram Business</p>
                         </div>
                     </div>
                     <div class="flex items-center text-left bg-gray-800 p-4 rounded-lg border border-gray-700">
                         <i class="fas fa-store text-2xl text-blue-400 mr-4"></i>
                         <div>
                             <h3 class="font-semibold text-white">Marketplaces</h3>
                             <p class="text-gray-300">Ajuda para vender nos principais marketplaces</p>
                         </div>
                     </div>
                 </div>
                <a href="https://contabilidade.evoqueassessoria.com.br" target="_blank" 
                   class="inline-flex items-center px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-lg transition-all shadow-lg hover:shadow-xl">
                    <i class="fas fa-calculator mr-2"></i>
                    Conhecer Nossa Contabilidade Digital
                </a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-green-600 to-blue-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold mb-4">Pronto para começar?</h2>
            <p class="text-xl mb-8 max-w-2xl mx-auto">
                Junte-se a milhares de empresas que já confiam na Evoque Assessoria para gestão completa dos seus negócios.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#planos" class="bg-white text-green-600 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-gray-100 transition duration-300 hover:shadow-lg">
                    Ver Planos QFiscal
                </a>
                <a href="https://contabilidade.evoqueassessoria.com.br" target="_blank" class="bg-blue-800 text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-blue-900 transition duration-300 hover:shadow-lg">
                    Contabilidade Digital
                </a>
                <a href="https://certificados.evoqueassessoria.com.br" target="_blank" class="bg-green-700 text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-green-800 transition duration-300 hover:shadow-lg">
                    Certificado Digital
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <img src="{{ $logoPath }}" alt="QFiscal" class="h-8 w-auto mb-4">
                    <p class="text-gray-400">
                        Soluções completas em ERP e emissor fiscal para sua empresa.
                    </p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Produtos</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">ERP</a></li>
                        <li><a href="#" class="hover:text-white">Emissor Fiscal</a></li>
                        <li><a href="#" class="hover:text-white">Relatórios</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Suporte</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">Central de Ajuda</a></li>
                        <li><a href="#" class="hover:text-white">Documentação</a></li>
                        <li><a href="#" class="hover:text-white">Contato</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Empresa</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">Sobre nós</a></li>
                        <li><a href="#" class="hover:text-white">Blog</a></li>
                        <li><a href="#" class="hover:text-white">Carreiras</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 QFiscal. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Modal de Seleção de Plano -->
    <div id="modalPlano" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold mb-4">Selecionar Plano</h3>
            <p class="text-gray-600 mb-6">Você selecionou o plano: <span id="planoSelecionado" class="font-semibold"></span></p>
            <div class="flex space-x-4">
                <button onclick="fecharModal()" class="flex-1 bg-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-400">
                    Cancelar
                </button>
                <button onclick="irParaCadastro()" class="flex-1 btn-primary text-white py-2 rounded-lg">
                    Continuar
                </button>
            </div>
        </div>
    </div>

    <script>
        let planoSelecionado = '';

        function selecionarPlano(plano) {
            planoSelecionado = plano;
            const nomes = {
                'gratuito': 'Gratuito',
                'emissor-fiscal': 'Emissor Fiscal',
                'basico': 'Básico',
                'profissional': 'Profissional'
            };
            document.getElementById('planoSelecionado').textContent = nomes[plano] || plano;
            document.getElementById('modalPlano').classList.remove('hidden');
            document.getElementById('modalPlano').classList.add('flex');
        }

        function fecharModal() {
            document.getElementById('modalPlano').classList.add('hidden');
            document.getElementById('modalPlano').classList.remove('flex');
        }

        function irParaCadastro() {
            // Salvar o plano selecionado no localStorage
            localStorage.setItem('planoSelecionado', planoSelecionado);
            // Redirecionar para o cadastro no Laravel
            window.location.href = '/register';
        }

        // Fechar modal ao clicar fora
        document.getElementById('modalPlano').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });
    </script>
</body>
</html>
