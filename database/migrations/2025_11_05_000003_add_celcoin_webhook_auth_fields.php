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
                if (!Schema::hasColumn('gateway_configs', 'celcoin_webhook_type')) {
                    $table->string('celcoin_webhook_type')->nullable(); // basic | jwt
                }
                if (!Schema::hasColumn('gateway_configs', 'celcoin_webhook_login')) {
                    $table->string('celcoin_webhook_login')->nullable();
                }
                if (!Schema::hasColumn('gateway_configs', 'celcoin_webhook_pwd')) {
                    $table->string('celcoin_webhook_pwd')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gateway_configs')) {
            Schema::table('gateway_configs', function (Blueprint $table) {
                if (Schema::hasColumn('gateway_configs', 'celcoin_webhook_type')) {
                    $table->dropColumn('celcoin_webhook_type');
                }
                if (Schema::hasColumn('gateway_configs', 'celcoin_webhook_login')) {
                    $table->dropColumn('celcoin_webhook_login');
                }
                if (Schema::hasColumn('gateway_configs', 'celcoin_webhook_pwd')) {
                    $table->dropColumn('celcoin_webhook_pwd');
                }
            });
        }
    }
};


