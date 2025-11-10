<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_payments')) {
            Schema::create('subscription_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subscription_id')->nullable();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->string('provider')->default('celcoin');
                $table->string('provider_payment_id')->nullable();
                $table->string('status')->nullable(); // received, pending, failed
                $table->decimal('amount', 12, 2)->default(0);
                $table->timestamp('paid_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};


