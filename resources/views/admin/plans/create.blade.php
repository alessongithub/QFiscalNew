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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nome -->
                    <div>
                        <x-input-label for="name" :value="__('Nome do Plano')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <!-- Slug -->
                    <div>
                        <x-input-label for="slug" :value="__('Slug')" />
                        <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full" :value="old('slug')" required />
                        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                    </div>

                    <!-- Preço -->
                    <div>
                        <x-input-label for="price" :value="__('Preço (R$)')" />
                        <x-text-input id="price" name="price" type="number" step="0.01" class="mt-1 block w-full" :value="old('price')" required />
                        <x-input-error :messages="$errors->get('price')" class="mt-2" />
                    </div>

                    <!-- Limites e Capacidades (Features estruturadas) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:col-span-2">
                        <div>
                            <x-input-label for="max_clients" :value="__('Máximo de Clientes (-1 = ilimitado)')" />
                            <x-text-input id="max_clients" name="max_clients" type="number" class="mt-1 block w-full" :value="old('max_clients', 50)" />
                            <x-input-error :messages="$errors->get('max_clients')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="max_users" :value="__('Máximo de Usuários (-1 = ilimitado)')" />
                            <x-text-input id="max_users" name="max_users" type="number" class="mt-1 block w-full" :value="old('max_users', 1)" />
                            <x-input-error :messages="$errors->get('max_users')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="max_products" :value="__('Máximo de Produtos (-1 = ilimitado)')" />
                            <x-text-input id="max_products" name="max_products" type="number" class="mt-1 block w-full" :value="old('max_products')" />
                            <x-input-error :messages="$errors->get('max_products')" class="mt-2" />
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center">
                                <input id="has_api_access" name="has_api_access" type="checkbox" value="1" {{ old('has_api_access', false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="has_api_access" class="ml-2 text-sm text-gray-900">Acesso à API</label>
                            </div>
                            <div class="flex items-center">
                                <input id="has_emissor" name="has_emissor" type="checkbox" value="1" {{ old('has_emissor', false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="has_emissor" class="ml-2 text-sm text-gray-900">Emissor Fiscal</label>
                            </div>
                            <div class="flex items-center">
                                <input id="has_erp" name="has_erp" type="checkbox" value="1" {{ old('has_erp', true) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="has_erp" class="ml-2 text-sm text-gray-900">ERP</label>
                            </div>
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
                        <div class="md:col-span-2">
                            <x-input-label for="display_features_text" :value="__('Recursos para exibição na landing (um por linha)')" />
                            <textarea id="display_features_text" name="display_features_text" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('display_features_text') }}</textarea>
                            <x-input-error :messages="$errors->get('display_features_text')" class="mt-2" />
                        </div>
                    </div>

                    <!-- Descrição -->
                    <div class="col-span-2">
                        <x-input-label for="description" :value="__('Descrição')" />
                        <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                </div>

                <!-- Status -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">Status do Plano</h3>
                    
                    <div class="flex items-center">
                        <input id="active" name="active" type="checkbox" value="1" {{ old('active', true) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <label for="active" class="ml-2 text-sm text-gray-900">
                            Plano Ativo
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
