<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Remover índice único anterior do cpf_cnpj
            $table->dropUnique(['cpf_cnpj']);
            
            // Adicionar tenant_id
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            // Criar índice único composto para cpf_cnpj + tenant_id
            $table->unique(['cpf_cnpj', 'tenant_id']);
        });
    }

    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Remover índice composto
            $table->dropUnique(['cpf_cnpj', 'tenant_id']);
            
            // Remover foreign key e coluna
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
            
            // Recriar índice único original
            $table->unique('cpf_cnpj');
        });
    }
};