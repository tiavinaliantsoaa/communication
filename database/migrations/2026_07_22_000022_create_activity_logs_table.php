<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('module', 40); // projet, editorial, evenement, depense, campagne, budget, stock, user, autre
            $table->string('action', 40)->default('update'); // create, update, delete, comment, move, ...
            $table->string('titre');
            $table->text('description');
            $table->string('url')->nullable();
            $table->nullableMorphs('subject');
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['module', 'created_at']);
        });

        // Backfill from existing project activities
        if (Schema::hasTable('projet_activites')) {
            $rows = DB::table('projet_activites')->orderBy('id')->get();
            foreach ($rows as $row) {
                DB::table('activity_logs')->insert([
                    'user_id' => $row->user_id,
                    'module' => 'projet',
                    'action' => 'update',
                    'titre' => 'Gestion de projet',
                    'description' => $row->message,
                    'url' => null,
                    'subject_type' => 'App\\Models\\ProjetCarte',
                    'subject_id' => $row->projet_carte_id,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at ?? $row->created_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
