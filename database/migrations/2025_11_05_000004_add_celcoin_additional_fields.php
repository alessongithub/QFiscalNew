<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gateway_configs')) {
            Schema::table('gateway_configs', function (Blueprint $table) {
                if (!Schema::hasColumn('gateway_configs', 'celcoin_galax_id')) {
                    $table->string('celcoin_galax_id')->nullable();
                }
                if (!Schema::hasColumn('gateway_configs', 'celcoin_galax_hash')) {
                    $table->string('celcoin_galax_hash')->nullable();
                }
                if (!Schema::hasColumn('gateway_configs', 'celcoin_public_token')) {
                    $table->string('celcoin_public_token')->nullable();
                }
                if (!Schema::hasColumn('gateway_configs', 'celcoin_api_version')) {
                    $table->string('celcoin_api_version')->nullable(); // v1 | v2
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gateway_configs')) {
            Schema::table('gateway_configs', function (Blueprint $table) {
                foreach (['celcoin_galax_id','celcoin_galax_hash','celcoin_public_token','celcoin_api_version'] as $col) {
                    if (Schema::hasColumn('gateway_configs', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};


