<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_transfer_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade')->unique();

            // Conta bancária
            $table->string('bank_name')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('agency')->nullable();
            $table->string('account')->nullable();
            $table->string('account_type')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('account_holder_document')->nullable();

            // PIX
            $table->string('pix_key')->nullable();
            $table->enum('pix_key_type', ['cpf','cnpj','email','phone','random'])->nullable();

            // Preferências
            $table->enum('preferred_method', ['pix','ted'])->default('pix');
            $table->boolean('auto_transfer_enabled')->default(false);
            $table->decimal('auto_transfer_min_amount', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_transfer_settings');
    }
};


