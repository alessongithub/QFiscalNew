<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nfe_notes') && !Schema::hasColumn('nfe_notes', 'serie_nfe')) {
            Schema::table('nfe_notes', function (Blueprint $table) {
                $table->string('serie_nfe', 5)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nfe_notes') && Schema::hasColumn('nfe_notes', 'serie_nfe')) {
            Schema::table('nfe_notes', function (Blueprint $table) {
                $table->dropColumn('serie_nfe');
            });
        }
    }
};


