<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'volume_qtd')) {
                $table->integer('volume_qtd')->nullable()->after('nfe_issued_at');
            }
            if (!Schema::hasColumn('orders', 'volume_especie')) {
                $table->string('volume_especie', 50)->nullable()->after('volume_qtd');
            }
            if (!Schema::hasColumn('orders', 'peso_bruto')) {
                $table->decimal('peso_bruto', 10, 3)->nullable()->after('volume_especie');
            }
            if (!Schema::hasColumn('orders', 'peso_liquido')) {
                $table->decimal('peso_liquido', 10, 3)->nullable()->after('peso_bruto');
            }
            if (!Schema::hasColumn('orders', 'valor_seguro')) {
                $table->decimal('valor_seguro', 10, 2)->nullable()->after('peso_liquido');
            }
            if (!Schema::hasColumn('orders', 'outras_despesas')) {
                $table->decimal('outras_despesas', 10, 2)->nullable()->after('valor_seguro');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'outras_despesas')) {
                $table->dropColumn('outras_despesas');
            }
            if (Schema::hasColumn('orders', 'valor_seguro')) {
                $table->dropColumn('valor_seguro');
            }
            if (Schema::hasColumn('orders', 'peso_liquido')) {
                $table->dropColumn('peso_liquido');
            }
            if (Schema::hasColumn('orders', 'peso_bruto')) {
                $table->dropColumn('peso_bruto');
            }
            if (Schema::hasColumn('orders', 'volume_especie')) {
                $table->dropColumn('volume_especie');
            }
            if (Schema::hasColumn('orders', 'volume_qtd')) {
                $table->dropColumn('volume_qtd');
            }
        });
    }
};


