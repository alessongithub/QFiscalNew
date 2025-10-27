<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Inserir roles básicos
        $roles = [
            [
                'name' => 'Administrador',
                'slug' => 'admin',
                'description' => 'Acesso total ao sistema',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Usuário',
                'slug' => 'user',
                'description' => 'Usuário padrão do sistema',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cliente',
                'slug' => 'client',
                'description' => 'Cliente do sistema',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $role['slug']],
                $role
            );
        }

        // Inserir permissões básicas
        $permissions = [
            // Dashboard
            [
                'name' => 'Visualizar Dashboard',
                'slug' => 'dashboard.view',
                'description' => 'Permite visualizar o dashboard',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Clientes
            [
                'name' => 'Visualizar Clientes',
                'slug' => 'clients.view',
                'description' => 'Permite visualizar clientes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Criar Clientes',
                'slug' => 'clients.create',
                'description' => 'Permite criar clientes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Editar Clientes',
                'slug' => 'clients.edit',
                'description' => 'Permite editar clientes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Excluir Clientes',
                'slug' => 'clients.delete',
                'description' => 'Permite excluir clientes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Ordens de Serviço
            [
                'name' => 'Visualizar Ordens de Serviço',
                'slug' => 'service_orders.view',
                'description' => 'Permite visualizar ordens de serviço',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Criar Ordens de Serviço',
                'slug' => 'service_orders.create',
                'description' => 'Permite criar ordens de serviço',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Editar Ordens de Serviço',
                'slug' => 'service_orders.edit',
                'description' => 'Permite editar ordens de serviço',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Excluir Ordens de Serviço',
                'slug' => 'service_orders.delete',
                'description' => 'Permite excluir ordens de serviço',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Produtos
            [
                'name' => 'Visualizar Produtos',
                'slug' => 'products.view',
                'description' => 'Permite visualizar produtos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Criar Produtos',
                'slug' => 'products.create',
                'description' => 'Permite criar produtos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Editar Produtos',
                'slug' => 'products.edit',
                'description' => 'Permite editar produtos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Excluir Produtos',
                'slug' => 'products.delete',
                'description' => 'Permite excluir produtos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Orçamentos
            [
                'name' => 'Visualizar Orçamentos',
                'slug' => 'quotes.view',
                'description' => 'Permite visualizar orçamentos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Criar Orçamentos',
                'slug' => 'quotes.create',
                'description' => 'Permite criar orçamentos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Editar Orçamentos',
                'slug' => 'quotes.edit',
                'description' => 'Permite editar orçamentos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Excluir Orçamentos',
                'slug' => 'quotes.delete',
                'description' => 'Permite excluir orçamentos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Pedidos
            [
                'name' => 'Visualizar Pedidos',
                'slug' => 'orders.view',
                'description' => 'Permite visualizar pedidos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Criar Pedidos',
                'slug' => 'orders.create',
                'description' => 'Permite criar pedidos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Editar Pedidos',
                'slug' => 'orders.edit',
                'description' => 'Permite editar pedidos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Excluir Pedidos',
                'slug' => 'orders.delete',
                'description' => 'Permite excluir pedidos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Relatórios
            [
                'name' => 'Visualizar Relatórios',
                'slug' => 'reports.view',
                'description' => 'Permite visualizar relatórios',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Configurações
            [
                'name' => 'Visualizar Configurações',
                'slug' => 'settings.view',
                'description' => 'Permite visualizar configurações',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Editar Configurações',
                'slug' => 'settings.edit',
                'description' => 'Permite editar configurações',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Associar permissões aos roles
        $this->assignPermissionsToRoles();
    }

    private function assignPermissionsToRoles()
    {
        // Buscar IDs dos roles
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        $userRole = DB::table('roles')->where('slug', 'user')->first();
        $clientRole = DB::table('roles')->where('slug', 'client')->first();

        // Buscar todas as permissões
        $permissions = DB::table('permissions')->get();

        // Admin tem todas as permissões
        foreach ($permissions as $permission) {
            DB::table('permission_role')->updateOrInsert(
                [
                    'permission_id' => $permission->id,
                    'role_id' => $adminRole->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Usuário tem permissões básicas (exceto configurações)
        $userPermissions = $permissions->whereNotIn('slug', ['settings.view', 'settings.edit']);
        foreach ($userPermissions as $permission) {
            DB::table('permission_role')->updateOrInsert(
                [
                    'permission_id' => $permission->id,
                    'role_id' => $userRole->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Cliente tem permissões limitadas
        $clientPermissions = $permissions->whereIn('slug', [
            'dashboard.view',
            'service_orders.view',
            'quotes.view',
            'orders.view',
        ]);
        foreach ($clientPermissions as $permission) {
            DB::table('permission_role')->updateOrInsert(
                [
                    'permission_id' => $permission->id,
                    'role_id' => $clientRole->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
