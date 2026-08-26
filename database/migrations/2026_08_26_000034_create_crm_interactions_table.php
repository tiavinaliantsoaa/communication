<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_candidate_id')->constrained('crm_candidates')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->text('commentaire');
            $table->timestamps();

            $table->index(['crm_candidate_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_interactions');
    }
};
