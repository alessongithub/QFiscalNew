<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_tax_configs', function (Blueprint $table) {
            // Campo de auditoria
            if (!Schema::hasColumn('tenant_tax_configs', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('codigo_ibpt_padrao')->constrained('users')->onDelete('set null');
                $table->index('updated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenant_tax_configs', function (Blueprint $table) {
            if (Schema::hasColumn('tenant_tax_configs', 'updated_by')) {
                $table->dropForeign(['updated_by']);
                $table->dropIndex(['updated_by']);
                $table->dropColumn('updated_by');
            }
        });
    }
};

