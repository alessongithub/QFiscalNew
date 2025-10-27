<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Adicionando campos que faltam
            $table->string('fantasy_name')->nullable()->after('name');
            $table->string('address')->nullable()->after('cnpj');
            $table->string('number', 20)->nullable()->after('address');
            $table->string('complement', 100)->nullable()->after('number');
            $table->string('neighborhood', 100)->nullable()->after('complement');
            $table->string('city', 100)->nullable()->after('neighborhood');
            $table->string('state', 2)->nullable()->after('city');
            $table->string('zip_code', 10)->nullable()->after('state');
            $table->boolean('active')->default(true)->after('status');
        });
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'fantasy_name',
                'address',
                'number',
                'complement',
                'neighborhood',
                'city',
                'state',
                'zip_code',
                'active'
            ]);
        });
    }
};