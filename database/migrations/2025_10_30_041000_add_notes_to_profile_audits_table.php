<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_audits', function (Blueprint $table) {
            if (!Schema::hasColumn('profile_audits', 'notes')) {
                $table->text('notes')->nullable()->after('changes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('profile_audits', function (Blueprint $table) {
            if (Schema::hasColumn('profile_audits', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};


