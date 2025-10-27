<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_order_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained()->onDelete('cascade');
            $table->enum('occurrence_type', [
                'client_contact',      // Tentativas de contato, retorno do cliente
                'status_change',       // Mudanças de status com contexto
                'technical_note',      // Observações técnicas, diagnósticos
                'warranty_issue',      // Problemas na garantia
                'delivery_note',       // Observações de entrega
                'payment_note',        // Observações de pagamento
                'other'               // Outras observações gerais
            ]);
            $table->text('description');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_internal')->default(false); // Se é nota interna ou visível ao cliente
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->timestamps();
            
            // Índices para performance
            $table->index(['service_order_id', 'created_at']);
            $table->index(['occurrence_type', 'created_at']);
            $table->index(['created_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_order_occurrences');
    }
};