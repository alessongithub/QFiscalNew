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
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'features')) {
                $table->json('features')->nullable()->after('description');
            }
            // Garantir que a coluna active existe (pode ser is_active ou active)
            if (!Schema::hasColumn('plans', 'active') && Schema::hasColumn('plans', 'is_active')) {
                // Renomear is_active para active se necessário, ou criar alias
                // Por enquanto, só adicionamos features
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'features')) {
                $table->dropColumn('features');
            }
        });
    }
};
