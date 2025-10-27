<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

class GrantAdmin extends Command
{
    protected $signature = 'tenant:grant-admin {email} {--revoke}';
    protected $description = 'Concede (ou revoga com --revoke) privilégios de admin ao usuário (is_admin e papel admin)';

    public function handle(): int
    {
        $email = $this->argument('email');
        /** @var User|null $user */
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error('Usuário não encontrado: ' . $email);
            return self::FAILURE;
        }

        $revoke = (bool) $this->option('revoke');
        $roleAdmin = Role::where('slug', 'admin')->first();

        if ($revoke) {
            $user->is_admin = false;
            $user->save();
            if ($roleAdmin) {
                $user->roles()->detach([$roleAdmin->id]);
            }
            $this->info('Admin revogado de: ' . $email);
            return self::SUCCESS;
        }

        $user->is_admin = true;
        $user->save();
        if ($roleAdmin) {
            $user->roles()->syncWithoutDetaching([$roleAdmin->id]);
        }
        $this->info('Admin concedido a: ' . $email);
        return self::SUCCESS;
    }
}


