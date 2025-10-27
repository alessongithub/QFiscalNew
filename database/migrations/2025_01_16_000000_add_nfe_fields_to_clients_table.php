<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('codigo_ibge', 7)->nullable()->after('zip_code')->comment('Código IBGE da cidade');
            $table->enum('consumidor_final', ['S', 'N'])->default('N')->after('codigo_ibge')->comment('Consumidor final para NFe');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['codigo_ibge', 'consumidor_final']);
        });
    }
};




