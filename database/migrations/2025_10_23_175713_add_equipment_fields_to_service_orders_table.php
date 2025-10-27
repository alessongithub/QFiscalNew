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
        Schema::table('service_orders', function (Blueprint $table) {
            // Campos de equipamento
            if (!Schema::hasColumn('service_orders', 'equipment_brand')) {
                $table->string('equipment_brand')->nullable()->after('description');
            }
            if (!Schema::hasColumn('service_orders', 'equipment_model')) {
                $table->string('equipment_model')->nullable()->after('equipment_brand');
            }
            if (!Schema::hasColumn('service_orders', 'equipment_serial')) {
                $table->string('equipment_serial')->nullable()->after('equipment_model');
            }
            if (!Schema::hasColumn('service_orders', 'equipment_description')) {
                $table->text('equipment_description')->nullable()->after('equipment_serial');
            }
            if (!Schema::hasColumn('service_orders', 'defect_reported')) {
                $table->text('defect_reported')->nullable()->after('equipment_description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            if (Schema::hasColumn('service_orders', 'equipment_brand')) {
                $table->dropColumn('equipment_brand');
            }
            if (Schema::hasColumn('service_orders', 'equipment_model')) {
                $table->dropColumn('equipment_model');
            }
            if (Schema::hasColumn('service_orders', 'equipment_serial')) {
                $table->dropColumn('equipment_serial');
            }
            if (Schema::hasColumn('service_orders', 'equipment_description')) {
                $table->dropColumn('equipment_description');
            }
            if (Schema::hasColumn('service_orders', 'defect_reported')) {
                $table->dropColumn('defect_reported');
            }
        });
    }
};