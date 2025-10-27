<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Criar planos primeiro
        $this->call(PlanSeeder::class);
        // Permissões e papéis
        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);
        // Gateway Mercado Pago (sandbox defaults)
        $this->call(GatewayConfigSeeder::class);
        // Regras NCM → GTIN (MVP)
        $this->call(NcmRuleSeeder::class);
    }
}
