<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nfe_notes') && !Schema::hasColumn('nfe_notes', 'modelo')) {
            Schema::table('nfe_notes', function (Blueprint $table) {
                $table->unsignedTinyInteger('modelo')->default(55)->after('serie_nfe'); // 55 = NFe, 65 = NFC-e
                $table->index(['modelo']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nfe_notes') && Schema::hasColumn('nfe_notes', 'modelo')) {
            Schema::table('nfe_notes', function (Blueprint $table) {
                $table->dropIndex(['modelo']);
                $table->dropColumn('modelo');
            });
        }
    }
};


