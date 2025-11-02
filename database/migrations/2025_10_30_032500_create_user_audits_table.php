<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->onDelete('set null'); // quem fez
            $table->foreignId('target_user_id')->nullable()->constrained('users')->onDelete('set null'); // usuário afetado
            $table->string('action'); // created_user, updated_user, deleted_user, role_assigned, role_revoked, perm_granted, perm_revoked
            $table->json('changes')->nullable(); // diffs de nome/email/roles/perms
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['action']);
            $table->index(['actor_user_id']);
            $table->index(['target_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_audits');
    }
};


