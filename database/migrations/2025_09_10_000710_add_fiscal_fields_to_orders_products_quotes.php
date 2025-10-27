<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Orders: informações complementares e ao fisco
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'additional_info')) {
                $table->text('additional_info')->nullable()->after('outras_despesas');
            }
            if (!Schema::hasColumn('orders', 'fiscal_info')) {
                $table->text('fiscal_info')->nullable()->after('additional_info');
            }
        });

        // Products: observações e informações fiscais padrão
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'fiscal_observations')) {
                $table->text('fiscal_observations')->nullable()->after('type');
            }
            if (!Schema::hasColumn('products', 'fiscal_info')) {
                $table->text('fiscal_info')->nullable()->after('fiscal_observations');
            }
        });

        // Quotes: campos para futura herança
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'additional_info')) {
                $table->text('additional_info')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('quotes', 'fiscal_info')) {
                $table->text('fiscal_info')->nullable()->after('additional_info');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'fiscal_info')) { $table->dropColumn('fiscal_info'); }
            if (Schema::hasColumn('orders', 'additional_info')) { $table->dropColumn('additional_info'); }
        });
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'fiscal_info')) { $table->dropColumn('fiscal_info'); }
            if (Schema::hasColumn('products', 'fiscal_observations')) { $table->dropColumn('fiscal_observations'); }
        });
        Schema::table('quotes', function (Blueprint $table) {
            if (Schema::hasColumn('quotes', 'fiscal_info')) { $table->dropColumn('fiscal_info'); }
            if (Schema::hasColumn('quotes', 'additional_info')) { $table->dropColumn('additional_info'); }
        });
    }
};


