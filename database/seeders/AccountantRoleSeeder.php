<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Permission;

class AccountantRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar role "Contador"
        $accountantRole = Role::updateOrCreate(
            ['slug' => 'accountant'],
            [
                'name' => 'Contador',
                'description' => 'Usuário responsável por gestão fiscal e tributária',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Buscar permissões relacionadas a contabilidade/fiscal
        $accountantPermissions = Permission::whereIn('slug', [
            'tax_config.view',
            'tax_config.edit',
            'tax_rates.view',
            'tax_rates.create',
            'tax_rates.edit',
            'tax_rates.delete',
            'settings.view', // Para visualizar configurações gerais
            'nfe.view', // Para acessar XMLs das notas fiscais
        ])->get();

        // Associar permissões ao role Contador
        foreach ($accountantPermissions as $permission) {
            DB::table('permission_role')->updateOrInsert(
                [
                    'permission_id' => $permission->id,
                    'role_id' => $accountantRole->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info("Role 'Contador' criada com sucesso!");
        $this->command->info("Permissões associadas: " . $accountantPermissions->pluck('slug')->join(', '));
    }
}

