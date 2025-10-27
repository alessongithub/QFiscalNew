<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parceria para Contabilidades - QFiscal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex items-center justify-between">
            <div class="flex items-center">
                <img src="{{ asset('logo/logo_transp.png') }}" class="h-10 w-auto" alt="QFiscal">
                <span class="ml-3 text-2xl font-bold text-gray-900">QFiscal</span>
            </div>
            <a href="/" class="text-gray-600 hover:text-gray-900">Voltar para o site</a>
        </div>
    </header>

    <section class="bg-gradient-to-r from-green-600 to-green-700 text-white py-14">
        <div class="max-w-4xl mx-auto px-4">
            <h1 class="text-4xl font-bold mb-3">Programa de Parceria para Contabilidades</h1>
            <p class="text-lg text-green-100">White-label: subdomínio, sua marca e benefícios recorrentes por cliente ativo. Exclusivo para empresas contábeis com CNPJ.</p>
        </div>
    </section>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 text-red-800 rounded">{{ $errors->first() }}</div>
        @endif

        <div class="bg-white rounded shadow p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Como funciona</h2>
            <ul class="list-disc pl-6 text-gray-700 space-y-2">
                <li>Sua contabilidade recebe um subdomínio: <em>sua-contabilidade.qfiscal.com.br</em>.</li>
                <li>Aplicamos sua marca (logo, cores e tema) com selo “Powered by QFiscal”.</li>
                <li>Seus clientes contratam planos pelo seu subdomínio e recebem suporte padrão.</li>
                <li>Você recebe benefícios recorrentes por cada assinatura ativa.</li>
                <li>Pré-requisito: CNPJ ativo (CRC quando aplicável).</li>
            </ul>
        </div>

        <div class="bg-white rounded shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Inscreva sua Contabilidade</h2>
            <form method="POST" action="{{ route('partner.apply.submit') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="block text-xs text-gray-600">Nome da Contabilidade</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded p-2" required>
                </div>
                <div>
                    <label class="block text-xs text-gray-600">Slug desejado (subdomínio)</label>
                    <input type="text" name="slug" value="{{ old('slug') }}" class="w-full border rounded p-2" required placeholder="ex.: contabx">
                </div>
                <div>
                    <label class="block text-xs text-gray-600">CNPJ</label>
                    <input id="cnpj" type="text" name="cnpj" value="{{ old('cnpj') }}" class="w-full border rounded p-2" required>
                </div>
                <div>
                    <label class="block text-xs text-gray-600">CRC (opcional)</label>
                    <input type="text" name="crc" value="{{ old('crc') }}" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-xs text-gray-600">Contato (responsável)</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name') }}" class="w-full border rounded p-2" required>
                </div>
                <div>
                    <label class="block text-xs text-gray-600">E-mail</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email') }}" class="w-full border rounded p-2" required>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-600">WhatsApp (com DDD)</label>
                    <input id="contact_phone" type="text" name="contact_phone" value="{{ old('contact_phone') }}" class="w-full border rounded p-2" required>
                </div>
                <div class="md:col-span-2 flex gap-3 pt-2">
                    <button class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded">Enviar inscrição</button>
                    <a href="/" class="px-5 py-2 border rounded">Cancelar</a>
                </div>
            </form>
        </div>
    </main>

    <footer class="bg-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-500">
            <div>© {{ date('Y') }} QFiscal — Powered by QFiscal</div>
        </div>
    </footer>
    <script>
    // Máscara simples de CNPJ e WhatsApp
    function maskCNPJ(v){
        v = v.replace(/\D/g, '');
        v = v.replace(/(\d{2})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1/$2');
        v = v.replace(/(\d{4})(\d)/, '$1-$2');
        return v;
    }
    function maskPhone(v){
        v = v.replace(/\D/g, '');
        if (v.length > 11) v = v.slice(0,11);
        if (v.length > 10) {
            return v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        }
        if (v.length > 6) {
            return v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        }
        if (v.length > 2) {
            return v.replace(/(\d{2})(\d{0,5})/, '($1) $2');
        }
        return v;
    }
    const cnpjInput = document.getElementById('cnpj');
    const phoneInput = document.getElementById('contact_phone');
    if (cnpjInput){ cnpjInput.addEventListener('input', (e)=>{ e.target.value = maskCNPJ(e.target.value); }); }
    if (phoneInput){ phoneInput.addEventListener('input', (e)=>{ e.target.value = maskPhone(e.target.value); }); }
    </script>
</body>
</html>


