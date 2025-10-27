<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Permite tenant_id NULL para configurações globais do admin
            $table->unsignedBigInteger('tenant_id')->nullable()->change();
            
            // Remove a constraint única atual
            $table->dropUnique(['tenant_id', 'key']);
            
            // Adiciona nova constraint única que permite NULL
            $table->unique(['tenant_id', 'key'], 'settings_tenant_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Reverte para não permitir NULL
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            
            // Remove a constraint única
            $table->dropUnique('settings_tenant_key_unique');
            
            // Adiciona constraint original
            $table->unique(['tenant_id', 'key']);
        });
    }
};