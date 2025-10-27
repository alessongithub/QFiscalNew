<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfe_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('numero_pedido')->unique(); // Número do pedido/OS
            $table->string('numero_nfe')->nullable(); // Número da NFe emitida
            $table->string('protocolo')->nullable(); // Protocolo de autorização
            $table->string('chave_acesso')->nullable(); // Chave de acesso da NFe
            $table->string('xml_path')->nullable(); // Caminho do XML no Delphi
            $table->string('pdf_path')->nullable(); // Caminho do PDF (se gerado)
            $table->enum('status', ['pending', 'emitted', 'error', 'cancelled'])->default('pending');
            $table->text('error_message')->nullable(); // Mensagem de erro se houver
            $table->json('payload_sent')->nullable(); // Payload enviado para o Delphi
            $table->json('response_received')->nullable(); // Resposta recebida do Delphi
            $table->timestamp('emitted_at')->nullable(); // Data/hora da emissão
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['numero_pedido']);
            $table->index(['numero_nfe']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfe_notes');
    }
};
