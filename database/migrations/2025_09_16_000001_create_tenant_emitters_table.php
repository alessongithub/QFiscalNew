<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_emitters')) {
            Schema::create('tenant_emitters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();

                // Dados cadastrais do emissor
                $table->string('cnpj', 18)->nullable();
                $table->string('ie', 20)->nullable();
                $table->string('razao_social', 255)->nullable();
                $table->string('nome_fantasia', 255)->nullable();

                // Contato
                $table->string('phone', 20)->nullable();
                $table->string('email', 255)->nullable();

                // Endereço
                $table->string('zip_code', 10)->nullable();
                $table->string('address', 255)->nullable();
                $table->string('number', 20)->nullable();
                $table->string('complement', 100)->nullable();
                $table->string('neighborhood', 100)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('state', 2)->nullable();
                $table->string('codigo_ibge', 7)->nullable(); // Código IBGE do município (7 dígitos)

                // Certificado A1 (PFX) - armazenamento seguro em storage privado
                $table->string('certificate_path')->nullable();
                $table->text('certificate_password_encrypted')->nullable();
                $table->date('certificate_valid_until')->nullable();

                // Configurações de emissão
                $table->string('nfe_model', 2)->default('55'); // 55 = NFe
                $table->string('nfe_serie', 3)->default('1');
                $table->unsignedBigInteger('nfe_number_current')->default(0);
                $table->decimal('icms_credit_percent', 5, 2)->nullable(); // Ex.: 3.00

                // Diretórios (opcionais). Se nulos, usar padrão derivado do tenant
                $table->string('base_storage_disk')->nullable(); // ex: local|s3
                $table->string('base_storage_path')->nullable(); // ex: tenants/{id}/nfe

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_emitters');
    }
};


