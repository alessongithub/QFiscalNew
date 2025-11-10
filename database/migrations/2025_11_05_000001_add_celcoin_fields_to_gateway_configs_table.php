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
                if (!Schema::hasColumn('gateway_configs', 'celcoin_client_id')) {
                    $table->string('celcoin_client_id')->nullable();
                }
                if (!Schema::hasColumn('gateway_configs', 'celcoin_client_secret')) {
                    $table->string('celcoin_client_secret')->nullable();
                }
                if (!Schema::hasColumn('gateway_configs', 'celcoin_webhook_secret')) {
                    $table->string('celcoin_webhook_secret')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gateway_configs')) {
            Schema::table('gateway_configs', function (Blueprint $table) {
                if (Schema::hasColumn('gateway_configs', 'celcoin_client_id')) {
                    $table->dropColumn('celcoin_client_id');
                }
                if (Schema::hasColumn('gateway_configs', 'celcoin_client_secret')) {
                    $table->dropColumn('celcoin_client_secret');
                }
                if (Schema::hasColumn('gateway_configs', 'celcoin_webhook_secret')) {
                    $table->dropColumn('celcoin_webhook_secret');
                }
            });
        }
    }
};


