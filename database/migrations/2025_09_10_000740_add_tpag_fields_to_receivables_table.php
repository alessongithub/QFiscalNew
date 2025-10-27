<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            if (!Schema::hasColumn('receivables', 'tpag_override')) {
                $table->string('tpag_override', 4)->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('receivables', 'tpag_hint')) {
                $table->string('tpag_hint', 30)->nullable()->after('tpag_override');
            }
        });
    }

    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            if (Schema::hasColumn('receivables', 'tpag_hint')) { $table->dropColumn('tpag_hint'); }
            if (Schema::hasColumn('receivables', 'tpag_override')) { $table->dropColumn('tpag_override'); }
        });
    }
};


