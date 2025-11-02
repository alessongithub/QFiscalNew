<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (!Schema::hasColumn('news', 'image_url')) {
                $table->string('image_url', 500)->nullable()->after('content');
            }
            if (!Schema::hasColumn('news', 'link_url')) {
                $table->string('link_url', 500)->nullable()->after('image_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (Schema::hasColumn('news', 'image_url')) {
                $table->dropColumn('image_url');
            }
            if (Schema::hasColumn('news', 'link_url')) {
                $table->dropColumn('link_url');
            }
        });
    }
};
