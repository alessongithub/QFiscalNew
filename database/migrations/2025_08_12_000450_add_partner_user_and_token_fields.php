<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'partner_id')) {
                    $table->foreignId('partner_id')->nullable()->after('tenant_id')->constrained('partners')->nullOnDelete();
                }
                if (!Schema::hasColumn('users', 'invite_token')) {
                    $table->string('invite_token', 100)->nullable()->after('remember_token');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'partner_id')) {
                    $table->dropForeign(['partner_id']);
                    $table->dropColumn('partner_id');
                }
                if (Schema::hasColumn('users', 'invite_token')) {
                    $table->dropColumn('invite_token');
                }
            });
        }
    }
};


