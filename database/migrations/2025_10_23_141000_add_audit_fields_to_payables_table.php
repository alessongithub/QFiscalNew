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
        Schema::table('payables', function (Blueprint $table) {
            // Campos de auditoria - verificando se já existem
            if (!Schema::hasColumn('payables', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('tenant_id');
            }
            if (!Schema::hasColumn('payables', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('payables', 'paid_by')) {
                $table->unsignedBigInteger('paid_by')->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('payables', 'reversed_by')) {
                $table->unsignedBigInteger('reversed_by')->nullable()->after('paid_by');
            }
            if (!Schema::hasColumn('payables', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('reversed_by');
            }
            if (!Schema::hasColumn('payables', 'reverse_reason')) {
                $table->text('reverse_reason')->nullable()->after('reversed_at');
            }
            if (!Schema::hasColumn('payables', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('reverse_reason');
            }
            
            // Índices para performance
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('paid_by');
            $table->index('reversed_by');
            $table->index('deleted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
            $table->dropIndex(['paid_by']);
            $table->dropIndex(['reversed_by']);
            $table->dropIndex(['deleted_by']);
            
            $table->dropColumn([
                'created_by',
                'updated_by', 
                'paid_by',
                'reversed_by',
                'reversed_at',
                'reverse_reason',
                'deleted_by'
            ]);
        });
    }
};
