<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('budget_lignes')) {
            return;
        }

        Schema::create('budget_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('depense_categorie_id')->constrained('depense_categories')->cascadeOnDelete();
            $table->decimal('montant', 15, 2);
            $table->timestamps();

            $table->unique(['budget_id', 'depense_categorie_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lignes');
    }
};
