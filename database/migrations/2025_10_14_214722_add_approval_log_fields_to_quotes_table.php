<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'approved_by')) {
                $table->string('approved_by')->nullable()->after('cancel_reason');
            }
            if (!Schema::hasColumn('quotes', 'approval_reason')) {
                $table->text('approval_reason')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['approved_by', 'approval_reason']);
        });
    }
};