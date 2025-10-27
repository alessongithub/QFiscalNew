<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('nfe_notes')) {
            Schema::create('nfe_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->string('serie_nfe', 5)->nullable();
                $table->unsignedBigInteger('numero_nfe')->nullable();
                $table->string('chave_nfe', 60)->nullable()->index();
                $table->string('protocolo_autorizacao', 40)->nullable();
                $table->enum('status', ['criada','transmitida','cancelada','com_cc'])->default('transmitida');
                $table->text('arquivo_xml')->nullable();
                $table->text('arquivo_danfe')->nullable();
                $table->timestamp('data_emissao')->nullable();
                $table->timestamp('data_transmissao')->nullable();
                // Eventos
                $table->text('cancelamento_justificativa')->nullable();
                $table->timestamp('cancelamento_data')->nullable();
                $table->unsignedInteger('cc_sequencia')->default(0);
                $table->text('cc_ultima_correcao')->nullable();
                $table->timestamp('cc_data')->nullable();
                $table->timestamps();
                $table->index(['tenant_id','order_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nfe_notes');
    }
};


