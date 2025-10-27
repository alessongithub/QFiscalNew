<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tax_rates')) {
            Schema::create('tax_rates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->enum('tipo_nota', ['produto','servico']);
                $table->string('ncm', 10)->nullable();
                $table->string('cfop', 10)->nullable();
                $table->string('codigo_servico', 30)->nullable();
                // Aliquotas
                $table->decimal('icms_aliquota', 6, 4)->nullable();
                $table->decimal('icms_reducao_bc', 6, 4)->nullable();
                $table->decimal('pis_aliquota', 6, 4)->nullable();
                $table->decimal('cofins_aliquota', 6, 4)->nullable();
                $table->decimal('iss_aliquota', 6, 4)->nullable();
                $table->decimal('csll_aliquota', 6, 4)->nullable();
                $table->decimal('inss_aliquota', 6, 4)->nullable();
                $table->decimal('irrf_aliquota', 6, 4)->nullable();
                // ICMS-ST
                $table->unsignedTinyInteger('icmsst_modalidade')->nullable(); // 0=Margem,1=Pauta, etc.
                $table->decimal('icmsst_mva', 6, 4)->nullable();
                $table->decimal('icmsst_aliquota', 6, 4)->nullable();
                $table->decimal('icmsst_reducao_bc', 6, 4)->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamps();
                $table->index(['tenant_id','tipo_nota','ncm','cfop']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};


