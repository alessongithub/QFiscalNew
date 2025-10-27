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
        if (!Schema::hasColumn('cash_withdrawals', 'type')) {
            Schema::table('cash_withdrawals', function (Blueprint $table) {
                $table->enum('type', ['normal', 'reversal'])->default('normal')->after('reason');
            });
        }

        if (!Schema::hasColumn('cash_withdrawals', 'reversed_by')) {
            Schema::table('cash_withdrawals', function (Blueprint $table) {
                $table->unsignedBigInteger('reversed_by')->nullable()->after('created_by');
            });
        }

        if (!Schema::hasColumn('cash_withdrawals', 'reversed_at')) {
            Schema::table('cash_withdrawals', function (Blueprint $table) {
                $table->timestamp('reversed_at')->nullable()->after('reversed_by');
            });
        }

        if (!Schema::hasColumn('cash_withdrawals', 'updated_by')) {
            Schema::table('cash_withdrawals', function (Blueprint $table) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('reversed_at');
            });
        }

        // FKs removidos por ora para evitar erro 150 (diferenças de engine/charset/índice em alguns ambientes).
        // Caso necessário, adicionaremos em migration separada e validada no ambiente.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_withdrawals', function (Blueprint $table) {
            $table->dropForeign(['reversed_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['type', 'reversed_by', 'reversed_at', 'updated_by']);
        });
    }
};