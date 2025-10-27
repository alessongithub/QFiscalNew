<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gateway_configs')) {
            Schema::create('gateway_configs', function (Blueprint $table) {
                $table->id();
                $table->string('provider')->default('mercadopago');
                $table->enum('mode', ['sandbox','production'])->default('sandbox');
                // Sandbox keys
                $table->string('public_key_sandbox')->nullable();
                $table->string('access_token_sandbox')->nullable();
                // Production keys
                $table->string('public_key_production')->nullable();
                $table->string('access_token_production')->nullable();
                $table->string('client_id_production')->nullable();
                $table->string('client_secret_production')->nullable();
                // Webhook secret/signature
                $table->string('webhook_secret')->nullable();
                // Policy: days after expiration to block login
                $table->unsignedInteger('block_login_after_days')->default(3);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_configs');
    }
};


