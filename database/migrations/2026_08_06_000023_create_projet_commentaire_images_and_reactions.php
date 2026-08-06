<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projet_commentaire_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_commentaire_id')->constrained('projet_commentaires')->cascadeOnDelete();
            $table->string('path');
            $table->string('nom')->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
        });

        Schema::create('projet_commentaire_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_commentaire_id')->constrained('projet_commentaires')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->timestamps();

            $table->unique(['projet_commentaire_id', 'user_id', 'emoji'], 'commentaire_user_emoji_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projet_commentaire_reactions');
        Schema::dropIfExists('projet_commentaire_images');
    }
};
