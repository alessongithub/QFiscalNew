<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('plans');

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('max_users')->default(1);
            $table->integer('max_clients')->default(50);
            $table->boolean('has_api_access')->default(false);
            $table->boolean('has_support')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Adicionar referência do plano na tabela tenants
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->after('status')->constrained();
                $table->timestamp('plan_expires_at')->nullable()->after('plan_id');
            }
        });
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'plan_expires_at']);
        });

        Schema::dropIfExists('plans');
    }
};