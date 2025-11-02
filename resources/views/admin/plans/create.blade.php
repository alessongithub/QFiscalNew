<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Criar Novo Plano') }}
        </h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            <form method="POST" action="{{ route('admin.plans.store') }}" class="space-y-6">
                @csrf

                <!-- Informações Básicas -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informações Básicas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="name" :value="__('Nome do Plano')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="slug" :value="__('Slug (identificador único)')" />
                            <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full" :value="old('slug')" required />
                            <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="price" :value="__('Preço Mensal (R$)')" />
                            <x-text-input id="price" name="price" type="number" step="0.01" class="mt-1 block w-full" :value="old('price')" required />
                            <x-input-error :messages="$errors->get('price')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Descrição')" />
                            <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Limites de Recursos -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Limites de Recursos (-1 = Ilimitado)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <x-input-label for="max_clients" :value="__('Máximo de Clientes')" />
                            <x-text-input id="max_clients" name="max_clients" type="number" step="1" placeholder="50 ou -1" class="mt-1 block w-full" :value="old('max_clients', 50)" />
                            <x-input-error :messages="$errors->get('max_clients')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="max_users" :value="__('Máximo de Usuários')" />
                            <x-text-input id="max_users" name="max_users" type="number" step="1" placeholder="1 ou -1" class="mt-1 block w-full" :value="old('max_users', 1)" />
                            <x-input-error :messages="$errors->get('max_users')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="max_products" :value="__('Máximo de Produtos')" />
                            <x-text-input id="max_products" name="max_products" type="number" step="1" placeholder="100 ou -1" class="mt-1 block w-full" :value="old('max_products')" />
                            <x-input-error :messages="$errors->get('max_products')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Features de Acesso -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Features de Acesso</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <input id="has_erp" name="has_erp" type="checkbox" value="1" {{ old('has_erp', true) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="has_erp" class="ml-2 text-sm text-gray-900">Acesso ao ERP</label>
                            </div>
                            <div class="flex items-center">
                                <input id="has_emissor" name="has_emissor" type="checkbox" value="1" {{ old('has_emissor', false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="has_emissor" class="ml-2 text-sm text-gray-900">Acesso ao Emissor Fiscal</label>
                            </div>
                            <div class="flex items-center">
                                <input id="has_api_access" name="has_api_access" type="checkbox" value="1" {{ old('has_api_access', false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="has_api_access" class="ml-2 text-sm text-gray-900">Acesso à API</label>
                            </div>
                            <div class="flex items-center">
                                <input id="allow_issue_nfe" name="allow_issue_nfe" type="checkbox" value="1" {{ old('allow_issue_nfe', false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="allow_issue_nfe" class="ml-2 text-sm text-gray-900">Permitir Emissão de NF-e no ERP</label>
                            </div>
                            <div class="flex items-center">
                                <input id="allow_pos" name="allow_pos" type="checkbox" value="1" {{ old('allow_pos', false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="allow_pos" class="ml-2 text-sm text-gray-900">Acesso ao PDV (POS)</label>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="erp_access_level" :value="__('Nível de Acesso no ERP (para Plano Emissor Fiscal)')" />
                                <select id="erp_access_level" name="erp_access_level" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Nenhum</option>
                                    <option value="free" {{ old('erp_access_level') === 'free' ? 'selected' : '' }}>Free (Gratuito)</option>
                                    <option value="basic" {{ old('erp_access_level') === 'basic' ? 'selected' : '' }}>Básico</option>
                                    <option value="professional" {{ old('erp_access_level') === 'professional' ? 'selected' : '' }}>Profissional</option>
                                    <option value="enterprise" {{ old('erp_access_level') === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Usado quando o plano tem emissor mas ERP limitado</p>
                                <x-input-error :messages="$errors->get('erp_access_level')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="support_type" :value="__('Tipo de Suporte')" />
                                <select id="support_type" name="support_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @php $support = old('support_type', 'email'); @endphp
                                    <option value="email" {{ $support === 'email' ? 'selected' : '' }}>Email</option>
                                    <option value="priority" {{ $support === 'priority' ? 'selected' : '' }}>Prioritário</option>
                                    <option value="24/7" {{ $support === '24/7' ? 'selected' : '' }}>24/7</option>
                                </select>
                                <x-input-error :messages="$errors->get('support_type')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Limites de Armazenamento -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Limites de Armazenamento (-1 = Ilimitado)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="storage_data_mb" :value="__('Armazenamento de Dados (MB)')" />
                            <x-text-input id="storage_data_mb" name="storage_data_mb" type="number" step="1" placeholder="50 ou -1" class="mt-1 block w-full" :value="old('storage_data_mb', 50)" />
                            <p class="mt-1 text-xs text-gray-500">Espaço para dados estruturados (clientes, produtos, vendas)</p>
                            <x-input-error :messages="$errors->get('storage_data_mb')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="storage_files_mb" :value="__('Armazenamento de Arquivos (MB)')" />
                            <x-text-input id="storage_files_mb" name="storage_files_mb" type="number" step="1" placeholder="500 ou -1" class="mt-1 block w-full" :value="old('storage_files_mb', 500)" />
                            <p class="mt-1 text-xs text-gray-500">Espaço para arquivos (XMLs NF-e, imagens, PDFs)</p>
                            <x-input-error :messages="$errors->get('storage_files_mb')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="additional_data_price" :value="__('Preço por 50MB Adicional de Dados (R$)')" />
                            <x-text-input id="additional_data_price" name="additional_data_price" type="number" step="0.01" class="mt-1 block w-full" :value="old('additional_data_price', 9.90)" />
                            <x-input-error :messages="$errors->get('additional_data_price')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="additional_files_price" :value="__('Preço por 500MB Adicional de Arquivos (R$)')" />
                            <x-text-input id="additional_files_price" name="additional_files_price" type="number" step="0.01" class="mt-1 block w-full" :value="old('additional_files_price', 9.90)" />
                            <x-input-error :messages="$errors->get('additional_files_price')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Recursos para Exibição -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recursos para Exibição na Landing</h3>
                    <div>
                        <x-input-label for="display_features_text" :value="__('Lista de recursos (um por linha)')" />
                        <textarea id="display_features_text" name="display_features_text" rows="6" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Exemplo:&#10;Até 50 clientes&#10;ERP básico&#10;Suporte por email">{{ old('display_features_text') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Um recurso por linha, será exibido na página de planos</p>
                        <x-input-error :messages="$errors->get('display_features_text')" class="mt-2" />
                    </div>
                </div>

                <!-- Status -->
                <div class="pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Status do Plano</h3>
                    <div class="flex items-center">
                        <input id="active" name="active" type="checkbox" value="1" {{ old('active', true) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <label for="active" class="ml-2 text-sm text-gray-900">
                            Plano Ativo (plano disponível para contratação)
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6 space-x-3">
                    <a href="{{ route('admin.plans') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Cancelar
                    </a>
                    <x-primary-button>
                        {{ __('Criar Plano') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
