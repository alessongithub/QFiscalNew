<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'reopen_preserve_financial')) {
                $table->boolean('reopen_preserve_financial')->default(false)->after('nfe_issued_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'reopen_preserve_financial')) {
                $table->dropColumn('reopen_preserve_financial');
            }
        });
    }
};


