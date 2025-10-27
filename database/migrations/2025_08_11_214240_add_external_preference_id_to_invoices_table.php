<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'external_preference_id')) {
                $table->string('external_preference_id')->nullable()->after('description');
            }
            if (!Schema::hasColumn('invoices', 'external_payment_id')) {
                $table->string('external_payment_id')->nullable()->after('external_preference_id');
            }
            if (!Schema::hasColumn('invoices', 'external_status')) {
                $table->string('external_status')->nullable()->after('external_payment_id');
            }
            if (!Schema::hasColumn('invoices', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('external_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['external_preference_id', 'external_payment_id', 'external_status', 'paid_at']);
        });
    }
};