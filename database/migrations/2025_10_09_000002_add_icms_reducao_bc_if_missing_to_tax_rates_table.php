<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tax_rates') && !Schema::hasColumn('tax_rates', 'icms_reducao_bc')) {
            Schema::table('tax_rates', function (Blueprint $table) {
                $table->decimal('icms_reducao_bc', 6, 4)->nullable()->after('icms_aliquota');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tax_rates') && Schema::hasColumn('tax_rates', 'icms_reducao_bc')) {
            Schema::table('tax_rates', function (Blueprint $table) {
                $table->dropColumn('icms_reducao_bc');
            });
        }
    }
};

?>


