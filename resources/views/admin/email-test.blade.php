<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Teste de E-mail</h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 space-y-6">
            @if(session('success'))
                <div class="p-3 rounded bg-green-100 text-green-800">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="p-3 rounded bg-red-100 text-red-800">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded border p-4">
                <p class="text-sm text-gray-600 mb-2">Você pode usar a configuração SMTP ativa ou informar uma configuração temporária apenas para este envio.</p>
                <div class="text-sm mb-2">
                    <span class="font-medium">SMTP ativo:</span>
                    @if($smtpConfig)
                        <span class="text-green-700">{{ $smtpConfig->host }}:{{ $smtpConfig->port }} ({{ strtoupper($smtpConfig->encryption) }})</span>
                    @else
                        <span class="text-red-700">Nenhuma configuração ativa. Use os campos abaixo ou configure em <a href="{{ route('admin.smtp-settings') }}" class="underline">Configurações SMTP</a>.</span>
                    @endif
                </div>
                <a href="{{ route('admin.smtp-settings') }}" class="text-indigo-600 underline">Abrir Configurações SMTP</a>
            </div>

            <form action="{{ route('admin.email-test.send') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="to" :value="__('Enviar para')" />
                        <x-text-input id="to" name="to" type="email" class="mt-1 block w-full" value="{{ old('to') }}" placeholder="destinatario@exemplo.com" required />
                    </div>
                    <div>
                        <x-input-label for="template" :value="__('Template')" />
                        <select id="template" name="template" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">Selecionar...</option>
                            @foreach($templates as $key => $tpl)
                                <option value="{{ $key }}" @selected(old('template')===$key)>{{ $tpl['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="subject" :value="__('Assunto')" />
                        <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" value="{{ old('subject') }}" placeholder="Assunto do e-mail" required />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="body" :value="__('Mensagem (HTML)')" />
                        <textarea id="body" name="body" rows="8" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Escreva sua mensagem ou deixe em branco para usar o template selecionado">{{ old('body') }}</textarea>
                    </div>
                </div>

                <details class="border rounded-md">
                    <summary class="cursor-pointer px-4 py-2 font-medium">Configuração SMTP temporária (opcional)</summary>
                    <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="smtp_host" :value="__('Host')" />
                            <x-text-input id="smtp_host" name="smtp[host]" type="text" class="mt-1 block w-full" value="{{ old('smtp.host') }}" placeholder="smtp.gmail.com" />
                        </div>
                        <div>
                            <x-input-label for="smtp_port" :value="__('Porta')" />
                            <x-text-input id="smtp_port" name="smtp[port]" type="number" class="mt-1 block w-full" value="{{ old('smtp.port') }}" placeholder="587" />
                        </div>
                        <div>
                            <x-input-label for="smtp_username" :value="__('Usuário')" />
                            <x-text-input id="smtp_username" name="smtp[username]" type="text" class="mt-1 block w-full" value="{{ old('smtp.username') }}" placeholder="seu-email@exemplo.com" />
                        </div>
                        <div>
                            <x-input-label for="smtp_password" :value="__('Senha')" />
                            <x-text-input id="smtp_password" name="smtp[password]" type="password" class="mt-1 block w-full" value="{{ old('smtp.password') }}" />
                        </div>
                        <div>
                            <x-input-label for="smtp_encryption" :value="__('Criptografia')" />
                            <select id="smtp_encryption" name="smtp[encryption]" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="tls" @selected(old('smtp.encryption')==='tls')>TLS</option>
                                <option value="ssl" @selected(old('smtp.encryption')==='ssl')>SSL</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="smtp_from_address" :value="__('E-mail de envio (From)')" />
                            <x-text-input id="smtp_from_address" name="smtp[from_address]" type="email" class="mt-1 block w-full" value="{{ old('smtp.from_address') }}" />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="smtp_from_name" :value="__('Nome de exibição (From Name)')" />
                            <x-text-input id="smtp_from_name" name="smtp[from_name]" type="text" class="mt-1 block w-full" value="{{ old('smtp.from_name') }}" />
                        </div>
                    </div>
                </details>

                <div class="flex items-center justify-end">
                    <x-primary-button>Enviar e-mail</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>


