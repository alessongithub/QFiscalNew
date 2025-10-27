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
        Schema::create('service_order_cancellations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('service_order_id');
            $table->text('cancellation_reason');
            $table->unsignedBigInteger('cancelled_by');
            $table->timestamp('cancelled_at');
            $table->json('impact_analysis')->nullable(); // Análise de impactos
            $table->boolean('stock_reversed')->default(false);
            $table->boolean('payments_reversed')->default(false);
            $table->boolean('warranties_cancelled')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('service_order_id')->references('id')->on('service_orders')->onDelete('cascade');
            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index(['tenant_id', 'service_order_id']);
            $table->index(['tenant_id', 'cancelled_at']);
            $table->index(['tenant_id', 'cancelled_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_order_cancellations');
    }
};