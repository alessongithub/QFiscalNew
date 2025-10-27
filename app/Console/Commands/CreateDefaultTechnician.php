<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateDefaultTechnician extends Command
{
    protected $signature = 'tenant:create-default-technician {tenant_id?} {--email=} {--name=Tecnico Padrao} {--password=}';
    protected $description = 'Cria (ou garante) um usuário Técnico Padrão para um tenant e atribui o papel technician';

    public function handle(): int
    {
        $tenantId = $this->argument('tenant_id');
        if (!$tenantId) {
            /** @var Tenant|null $tenant */
            $tenant = Tenant::query()->orderBy('id')->first();
        } else {
            $tenant = Tenant::find($tenantId);
        }

        if (!$tenant) {
            $this->error('Tenant não encontrado. Informe tenant_id.');
            return self::FAILURE;
        }

        $email = $this->option('email');
        if (!$email) {
            // gera um email padrão baseado no tenant
            $slug = Str::slug($tenant->name ?: ('tenant-'.$tenant->id));
            $email = 'tecnico@'.$slug.'.local';
        }

        $name = (string) $this->option('name');
        $passwordPlain = $this->option('password') ?: Str::random(10);

        /** @var User $user */
        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($passwordPlain),
                'tenant_id' => $tenant->id,
                'is_admin' => false,
            ]);
            $this->info('Usuário técnico criado: '.$email);
        } else {
            // garantir tenant e senha se não tiver
            if (!$user->tenant_id) {
                $user->tenant_id = $tenant->id;
            }
            if ($this->option('password')) {
                $user->password = Hash::make($passwordPlain);
            }
            $user->save();
            $this->info('Usuário já existia, garantido no tenant: '.$email);
        }

        $role = Role::where('slug', 'technician')->first();
        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        $this->line('Credenciais do Técnico Padrão:');
        $this->line('Email: '.$email);
        $this->line('Senha: '.($this->option('password') ? $passwordPlain : '(gerada aleatória na criação; informe --password para definir)'));
        $this->info('Papel "technician" atribuído (se disponível).');

        return self::SUCCESS;
    }
}


