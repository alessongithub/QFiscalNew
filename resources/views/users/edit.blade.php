<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Usuário</h2>
            <a href="{{ route('users.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6" x-data='{
                    roles: @json($rolesPayload),
                    allPermissions: @json($permissionsPayload),
                    selectedRoleId: "{{ $user->roles->first()?->id }}",
                    selectedPermissions: @json($user->permissions->pluck("id")),
                    onRoleChange(){
                        const role = this.roles.find(r=> String(r.id) === String(this.selectedRoleId));
                        if (role) {
                            this.selectedPermissions = Array.from(new Set([...this.selectedPermissions, ...role.permissions]));
                        }
                    },
                    togglePerm(pid){
                        pid = Number(pid);
                        if (this.selectedPermissions.includes(pid)) {
                            this.selectedPermissions = this.selectedPermissions.filter(id=> id!==pid);
                        } else {
                            this.selectedPermissions = [...this.selectedPermissions, pid];
                        }
                    }
                }'>
                    @csrf @method('PUT')
                    
                    <!-- Informações Básicas -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informações Básicas</h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome</label>
                                <input name="name" type="text" value="{{ old('name', $user->name) }}" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                       required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">E-mail</label>
                                <input name="email" type="email" value="{{ old('email', $user->email) }}" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                       required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
                                <input name="password" type="password" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                       placeholder="Deixe em branco para manter a senha atual" />
                                <p class="text-xs text-gray-500 mt-1">Deixe em branco para manter a senha atual</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Papel</label>
                                <select name="role_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                        x-model="selectedRoleId" @change="onRoleChange()">
                                    <option value="">— Selecionar Papel —</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ $user->roles->contains('id', $role->id) ? 'selected' : '' }}>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Permissões -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Permissões</h3>
                        <p class="text-sm text-gray-600 mb-4">Selecione as permissões específicas para este usuário. As permissões do papel selecionado serão aplicadas automaticamente.</p>
                        
                        <div class="grid md:grid-cols-2 gap-4 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-4 bg-white">
                            @php
                                $permissionGroups = [
                                    'Clientes' => $permissions->filter(fn($p) => str_starts_with($p->slug, 'clients')),
                                    'Usuários' => $permissions->filter(fn($p) => str_starts_with($p->slug, 'users')),
                                    'Produtos' => $permissions->filter(fn($p) => str_starts_with($p->slug, 'products')),
                                    'Estoque' => $permissions->filter(fn($p) => str_starts_with($p->slug, 'stock')),
                                    'Orçamentos' => $permissions->filter(fn($p) => str_starts_with($p->slug, 'quotes')),
                                    'Pedidos' => $permissions->filter(fn($p) => str_starts_with($p->slug, 'orders')),
                                    'Financeiro' => $permissions->filter(fn($p) => str_starts_with($p->slug, 'receivables') || str_starts_with($p->slug, 'payables')),
                                    'OS' => $permissions->filter(fn($p) => str_starts_with($p->slug, 'service_orders')),
                                    'Outros' => $permissions->filter(fn($p) => !str_starts_with($p->slug, 'clients') && !str_starts_with($p->slug, 'users') && !str_starts_with($p->slug, 'products') && !str_starts_with($p->slug, 'stock') && !str_starts_with($p->slug, 'quotes') && !str_starts_with($p->slug, 'orders') && !str_starts_with($p->slug, 'receivables') && !str_starts_with($p->slug, 'payables') && !str_starts_with($p->slug, 'service_orders'))
                                ];
                            @endphp
                            
                            @foreach($permissionGroups as $groupName => $groupPermissions)
                                @if($groupPermissions->count() > 0)
                                    <div class="space-y-2">
                                        <h4 class="font-medium text-gray-800 text-sm border-b border-gray-200 pb-1">{{ $groupName }}</h4>
                                        @foreach($groupPermissions as $perm)
                                            <label class="flex items-center space-x-2 text-sm hover:bg-gray-50 p-1 rounded cursor-pointer">
                                                <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                                       :checked="selectedPermissions.includes({{ $perm->id }})"
                                                       @change="togglePerm({{ $perm->id }})"
                                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                />
                                                <span class="text-gray-700">{{ $perm->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <!-- Ações -->
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('users.index') }}" 
                           class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition duration-150 ease-in-out">
                            Cancelar
                        </a>
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Salvar Alterações
                        </button>
                    </div>

                    <!-- Mantém sincronizado os checks com o POST independente do Alpine -->
                    <template x-for="pid in selectedPermissions" :key="pid">
                        <input type="hidden" name="permissions[]" :value="pid" />
                    </template>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>