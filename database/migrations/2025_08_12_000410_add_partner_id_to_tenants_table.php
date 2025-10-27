<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenants') && !Schema::hasColumn('tenants', 'partner_id')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->foreignId('partner_id')->nullable()->after('id')->constrained('partners')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'partner_id')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropForeign(['partner_id']);
                $table->dropColumn('partner_id');
            });
        }
    }
};


