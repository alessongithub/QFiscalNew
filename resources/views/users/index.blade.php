<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gerenciamento de Usuários</h2>
            <div class="flex items-center space-x-4">
                <div class="bg-blue-50 px-3 py-1 rounded-full">
                    <span class="text-sm font-medium text-blue-700">{{ $currentUsers }} / {{ $maxUsers === -1 ? '∞' : $maxUsers }} usuários</span>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-3 gap-6">
                
                <!-- Formulário de Cadastro -->
                <div class="lg:col-span-1">
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="flex items-center mb-4">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900">Cadastrar Novo Usuário</h3>
                        </div>
                        
                        <form method="POST" action="{{ route('users.store') }}" class="space-y-4" x-data='{
                            roles: @json($rolesPayload),
                            allPermissions: @json($permissionsPayload),
                            selectedRoleId: "",
                            selectedPermissions: [],
                            init(){},
                            onRoleChange(){
                                const role = this.roles.find(r=> String(r.id) === String(this.selectedRoleId));
                                if (role) {
                                    this.selectedPermissions = [...role.permissions];
                                } else {
                                    this.selectedPermissions = [];
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
                            @csrf
                            
                            <!-- Informações Básicas -->
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                                    <input name="name" type="text" 
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                           placeholder="Digite o nome completo" required />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                                    <input name="email" type="email" 
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                           placeholder="usuario@exemplo.com" required />
                                </div>
                                <div x-data="{ show: false }">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                                    <div class="relative">
                                        <input :type="show ? 'text' : 'password'" name="password" 
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                               placeholder="Senha segura" required />
                                        <button type="button" @click="show=!show" 
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path x-show="!show" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path x-show="!show" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                <path x-show="show" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Papel</label>
                                    <select name="role_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                            x-model="selectedRoleId" @change="onRoleChange()">
                                        <option value="">— Selecionar Papel —</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Permissões Extras -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Permissões Extras</label>
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 max-h-40 overflow-y-auto">
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
                                            <div class="mb-3">
                                                <h4 class="text-xs font-medium text-gray-600 uppercase tracking-wide mb-1">{{ $groupName }}</h4>
                                                <div class="space-y-1">
                                                    @foreach($groupPermissions as $perm)
                                                        <label class="flex items-center space-x-2 text-xs hover:bg-white p-1 rounded cursor-pointer">
                                                            <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                                                   :checked="selectedPermissions.includes({{ $perm->id }})"
                                                                   @change="togglePerm({{ $perm->id }})"
                                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                            />
                                                            <span class="text-gray-700">{{ $perm->name }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                <p class="text-xs text-gray-500 mt-2">💡 Dica: Escolha um papel e adicione apenas permissões extras necessárias.</p>
                            </div>

                            <!-- Botão de Salvar -->
                            <div class="pt-4">
                                <button type="submit" 
                                        class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Criar Usuário
                                </button>
                            </div>

                            <!-- Mantém sincronizado os checks com o POST independente do Alpine -->
                            <template x-for="pid in selectedPermissions" :key="pid">
                                <input type="hidden" name="permissions[]" :value="pid" />
                            </template>
                        </form>
                    </div>
                </div>

                <!-- Lista de Usuários -->
                <div class="lg:col-span-2">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Usuários Cadastrados</h3>
                                <div class="text-sm text-gray-500">{{ $users->total() }} usuário(s)</div>
                            </div>
                        </div>
                        
                        <div class="divide-y divide-gray-200">
                            @forelse($users as $user)
                                <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <!-- Avatar -->
                                            <div class="flex-shrink-0">
                                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <span class="text-blue-600 font-medium text-sm">
                                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <!-- Informações do Usuário -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center space-x-2">
                                                    <h4 class="text-sm font-medium text-gray-900 truncate">{{ $user->name }}</h4>
                                                    @if($user->is_admin)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                            Admin
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="text-sm text-gray-500 truncate">{{ $user->email }}</p>
                                                <div class="flex items-center space-x-2 mt-1">
                                                    @if($user->roles->count() > 0)
                                                        @foreach($user->roles as $role)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                                {{ $role->name }}
                                                            </span>
                                                        @endforeach
                                                    @else
                                                        <span class="text-xs text-gray-400">Sem papel definido</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Ações -->
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('users.edit', $user) }}" 
                                               class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Editar
                                            </a>
                                            <form action="{{ route('users.destroy', $user) }}" method="POST" 
                                                  onsubmit="return confirm('Tem certeza que deseja excluir o usuário {{ $user->name }}? Esta ação não pode ser desfeita.')" 
                                                  class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" 
                                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                    Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum usuário encontrado</h3>
                                    <p class="mt-1 text-sm text-gray-500">Comece criando o primeiro usuário do sistema.</p>
                                </div>
                            @endforelse
                        </div>
                        
                        @if($users->hasPages())
                            <div class="px-6 py-3 border-t border-gray-200">
                                {{ $users->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>