<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_tax_configs')) {
            Schema::create('tenant_tax_configs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->enum('regime_tributario', ['simples_nacional','lucro_presumido','lucro_real'])->default('simples_nacional');
                $table->string('cnae_principal', 20)->nullable();
                // Simples Nacional (se aplicável)
                $table->string('anexo_simples', 10)->nullable(); // I, II, III, IV, V
                $table->decimal('aliquota_simples_nacional', 6, 4)->nullable();
                // Preferências adicionais
                $table->boolean('habilitar_ibpt')->default(false);
                $table->string('codigo_ibpt_padrao', 50)->nullable();
                // Campos de auditoria
                $table->timestamps();
                $table->unique('tenant_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_tax_configs');
    }
};


