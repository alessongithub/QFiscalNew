<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tax_rates')) {
            return;
        }

        Schema::table('tax_rates', function (Blueprint $table) {
            // Use separate condition checks outside the closure in some DBs; inside is acceptable in Laravel
            if (!Schema::hasColumn('tax_rates', 'icmsst_modalidade')) {
                $table->unsignedTinyInteger('icmsst_modalidade')->nullable()->after('irrf_aliquota');
            }
            if (!Schema::hasColumn('tax_rates', 'icmsst_mva')) {
                $table->decimal('icmsst_mva', 6, 4)->nullable()->after('icmsst_modalidade');
            }
            if (!Schema::hasColumn('tax_rates', 'icmsst_aliquota')) {
                $table->decimal('icmsst_aliquota', 6, 4)->nullable()->after('icmsst_mva');
            }
            if (!Schema::hasColumn('tax_rates', 'icmsst_reducao_bc')) {
                $table->decimal('icmsst_reducao_bc', 6, 4)->nullable()->after('icmsst_aliquota');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tax_rates')) {
            return;
        }

        Schema::table('tax_rates', function (Blueprint $table) {
            if (Schema::hasColumn('tax_rates', 'icmsst_reducao_bc')) {
                $table->dropColumn('icmsst_reducao_bc');
            }
            if (Schema::hasColumn('tax_rates', 'icmsst_aliquota')) {
                $table->dropColumn('icmsst_aliquota');
            }
            if (Schema::hasColumn('tax_rates', 'icmsst_mva')) {
                $table->dropColumn('icmsst_mva');
            }
            if (Schema::hasColumn('tax_rates', 'icmsst_modalidade')) {
                $table->dropColumn('icmsst_modalidade');
            }
        });
    }
};

?>


