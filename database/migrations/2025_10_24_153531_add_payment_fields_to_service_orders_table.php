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
        Schema::table('service_orders', function (Blueprint $table) {
            // Campos para parcelamento
            $table->integer('installments')->nullable()->after('payment_method');
            $table->decimal('entry_amount', 10, 2)->nullable()->after('installments');
            $table->string('entry_method')->nullable()->after('entry_amount');
            
            // Campos para cálculo de juros
            $table->decimal('interest_rate', 5, 2)->nullable()->after('entry_method');
            $table->decimal('interest_amount', 10, 2)->nullable()->after('interest_rate');
            $table->decimal('total_with_interest', 10, 2)->nullable()->after('interest_amount');
            
            // Campos para controle de recebíveis
            $table->boolean('receivables_created')->default(false)->after('total_with_interest');
            $table->timestamp('receivables_created_at')->nullable()->after('receivables_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn([
                'installments',
                'entry_amount', 
                'entry_method',
                'interest_rate',
                'interest_amount',
                'total_with_interest',
                'receivables_created',
                'receivables_created_at'
            ]);
        });
    }
};
