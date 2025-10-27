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
            // Campos de cancelamento
            if (!Schema::hasColumn('payables', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable()->after('reverse_reason');
            }
            if (!Schema::hasColumn('payables', 'canceled_at')) {
                $table->timestamp('canceled_at')->nullable()->after('cancel_reason');
            }
            if (!Schema::hasColumn('payables', 'canceled_by')) {
                $table->unsignedBigInteger('canceled_by')->nullable()->after('canceled_at');
            }
            
            // Índice para performance
            $table->index('canceled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->dropIndex(['canceled_by']);
            
            $table->dropColumn([
                'cancel_reason',
                'canceled_at',
                'canceled_by'
            ]);
        });
    }
};
