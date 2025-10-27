<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompletePlanSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Executar seeders existentes
        $this->call([
            PlanPermissionsSeeder::class,
            PlanConfigurationsSeeder::class,
        ]);

        // Criar assinaturas para tenants existentes
        $this->createSubscriptionsForExistingTenants();
    }

    private function createSubscriptionsForExistingTenants()
    {
        // Buscar todos os tenants que não têm assinatura
        $tenantsWithoutSubscription = DB::table('tenants')
            ->leftJoin('subscriptions', 'tenants.id', '=', 'subscriptions.tenant_id')
            ->whereNull('subscriptions.id')
            ->select('tenants.id', 'tenants.plan_id')
            ->get();

        foreach ($tenantsWithoutSubscription as $tenant) {
            // Usar o plan_id do tenant ou padrão para plano gratuito
            $planId = $tenant->plan_id ?: 1;

            DB::table('subscriptions')->insert([
                'tenant_id' => $tenant->id,
                'plan_id' => $planId,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
