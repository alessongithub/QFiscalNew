<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tax_credits')) {
            Schema::create('tax_credits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('document_type', 20)->default('nfe'); // nfe, nfce, etc
                $table->string('document_number', 20); // número da nota
                $table->string('document_series', 10)->nullable();
                $table->date('document_date');
                $table->string('supplier_cnpj', 20)->nullable();
                $table->string('supplier_name', 255)->nullable();
                
                // Valores fiscais da entrada
                $table->decimal('base_calculo_icms', 12, 2)->default(0);
                $table->decimal('valor_icms', 12, 2)->default(0);
                $table->decimal('aliquota_icms', 6, 4)->default(0);
                $table->string('cst_icms', 3)->nullable();
                $table->string('cfop', 4)->nullable();
                $table->string('ncm', 10)->nullable();
                
                // Quantidade e valor unitário
                $table->decimal('quantity', 12, 3)->default(0);
                $table->decimal('unit_price', 10, 2)->default(0);
                $table->decimal('total_value', 12, 2)->default(0);
                
                // Controle de utilização
                $table->decimal('quantity_used', 12, 3)->default(0); // qtd já utilizada
                $table->decimal('valor_icms_used', 12, 2)->default(0); // ICMS já utilizado
                $table->boolean('fully_used')->default(false);
                
                // Status e observações
                $table->enum('status', ['active', 'used', 'cancelled'])->default('active');
                $table->text('observations')->nullable();
                $table->timestamps();
                
                // Índices
                $table->index(['tenant_id', 'product_id', 'status']);
                $table->index(['tenant_id', 'document_number', 'document_series']);
                $table->index(['tenant_id', 'supplier_cnpj']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_credits');
    }
};
