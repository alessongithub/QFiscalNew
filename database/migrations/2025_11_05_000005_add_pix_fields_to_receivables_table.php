<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('receivables')) {
            Schema::table('receivables', function (Blueprint $table) {
                if (!Schema::hasColumn('receivables', 'pix_mp_id')) {
                    $table->string('pix_mp_id')->nullable();
                }
                if (!Schema::hasColumn('receivables', 'pix_qr_code')) {
                    $table->text('pix_qr_code')->nullable();
                }
                if (!Schema::hasColumn('receivables', 'pix_qr_code_base64')) {
                    $table->text('pix_qr_code_base64')->nullable();
                }
                if (!Schema::hasColumn('receivables', 'pix_emitted_at')) {
                    $table->timestamp('pix_emitted_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('receivables')) {
            Schema::table('receivables', function (Blueprint $table) {
                if (Schema::hasColumn('receivables', 'pix_mp_id')) {
                    $table->dropColumn('pix_mp_id');
                }
                if (Schema::hasColumn('receivables', 'pix_qr_code')) {
                    $table->dropColumn('pix_qr_code');
                }
                if (Schema::hasColumn('receivables', 'pix_qr_code_base64')) {
                    $table->dropColumn('pix_qr_code_base64');
                }
                if (Schema::hasColumn('receivables', 'pix_emitted_at')) {
                    $table->dropColumn('pix_emitted_at');
                }
            });
        }
    }
};

