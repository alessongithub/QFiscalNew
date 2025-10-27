<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run()
    {
        // Plano Emissor Fiscal (NOVO)
        Plan::updateOrCreate(['slug' => 'emissor'], [
            'name' => 'Emissor Fiscal',
            'slug' => 'emissor',
            'price' => 39.90,
            'features' => [
                'max_users' => 1,
                'max_clients' => 50,
                'max_products' => 50,
                'allow_issue_nfe' => false, // Não tem acesso à emissão pelo ERP
                'allow_pos' => false,
                'has_api_access' => false,
                'has_emissor' => true, // Tem acesso ao emissor Delphi
                'has_erp' => true, // Tem acesso ao ERP, mas em modo limitado
                'erp_access_level' => 'free', // Acesso equivalente ao plano gratuito no ERP
                'support_type' => 'email',
                'display_features' => [],
            ],
        ]);

        // Plano Gratuito
        Plan::updateOrCreate(['slug' => 'free'], [
            'name' => 'Plano Gratuito',
            'slug' => 'free',
            'price' => 0.00,
            'features' => [
                'max_users' => 1,
                'max_clients' => 50,
                'max_products' => 50,
                'allow_issue_nfe' => false,
                'allow_pos' => false,
                'has_api_access' => false,
                'has_emissor' => false, // Não tem acesso ao emissor Delphi
                'has_erp' => true, // Tem acesso ao ERP, mas limitado
                'support_type' => 'email',
                'display_features' => [],
            ],
        ]);

        // Plano Básico
        Plan::updateOrCreate(['slug' => 'basic'], [
            'name' => 'Plano Básico',
            'slug' => 'basic',
            'price' => 49.90,
            'features' => [
                'max_users' => 3,
                'max_clients' => 200,
                'max_products' => -1, // Ilimitado
                'allow_issue_nfe' => true,
                'allow_pos' => true,
                'has_api_access' => false,
                'has_emissor' => false, // Não tem acesso ao emissor Delphi
                'has_erp' => true, // Tem acesso ao ERP completo
                'support_type' => 'email',
                'display_features' => [],
            ],
        ]);

        // Plano Profissional
        Plan::updateOrCreate(['slug' => 'professional'], [
            'name' => 'Plano Profissional',
            'slug' => 'professional',
            'price' => 99.90,
            'features' => [
                'max_users' => 10,
                'max_clients' => 1000,
                'max_products' => -1, // Ilimitado
                'allow_issue_nfe' => true,
                'allow_pos' => true,
                'has_api_access' => true,
                'has_emissor' => true, // Tem acesso ao emissor Delphi
                'has_erp' => true, // Tem acesso ao ERP completo
                'support_type' => 'priority',
                'display_features' => [],
            ],
        ]);

        // Plano Enterprise
        Plan::updateOrCreate(['slug' => 'enterprise'], [
            'name' => 'Plano Enterprise',
            'slug' => 'enterprise',
            'price' => 199.90,
            'features' => [
                'max_users' => -1, // ilimitado
                'max_clients' => -1, // ilimitado
                'max_products' => -1, // Ilimitado
                'allow_issue_nfe' => true,
                'allow_pos' => true,
                'has_api_access' => true,
                'has_emissor' => true, // Tem acesso ao emissor Delphi
                'has_erp' => true, // Tem acesso ao ERP completo
                'support_type' => '24/7',
                'display_features' => [],
            ],
        ]);
    }
}