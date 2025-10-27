<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('service_orders', function (Blueprint $table) {
            // Campos para garantia estendida e controle avançado
            $table->integer('override_warranty_days')->nullable()->after('warranty_days');
            $table->text('warranty_notes')->nullable()->after('warranty_until');
            $table->boolean('is_supplier_warranty')->default(false)->after('is_warranty');
        });
    }

    public function down()
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['override_warranty_days', 'warranty_notes', 'is_supplier_warranty']);
        });
    }
};