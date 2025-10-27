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
        Schema::table('receivables', function (Blueprint $table) {
            // Campos de auditoria - verificando se já existem
            if (!Schema::hasColumn('receivables', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('tenant_id');
            }
            if (!Schema::hasColumn('receivables', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('receivables', 'received_by')) {
                $table->unsignedBigInteger('received_by')->nullable()->after('received_at');
            }
            if (!Schema::hasColumn('receivables', 'reversed_by')) {
                $table->unsignedBigInteger('reversed_by')->nullable()->after('received_by');
            }
            if (!Schema::hasColumn('receivables', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('reversed_by');
            }
            if (!Schema::hasColumn('receivables', 'reverse_reason')) {
                $table->text('reverse_reason')->nullable()->after('reversed_at');
            }
            if (!Schema::hasColumn('receivables', 'canceled_by')) {
                $table->unsignedBigInteger('canceled_by')->nullable()->after('reverse_reason');
            }
            if (!Schema::hasColumn('receivables', 'canceled_at')) {
                $table->timestamp('canceled_at')->nullable()->after('canceled_by');
            }
            if (!Schema::hasColumn('receivables', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable()->after('canceled_at');
            }
            
            // Índices para performance
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('received_by');
            $table->index('reversed_by');
            $table->index('canceled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
            $table->dropIndex(['received_by']);
            $table->dropIndex(['reversed_by']);
            $table->dropIndex(['canceled_by']);
            
            $table->dropColumn([
                'created_by',
                'updated_by', 
                'received_by',
                'reversed_by',
                'reversed_at',
                'reverse_reason',
                'canceled_by',
                'canceled_at',
                'cancel_reason'
            ]);
        });
    }
};