<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'partner_id')) {
                    $table->foreignId('partner_id')->nullable()->after('id')->constrained('partners')->nullOnDelete();
                }
                if (!Schema::hasColumn('payments', 'application_fee_amount')) {
                    $table->decimal('application_fee_amount', 12, 2)->nullable()->after('amount');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'partner_id')) {
                    $table->dropForeign(['partner_id']);
                    $table->dropColumn('partner_id');
                }
                if (Schema::hasColumn('payments', 'application_fee_amount')) {
                    $table->dropColumn('application_fee_amount');
                }
            });
        }
    }
};


