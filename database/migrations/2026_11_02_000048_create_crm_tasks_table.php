<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_tasks')) {
            return;
        }

        Schema::create('crm_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_candidate_id')->constrained('crm_candidates')->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->text('commentaire')->nullable();
            $table->timestamp('due_at');
            $table->string('statut', 32)->default('ouverte');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('due_notified_at')->nullable();
            $table->timestamps();

            $table->index(['assigned_to', 'statut', 'due_at']);
            $table->index(['crm_candidate_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_tasks');
    }
};
