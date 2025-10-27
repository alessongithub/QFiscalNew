<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Remover chave estrangeira se existir
        try {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropForeign(['plan_id']);
            });
        } catch (\Exception $e) {
            // Ignora erro se a chave não existir
        }

        // 2. Remover colunas de plano se existirem
        try {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn(['plan_id', 'plan_expires_at']);
            });
        } catch (\Exception $e) {
            // Ignora erro se as colunas não existirem
        }

        // 3. Dropar tabela plans se existir
        Schema::dropIfExists('plans');

        // 4. Criar tabela plans do zero
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('max_users')->default(1);
            $table->integer('max_clients')->default(50);
            $table->boolean('has_api_access')->default(false);
            $table->boolean('has_support')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Adicionar colunas de plano na tabela tenants
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('status')->constrained();
            $table->timestamp('plan_expires_at')->nullable()->after('plan_id');
        });

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
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'plan_expires_at']);
        });

        Schema::dropIfExists('plans');
    }
};