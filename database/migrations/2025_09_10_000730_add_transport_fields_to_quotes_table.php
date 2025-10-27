<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'volume_qtd')) {
                $table->integer('volume_qtd')->nullable()->after('fiscal_info');
            }
            if (!Schema::hasColumn('quotes', 'volume_especie')) {
                $table->string('volume_especie', 50)->nullable()->after('volume_qtd');
            }
            if (!Schema::hasColumn('quotes', 'peso_bruto')) {
                $table->decimal('peso_bruto', 10, 3)->nullable()->after('volume_especie');
            }
            if (!Schema::hasColumn('quotes', 'peso_liquido')) {
                $table->decimal('peso_liquido', 10, 3)->nullable()->after('peso_bruto');
            }
            if (!Schema::hasColumn('quotes', 'valor_seguro')) {
                $table->decimal('valor_seguro', 10, 2)->nullable()->after('peso_liquido');
            }
            if (!Schema::hasColumn('quotes', 'outras_despesas')) {
                $table->decimal('outras_despesas', 10, 2)->nullable()->after('valor_seguro');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            foreach (['outras_despesas','valor_seguro','peso_liquido','peso_bruto','volume_especie','volume_qtd'] as $col) {
                if (Schema::hasColumn('quotes', $col)) { $table->dropColumn($col); }
            }
        });
    }
};


