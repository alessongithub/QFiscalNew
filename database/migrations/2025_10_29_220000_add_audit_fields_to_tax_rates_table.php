<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_rates', function (Blueprint $table) {
            // Campos de auditoria
            if (!Schema::hasColumn('tax_rates', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('tenant_id')->constrained('users')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('tax_rates', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            }
            
            // Índices para auditoria
            if (!Schema::hasColumn('tax_rates', 'created_by')) {
                $table->index('created_by');
            }
            if (!Schema::hasColumn('tax_rates', 'updated_by')) {
                $table->index('updated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tax_rates', function (Blueprint $table) {
            if (Schema::hasColumn('tax_rates', 'updated_by')) {
                $table->dropForeign(['updated_by']);
                $table->dropIndex(['updated_by']);
                $table->dropColumn('updated_by');
            }
            
            if (Schema::hasColumn('tax_rates', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropIndex(['created_by']);
                $table->dropColumn('created_by');
            }
        });
    }
};

