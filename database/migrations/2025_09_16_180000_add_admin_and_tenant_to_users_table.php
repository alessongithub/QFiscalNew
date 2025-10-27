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
                if (!Schema::hasColumn('users', 'tenant_id')) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->after('password');
                }
                if (!Schema::hasColumn('users', 'is_admin')) {
                    $table->boolean('is_admin')->default(false)->after('tenant_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'is_admin')) {
                    $table->dropColumn('is_admin');
                }
                if (Schema::hasColumn('users', 'tenant_id')) {
                    $table->dropColumn('tenant_id');
                }
            });
        }
    }
};


