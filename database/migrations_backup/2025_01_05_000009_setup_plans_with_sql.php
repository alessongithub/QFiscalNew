<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Desabilitar verificação de chaves estrangeiras
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. Dropar tabela plans
        DB::statement('DROP TABLE IF EXISTS plans;');

        // 3. Criar tabela plans
        DB::statement('
            CREATE TABLE plans (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                slug varchar(255) NOT NULL,
                description text,
                price decimal(10,2) NOT NULL,
                max_users int NOT NULL DEFAULT 1,
                max_clients int NOT NULL DEFAULT 50,
                has_api_access tinyint(1) NOT NULL DEFAULT 0,
                has_support tinyint(1) NOT NULL DEFAULT 1,
                is_active tinyint(1) NOT NULL DEFAULT 1,
                created_at timestamp NULL DEFAULT NULL,
                updated_at timestamp NULL DEFAULT NULL,
                deleted_at timestamp NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY plans_slug_unique (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        // 4. Remover colunas antigas de plano da tabela tenants se existirem
        $columns = DB::select("SHOW COLUMNS FROM tenants WHERE Field IN ('plan_id', 'plan_expires_at')");
        if (!empty($columns)) {
            DB::statement('ALTER TABLE tenants DROP FOREIGN KEY IF EXISTS tenants_plan_id_foreign;');
            DB::statement('ALTER TABLE tenants DROP COLUMN IF EXISTS plan_id;');
            DB::statement('ALTER TABLE tenants DROP COLUMN IF EXISTS plan_expires_at;');
        }

        // 5. Adicionar novas colunas de plano na tabela tenants
        DB::statement('
            ALTER TABLE tenants
            ADD COLUMN plan_id bigint unsigned NULL AFTER status,
            ADD COLUMN plan_expires_at timestamp NULL DEFAULT NULL AFTER plan_id,
            ADD CONSTRAINT tenants_plan_id_foreign FOREIGN KEY (plan_id) REFERENCES plans (id);
        ');

        // 6. Inserir planos
        DB::table('plans')->insert([
            [
                'name' => 'Plano Gratuito',
                'slug' => 'free',
                'description' => 'Plano gratuito com recursos básicos',
                'price' => 0.00,
                'max_users' => 1,
                'max_clients' => 50,
                'has_api_access' => false,
                'has_support' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Plano Básico',
                'slug' => 'basic',
                'description' => 'Plano básico com recursos essenciais',
                'price' => 49.90,
                'max_users' => 3,
                'max_clients' => 200,
                'has_api_access' => false,
                'has_support' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Plano Profissional',
                'slug' => 'professional',
                'description' => 'Plano profissional com recursos avançados',
                'price' => 99.90,
                'max_users' => 10,
                'max_clients' => 1000,
                'has_api_access' => true,
                'has_support' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Plano Enterprise',
                'slug' => 'enterprise',
                'description' => 'Plano empresarial com recursos ilimitados',
                'price' => 199.90,
                'max_users' => -1,
                'max_clients' => -1,
                'has_api_access' => true,
                'has_support' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // 7. Reabilitar verificação de chaves estrangeiras
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('ALTER TABLE tenants DROP FOREIGN KEY IF EXISTS tenants_plan_id_foreign;');
        DB::statement('ALTER TABLE tenants DROP COLUMN IF EXISTS plan_id;');
        DB::statement('ALTER TABLE tenants DROP COLUMN IF EXISTS plan_expires_at;');
        DB::statement('DROP TABLE IF EXISTS plans;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};