<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('partners')) {
            Schema::create('partners', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('slug', 100)->unique();
                $table->string('domain', 190)->nullable()->unique();
                $table->decimal('commission_percent', 5, 4)->default(0.3000);
                $table->enum('theme', ['light','dark'])->default('light');
                $table->string('primary_color', 7)->nullable();
                $table->string('secondary_color', 7)->nullable();
                $table->string('logo_path', 255)->nullable();
                $table->boolean('active')->default(true);
                // Marketplace (futuro)
                $table->string('mp_user_id', 100)->nullable();
                $table->string('mp_public_key', 200)->nullable();
                $table->text('mp_access_token')->nullable();
                $table->text('mp_refresh_token')->nullable();
                $table->timestamp('mp_connected_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};


