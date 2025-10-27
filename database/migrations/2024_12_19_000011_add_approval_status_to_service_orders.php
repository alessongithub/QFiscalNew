<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('service_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('service_orders', 'approval_status')) {
                $table->enum('approval_status', ['awaiting', 'approved', 'customer_notified', 'not_approved'])->default('awaiting')->after('finalized_at');
            }
        });
    }

    public function down()
    {
        Schema::table('service_orders', function (Blueprint $table) {
            if (Schema::hasColumn('service_orders', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
        });
    }
};
