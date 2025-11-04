<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('receivable_id')->nullable()->constrained()->nullOnDelete();

            // Valores
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('mp_fee_amount', 10, 2)->default(0);
            $table->decimal('platform_fee_amount', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2);

            // Status do saldo
            $table->enum('status', ['pending','available','requested','transferring','transferred','failed'])->default('pending');

            // Datas
            $table->timestamp('payment_received_at')->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('transferred_at')->nullable();

            // Transferência
            $table->string('transfer_method')->nullable();
            $table->string('transfer_account')->nullable();
            $table->string('transfer_reference')->nullable();
            $table->text('transfer_notes')->nullable();

            // IDs externos
            $table->string('mp_payment_id')->nullable();
            $table->string('mp_transfer_id')->nullable();

            $table->timestamps();

            $table->index(['tenant_id','status']);
            $table->index(['status','available_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_balances');
    }
};


