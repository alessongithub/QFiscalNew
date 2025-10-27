<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Associar todos os usuários existentes ao role de "Usuário" (plano gratuito)
        $this->assignUsersToDefaultRole();
    }

    private function assignUsersToDefaultRole()
    {
        // Buscar o role de "Usuário" (ID 2)
        $userRole = DB::table('roles')->where('slug', 'user')->first();
        
        if (!$userRole) {
            $this->command->error('Role "user" não encontrado!');
            return;
        }

        // Buscar todos os usuários que não têm role associado
        $usersWithoutRole = DB::table('users')
            ->leftJoin('role_user', 'users.id', '=', 'role_user.user_id')
            ->whereNull('role_user.user_id')
            ->select('users.id')
            ->get();

        foreach ($usersWithoutRole as $user) {
            DB::table('role_user')->insert([
                'user_id' => $user->id,
                'role_id' => $userRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->command->info("Usuário ID {$user->id} associado ao role 'Usuário'");
        }

        $this->command->info("Total de usuários associados: " . $usersWithoutRole->count());
    }
}
