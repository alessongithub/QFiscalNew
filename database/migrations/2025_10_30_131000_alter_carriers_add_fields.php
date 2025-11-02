<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carriers', function (Blueprint $table) {
            if (!Schema::hasColumn('carriers', 'trade_name')) {
                $table->string('trade_name', 255)->nullable()->after('name');
            }
            if (!Schema::hasColumn('carriers', 'ie')) {
                $table->string('ie', 30)->nullable()->after('cnpj');
            }
            if (!Schema::hasColumn('carriers', 'street')) {
                $table->string('street', 255)->nullable()->after('ie');
            }
            if (!Schema::hasColumn('carriers', 'number')) {
                $table->string('number', 30)->nullable()->after('street');
            }
            if (!Schema::hasColumn('carriers', 'complement')) {
                $table->string('complement', 100)->nullable()->after('number');
            }
            if (!Schema::hasColumn('carriers', 'district')) {
                $table->string('district', 100)->nullable()->after('complement');
            }
            if (!Schema::hasColumn('carriers', 'vehicle_plate')) {
                $table->string('vehicle_plate', 10)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('carriers', 'vehicle_state')) {
                $table->string('vehicle_state', 2)->nullable()->after('vehicle_plate');
            }
            if (!Schema::hasColumn('carriers', 'rntc')) {
                $table->string('rntc', 20)->nullable()->after('vehicle_state');
            }
        });
    }

    public function down(): void
    {
        Schema::table('carriers', function (Blueprint $table) {
            if (Schema::hasColumn('carriers', 'trade_name')) { $table->dropColumn('trade_name'); }
            if (Schema::hasColumn('carriers', 'ie')) { $table->dropColumn('ie'); }
            if (Schema::hasColumn('carriers', 'street')) { $table->dropColumn('street'); }
            if (Schema::hasColumn('carriers', 'number')) { $table->dropColumn('number'); }
            if (Schema::hasColumn('carriers', 'complement')) { $table->dropColumn('complement'); }
            if (Schema::hasColumn('carriers', 'district')) { $table->dropColumn('district'); }
            if (Schema::hasColumn('carriers', 'vehicle_plate')) { $table->dropColumn('vehicle_plate'); }
            if (Schema::hasColumn('carriers', 'vehicle_state')) { $table->dropColumn('vehicle_state'); }
            if (Schema::hasColumn('carriers', 'rntc')) { $table->dropColumn('rntc'); }
        });
    }
};


