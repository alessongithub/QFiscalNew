<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run()
    {
        // Plano Gratuito
        Plan::updateOrCreate(['slug' => 'free'], [
            'name' => 'Plano Gratuito',
            'slug' => 'free',
            'description' => 'Plano gratuito com recursos básicos do ERP',
            'price' => 0.00,
            'active' => true,
            'is_active' => true,
            'features' => [
                // Limites de recursos
                'max_users' => 1,
                'max_clients' => 50,
                'max_products' => 50,
                // Features de acesso
                'has_api_access' => false,
                'has_emissor' => false,
                'has_erp' => true,
                'allow_issue_nfe' => false,
                'allow_pos' => false,
                'erp_access_level' => null,
                'support_type' => 'email',
                // Limites de armazenamento
                'storage_data_mb' => 50,
                'storage_files_mb' => 500,
                'additional_data_price' => 9.90,
                'additional_files_price' => 9.90,
                // Display
                'display_features' => [
                    'Até 50 clientes',
                    'Até 50 produtos',
                    '1 usuário (administrador)',
                    'ERP básico',
                    'Sem emissão de NF-e',
                    'Sem acesso ao PDV',
                    'Sem emissor fiscal',
                    'Suporte por email',
                    '50 MB de dados',
                    '500 MB de arquivos'
                ],
            ],
        ]);

        // Plano Emissor Fiscal
        Plan::updateOrCreate(['slug' => 'emissor'], [
            'name' => 'Plano Emissor Fiscal',
            'slug' => 'emissor',
            'description' => 'Plano focado em emissão fiscal com ERP em modo gratuito',
            'price' => 39.90,
            'active' => true,
            'is_active' => true,
            'features' => [
                // Limites de recursos
                'max_users' => 1,
                'max_clients' => 50,
                'max_products' => 50,
                // Features de acesso
                'has_api_access' => false,
                'has_emissor' => true,
                'has_erp' => true,
                'allow_issue_nfe' => false, // Não emite pelo ERP, apenas pelo Emissor Fiscal
                'allow_pos' => false,
                'erp_access_level' => 'free', // ERP funciona como plano gratuito
                'support_type' => 'email',
                // Limites de armazenamento
                'storage_data_mb' => 60,
                'storage_files_mb' => 1024, // 1 GB
                'additional_data_price' => 9.90,
                'additional_files_price' => 9.90,
                // Display
                'display_features' => [
                    'Emissor Fiscal',
                    'Emissão ilimitada de NF-e pelo Emissor',
                    'ERP em modo gratuito',
                    'Até 50 clientes no ERP',
                    'Até 50 produtos no ERP',
                    '1 usuário',
                    'Sem emissão NF-e pelo ERP',
                    'Sem acesso ao PDV',
                    'Suporte por email',
                    '60 MB de dados',
                    '1 GB de arquivos'
                ],
            ],
        ]);

        // Plano Básico
        Plan::updateOrCreate(['slug' => 'basic'], [
            'name' => 'Plano Básico',
            'slug' => 'basic',
            'description' => 'Plano básico com recursos essenciais do ERP',
            'price' => 49.90,
            'active' => true,
            'is_active' => true,
            'features' => [
                // Limites de recursos
                'max_users' => 1, // Não multiusuário conforme PLANOS_E_REGRAS.md
                'max_clients' => 200,
                'max_products' => null, // Sem limite definido (null)
                // Features de acesso
                'has_api_access' => false,
                'has_emissor' => false,
                'has_erp' => true,
                'allow_issue_nfe' => true,
                'allow_pos' => true,
                'erp_access_level' => null,
                'support_type' => 'email',
                // Limites de armazenamento
                'storage_data_mb' => 120,
                'storage_files_mb' => 2048, // 2 GB
                'additional_data_price' => 9.90,
                'additional_files_price' => 9.90,
                // Display
                'display_features' => [
                    'Até 200 clientes',
                    'Produtos ilimitados',
                    '1 usuário',
                    'Emissão de NF-e',
                    'Acesso ao PDV',
                    'ERP completo',
                    'Sem acesso ao Emissor Fiscal',
                    'Suporte por email',
                    '120 MB de dados',
                    '2 GB de arquivos'
                ],
            ],
        ]);

        // Plano Profissional
        Plan::updateOrCreate(['slug' => 'professional'], [
            'name' => 'Plano Profissional',
            'slug' => 'professional',
            'description' => 'Plano profissional com recursos avançados e multiusuário',
            'price' => 99.90,
            'active' => true,
            'is_active' => true,
            'features' => [
                // Limites de recursos
                'max_users' => 10, // Multiusuário
                'max_clients' => 1000,
                'max_products' => null, // Sem limite definido (null)
                // Features de acesso
                'has_api_access' => true,
                'has_emissor' => true,
                'has_erp' => true,
                'allow_issue_nfe' => true,
                'allow_pos' => true,
                'erp_access_level' => null,
                'support_type' => 'priority',
                // Limites de armazenamento
                'storage_data_mb' => 240,
                'storage_files_mb' => 5120, // 5 GB
                'additional_data_price' => 9.90,
                'additional_files_price' => 9.90,
                // Display
                'display_features' => [
                    'Até 1000 clientes',
                    'Produtos ilimitados',
                    'Até 10 usuários',
                    'Emissão de NF-e',
                    'Acesso ao PDV',
                    'Emissor Fiscal',
                    'Acesso à API',
                    'ERP completo',
                    'Suporte prioritário',
                    '240 MB de dados',
                    '5 GB de arquivos'
                ],
            ],
        ]);

        // Plano Enterprise
        Plan::updateOrCreate(['slug' => 'enterprise'], [
            'name' => 'Plano Enterprise',
            'slug' => 'enterprise',
            'description' => 'Plano empresarial com recursos ilimitados e integração com loja virtual',
            'price' => 199.90,
            'active' => true,
            'is_active' => true,
            'features' => [
                // Limites de recursos
                'max_users' => -1, // Ilimitado
                'max_clients' => -1, // Ilimitado
                'max_products' => -1, // Ilimitado
                // Features de acesso
                'has_api_access' => true,
                'has_emissor' => true,
                'has_erp' => true,
                'allow_issue_nfe' => true,
                'allow_pos' => true,
                'has_loja_virtual' => true, // Integração com loja virtual
                'erp_access_level' => null,
                'support_type' => '24/7',
                // Limites de armazenamento
                'storage_data_mb' => -1, // Ilimitado
                'storage_files_mb' => -1, // Ilimitado
                'additional_data_price' => 9.90,
                'additional_files_price' => 9.90,
                // Display
                'display_features' => [
                    'Clientes ilimitados',
                    'Produtos ilimitados',
                    'Usuários ilimitados',
                    'Emissão de NF-e',
                    'Acesso ao PDV',
                    'Emissor Fiscal',
                    'Acesso à API',
                    'ERP completo',
                    'Loja Virtual integrada',
                    'Suporte 24/7',
                    'Armazenamento ilimitado'
                ],
            ],
        ]);

        // Plano Platinum
        Plan::updateOrCreate(['slug' => 'platinum'], [
            'name' => 'Plano Platinum',
            'slug' => 'platinum',
            'description' => 'Plano premium com todos os recursos Enterprise, consultoria MKT Digital, manutenção de hardware e integrações personalizadas. Após pagamento inicial, nossa equipe entra em contato para ajustar valor conforme necessidade real.',
            'price' => 455.00,
            'active' => true,
            'is_active' => true,
            'features' => [
                // Limites de recursos
                'max_users' => -1, // Ilimitado
                'max_clients' => -1, // Ilimitado
                'max_products' => -1, // Ilimitado
                // Features de acesso
                'has_api_access' => true,
                'has_emissor' => true,
                'has_erp' => true,
                'allow_issue_nfe' => true,
                'allow_pos' => true,
                'has_loja_virtual' => true, // Integração com loja virtual
                'has_mkt_digital_consulting' => true, // Consultoria MKT Digital
                'has_hardware_maintenance' => true, // Manutenção de hardware (somente mão de obra)
                'has_custom_integrations' => true, // Outras integrações conforme necessidade
                'requires_custom_pricing' => true, // Valor ajustado conforme necessidade real após primeiro pagamento
                'erp_access_level' => null,
                'support_type' => '24/7',
                // Limites de armazenamento
                'storage_data_mb' => -1, // Ilimitado
                'storage_files_mb' => -1, // Ilimitado
                'additional_data_price' => 9.90,
                'additional_files_price' => 9.90,
                // Display
                'display_features' => [
                    'Todos os recursos Enterprise',
                    'Loja Virtual integrada',
                    'Consultoria MKT Digital',
                    'Manutenção de Hardware (mão de obra)',
                    'Integrações personalizadas',
                    'Equipe de suporte dedicada',
                    'Ajuste de valor conforme necessidade',
                    'Clientes ilimitados',
                    'Produtos ilimitados',
                    'Usuários ilimitados',
                    'Suporte 24/7',
                    'Armazenamento ilimitado'
                ],
            ],
        ]);
    }
}
