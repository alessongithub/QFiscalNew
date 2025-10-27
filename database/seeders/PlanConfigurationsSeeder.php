<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanConfigurationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Configurações do Plano Gratuito
        $freePlanConfigs = [
            'max_clients' => '50',
            'max_users' => '1',
            'max_products' => '100',
            'max_service_orders' => '200',
            'max_quotes' => '100',
            'max_orders' => '100',
            'has_api_access' => 'false',
            'has_support' => 'true',
            'has_reports' => 'true',
            'has_backup' => 'false',
            'storage_limit_mb' => '100',
            'features' => 'basic',
            'can_export_data' => 'false',
            'can_import_data' => 'false',
            'max_file_uploads_per_day' => '10',
            'email_notifications' => 'true',
            'sms_notifications' => 'false',
            'advanced_reports' => 'false',
            'custom_fields' => 'false',
            'api_rate_limit' => '100',
        ];

        // Configurações do Plano Básico
        $basicPlanConfigs = [
            'max_clients' => '200',
            'max_users' => '3',
            'max_products' => '500',
            'max_service_orders' => '1000',
            'max_quotes' => '500',
            'max_orders' => '500',
            'has_api_access' => 'false',
            'has_support' => 'true',
            'has_reports' => 'true',
            'has_backup' => 'true',
            'storage_limit_mb' => '500',
            'features' => 'standard',
            'can_export_data' => 'true',
            'can_import_data' => 'true',
            'max_file_uploads_per_day' => '50',
            'email_notifications' => 'true',
            'sms_notifications' => 'false',
            'advanced_reports' => 'true',
            'custom_fields' => 'true',
            'api_rate_limit' => '500',
        ];

        // Configurações do Plano Profissional
        $professionalPlanConfigs = [
            'max_clients' => '1000',
            'max_users' => '10',
            'max_products' => '2000',
            'max_service_orders' => '5000',
            'max_quotes' => '2000',
            'max_orders' => '2000',
            'has_api_access' => 'true',
            'has_support' => 'true',
            'has_reports' => 'true',
            'has_backup' => 'true',
            'storage_limit_mb' => '2000',
            'features' => 'professional',
            'can_export_data' => 'true',
            'can_import_data' => 'true',
            'max_file_uploads_per_day' => '200',
            'email_notifications' => 'true',
            'sms_notifications' => 'true',
            'advanced_reports' => 'true',
            'custom_fields' => 'true',
            'api_rate_limit' => '2000',
        ];

        // Configurações do Plano Enterprise
        $enterprisePlanConfigs = [
            'max_clients' => '-1',
            'max_users' => '-1',
            'max_products' => '-1',
            'max_service_orders' => '-1',
            'max_quotes' => '-1',
            'max_orders' => '-1',
            'has_api_access' => 'true',
            'has_support' => 'true',
            'has_reports' => 'true',
            'has_backup' => 'true',
            'storage_limit_mb' => '-1',
            'features' => 'enterprise',
            'can_export_data' => 'true',
            'can_import_data' => 'true',
            'max_file_uploads_per_day' => '-1',
            'email_notifications' => 'true',
            'sms_notifications' => 'true',
            'advanced_reports' => 'true',
            'custom_fields' => 'true',
            'api_rate_limit' => '-1',
        ];

        // Inserir configurações para cada plano
        $this->insertPlanConfigurations(1, $freePlanConfigs); // Plano Gratuito
        $this->insertPlanConfigurations(2, $basicPlanConfigs); // Plano Básico
        $this->insertPlanConfigurations(3, $professionalPlanConfigs); // Plano Profissional
        $this->insertPlanConfigurations(4, $enterprisePlanConfigs); // Plano Enterprise
    }

    private function insertPlanConfigurations($planId, $configurations)
    {
        foreach ($configurations as $key => $value) {
            DB::table('plan_configurations')->updateOrInsert(
                [
                    'plan_id' => $planId,
                    'config_key' => $key,
                ],
                [
                    'config_value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
