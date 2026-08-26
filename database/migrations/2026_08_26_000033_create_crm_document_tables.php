<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('label', 255);
            $table->boolean('is_required')->default(false);
            $table->boolean('actif')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('crm_candidate_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_candidate_id')->constrained('crm_candidates')->cascadeOnDelete();
            $table->foreignId('crm_document_type_id')->constrained('crm_document_types')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['crm_candidate_id', 'crm_document_type_id'], 'crm_candidate_doc_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_candidate_documents');
        Schema::dropIfExists('crm_document_types');
    }
};
