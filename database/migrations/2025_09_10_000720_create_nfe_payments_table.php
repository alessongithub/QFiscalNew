<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('nfe_payments')) {
            Schema::create('nfe_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('nfe_note_id')->constrained('nfe_notes')->cascadeOnDelete();
                $table->string('payment_method', 30); // DINHEIRO, PIX, BOLETO, CARTAO_CREDITO, CARTAO_DEBITO, etc.
                $table->decimal('amount', 12, 2);
                $table->integer('installments')->default(1);
                $table->date('due_date')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nfe_payments');
    }
};


