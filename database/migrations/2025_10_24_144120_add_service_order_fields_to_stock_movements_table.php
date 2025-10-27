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
        Schema::table('stock_movements', function (Blueprint $table) {
            // Adicionar campos necessários para integração com Service Orders
            if (!Schema::hasColumn('stock_movements', 'service_order_id')) {
                $table->unsignedBigInteger('service_order_id')->nullable()->after('product_id');
                $table->foreign('service_order_id')->references('id')->on('service_orders')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('stock_movements', 'movement_type')) {
                $table->enum('movement_type', ['in', 'out'])->after('service_order_id');
            }
            
            if (!Schema::hasColumn('stock_movements', 'reason')) {
                $table->string('reason')->after('movement_type');
            }
            
            if (!Schema::hasColumn('stock_movements', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('reason');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('stock_movements', 'notes')) {
                $table->text('notes')->nullable()->after('user_id');
            }
            
            // Adicionar índices se não existirem
            if (!Schema::hasIndex('stock_movements', ['tenant_id', 'service_order_id'])) {
                $table->index(['tenant_id', 'service_order_id']);
            }
            
            if (!Schema::hasIndex('stock_movements', ['tenant_id', 'movement_type'])) {
                $table->index(['tenant_id', 'movement_type']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // Remover campos adicionados
            $table->dropForeign(['service_order_id']);
            $table->dropForeign(['user_id']);
            $table->dropIndex(['tenant_id', 'service_order_id']);
            $table->dropIndex(['tenant_id', 'movement_type']);
            
            $table->dropColumn([
                'service_order_id',
                'movement_type', 
                'reason',
                'user_id',
                'notes'
            ]);
        });
    }
};
