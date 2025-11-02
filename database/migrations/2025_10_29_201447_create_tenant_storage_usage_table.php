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
        Schema::create('tenant_storage_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            // Uso atual (atualizado via trigger ou scheduled job)
            $table->bigInteger('data_size_bytes')->default(0)->comment('Tamanho dos dados em bytes');
            $table->bigInteger('files_size_bytes')->default(0)->comment('Tamanho dos arquivos em bytes');
            
            // Espaço adicional comprado (mesmo padrão do Bling)
            $table->integer('additional_data_mb')->default(0)->comment('MB adicionais comprados');
            $table->integer('additional_files_mb')->default(0)->comment('MB adicionais de arquivos comprados');
            
            // Cache da última atualização
            $table->timestamp('last_calculated_at')->nullable();
            
            $table->timestamps();
            
            $table->unique('tenant_id');
            
            // Índice para performance (busca rápida por tenant)
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_storage_usage');
    }
};
