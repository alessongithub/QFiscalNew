<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'canceled_at')) {
                $table->timestamp('canceled_at')->nullable()->after('outras_despesas');
            }
            if (!Schema::hasColumn('quotes', 'canceled_by')) {
                $table->string('canceled_by')->nullable()->after('canceled_at');
            }
            if (!Schema::hasColumn('quotes', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable()->after('canceled_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['canceled_at', 'canceled_by', 'cancel_reason']);
        });
    }
};