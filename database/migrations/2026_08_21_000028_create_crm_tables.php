<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_candidates', function (Blueprint $table) {
            $table->id();
            $table->string('prenom');
            $table->string('nom');
            $table->string('genre', 20)->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('telephone', 40)->nullable();
            $table->string('email')->nullable();
            $table->text('adresse')->nullable();

            $table->string('programme')->nullable();
            $table->string('annee_academique', 50)->nullable();
            $table->string('niveau_etudes', 100)->nullable();
            $table->string('etablissement_origine')->nullable();

            $table->string('statut', 40)->default('nouveau')->index();
            $table->string('source', 40)->nullable();
            $table->foreignId('advisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('pipeline_order')->default(0);
            $table->timestamps();

            $table->index(['advisor_id', 'statut']);
            $table->index('created_at');
        });

        Schema::create('crm_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_candidate_id')->constrained('crm_candidates')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('content');
            $table->timestamps();

            $table->index(['crm_candidate_id', 'created_at']);
        });

        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_candidate_id')->constrained('crm_candidates')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['crm_candidate_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('crm_notes');
        Schema::dropIfExists('crm_candidates');
    }
};
