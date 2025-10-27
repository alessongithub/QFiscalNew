<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partners')) {
            Schema::table('partners', function (Blueprint $table) {
                if (!Schema::hasColumn('partners', 'cnpj')) {
                    $table->string('cnpj', 20)->nullable()->after('domain');
                }
                if (!Schema::hasColumn('partners', 'crc')) {
                    $table->string('crc', 50)->nullable()->after('cnpj');
                }
                if (!Schema::hasColumn('partners', 'contact_name')) {
                    $table->string('contact_name', 150)->nullable()->after('crc');
                }
                if (!Schema::hasColumn('partners', 'contact_email')) {
                    $table->string('contact_email', 190)->nullable()->after('contact_name');
                }
                if (!Schema::hasColumn('partners', 'contact_phone')) {
                    $table->string('contact_phone', 50)->nullable()->after('contact_email');
                }
                if (!Schema::hasColumn('partners', 'applied_at')) {
                    $table->timestamp('applied_at')->nullable()->after('active');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('partners')) {
            Schema::table('partners', function (Blueprint $table) {
                foreach (['cnpj','crc','contact_name','contact_email','contact_phone','applied_at'] as $col) {
                    if (Schema::hasColumn('partners', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};


